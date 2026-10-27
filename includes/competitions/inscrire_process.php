<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
require_once __DIR__ . '/../general/security.php';
require_once __DIR__ . '/../account/family_helpers.php';
jcm_require_csrf();

if (!isset($_SESSION['id'])) {
    header('Location: ../../register.php');
    exit;
}

$idAccount = (int) $_SESSION['id'];
jcm_ensure_family_schema($pdo);
$familyId = jcm_get_or_create_family_for_account($pdo, $idAccount);
$isConfirmation = ($_POST['confirm_profile_update'] ?? '') === '1';
$pending = $_SESSION['pending_family_child_registration'] ?? null;

if ($isConfirmation) {
    $nonce = $_POST['profile_update_nonce'] ?? '';
    if (!is_array($pending)
        || (int) ($pending['account_id'] ?? 0) !== $idAccount
        || !is_string($nonce)
        || !hash_equals((string) ($pending['nonce'] ?? ''), $nonce)
        || !in_array($_POST['profile_update_choice'] ?? '', ['yes', 'no'], true)) {
        unset($_SESSION['pending_family_child_registration']);
        header('Location: ../../competitions.php?alert=' . urlencode('Confirmation invalide ou expirée. Veuillez recommencer l’inscription.'));
        exit;
    }

    $registration = $pending['registration'];
    $updateProfileId = (int) $pending['profile_id'];
    $shouldUpdateProfile = $_POST['profile_update_choice'] === 'yes';
} else {
    $registration = [
        'nom' => trim((string) ($_POST['nom'] ?? '')),
        'prenom' => trim((string) ($_POST['prenom'] ?? '')),
        'annee_naissance' => (string) ($_POST['annee_naissance'] ?? ''),
        'id_ceinture' => (string) ($_POST['id_ceinture'] ?? ''),
        'Poids' => (string) ($_POST['Poids'] ?? ''),
        'id_competition' => (int) ($_POST['id_competition'] ?? 0),
    ];
    $updateProfileId = 0;
    $shouldUpdateProfile = false;
}

$nom = trim((string) ($registration['nom'] ?? ''));
$prenom = trim((string) ($registration['prenom'] ?? ''));
$anneeNaissance = $registration['annee_naissance'] ?? '';
$idCeinture = $registration['id_ceinture'] ?? '';
$poids = $registration['Poids'] ?? '';
$idCompetition = (int) ($registration['id_competition'] ?? 0);

if (!jcm_valid_person_name($nom) || !jcm_valid_person_name($prenom)
    || !filter_var($anneeNaissance, FILTER_VALIDATE_INT)
    || (int) $anneeNaissance < 1900 || (int) $anneeNaissance > (int) date('Y')
    || !filter_var($idCeinture, FILTER_VALIDATE_INT) || (int) $idCeinture <= 0
    || filter_var($poids, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 300]]) === false
    || $idCompetition <= 0) {
    unset($_SESSION['pending_family_child_registration']);
    header('Location: ../../competitions.php?alert=' . urlencode('Données d’inscription invalides.'));
    exit;
}

$stmt = $pdo->prepare('SELECT date, date_limite_inscription FROM competitions WHERE id = ?');
$stmt->execute([$idCompetition]);
$competitionData = $stmt->fetch(PDO::FETCH_ASSOC);
$competitionDate = $competitionData['date'] ?? null;
$registrationDeadline = $competitionData['date_limite_inscription'] ?? null;

if (!$competitionDate) {
    unset($_SESSION['pending_family_child_registration']);
    header('Location: ../../competitions.php?alert=' . urlencode('Compétition introuvable.'));
    exit;
}

$adminCheck = $pdo->prepare('SELECT admin FROM account WHERE id = ? LIMIT 1');
$adminCheck->execute([$idAccount]);
$isAdmin = (int) $adminCheck->fetchColumn() === 1;
$_SESSION['admin'] = $isAdmin ? 1 : 0;

if (!$isAdmin) {
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $effectiveDeadline = !empty($registrationDeadline)
        ? $registrationDeadline
        : date('Y-m-d', strtotime($competitionDate . ' -7 days'));
    if ($today >= $effectiveDeadline || $today >= $competitionDate) {
        unset($_SESSION['pending_family_child_registration']);
        header('Location: ../../competitions.php?alert=' . urlencode('Les inscriptions sont fermées pour cette compétition.'));
        exit;
    }
}

$duplicate = $pdo->prepare(
    'SELECT 1 FROM inscrits i
     WHERE (i.id_account = ? OR EXISTS (
         SELECT 1 FROM family_members fm
         WHERE fm.family_id = ? AND fm.account_id = i.id_account
     ))
       AND i.id_competition = ? AND i.nom = ? AND i.prenom = ?
       AND i.annee_naissance = ? AND i.id_ceinture = ?
     LIMIT 1'
);
$duplicate->execute([$idAccount, $familyId, $idCompetition, $nom, $prenom, $anneeNaissance, $idCeinture]);
if ($duplicate->fetchColumn()) {
    unset($_SESSION['pending_family_child_registration']);
    header('Location: ../../competitions.php?alert=' . urlencode('Cette personne est déjà inscrite à cette compétition.'));
    exit;
}

if (!$isConfirmation) {
    $profile = jcm_find_family_child_profile_by_name($pdo, $familyId, $prenom, $nom);
    if ($profile !== null) {
        $profileDiffers = (int) $profile['annee_naissance'] !== (int) $anneeNaissance
            || (int) $profile['id_ceinture'] !== (int) $idCeinture
            || ($profile['Poids'] === null ? null : (int) $profile['Poids']) !== (int) $poids;

        if ($profileDiffers) {
            $nonce = bin2hex(random_bytes(32));
            $_SESSION['pending_family_child_registration'] = [
                'account_id' => $idAccount,
                'profile_id' => (int) $profile['id'],
                'nonce' => $nonce,
                'registration' => [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'annee_naissance' => (int) $anneeNaissance,
                    'id_ceinture' => (int) $idCeinture,
                    'Poids' => (int) $poids,
                    'id_competition' => $idCompetition,
                ],
            ];
            header('Location: ../../competitions.php?confirm_profile_update=1');
            exit;
        }
    }
}

try {
    $pdo->beginTransaction();

    if ($shouldUpdateProfile) {
        $profileStmt = $pdo->prepare(
            'SELECT firstname, lastname FROM child_profiles WHERE id = ? AND family_id = ? FOR UPDATE'
        );
        $profileStmt->execute([$updateProfileId, $familyId]);
        $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
        if (!$profile || !jcm_person_names_match(
            (string) $profile['firstname'],
            (string) $profile['lastname'],
            $prenom,
            $nom
        )) {
            throw new RuntimeException('Le profil enfant a changé ou n’est plus dans votre famille.');
        }

        $updateStmt = $pdo->prepare(
            'UPDATE child_profiles
             SET annee_naissance = ?, id_ceinture = ?, Poids = ?
             WHERE id = ? AND family_id = ?'
        );
        $updateStmt->execute([(int) $anneeNaissance, (int) $idCeinture, (int) $poids, $updateProfileId, $familyId]);
    }

    $insertStmt = $pdo->prepare(
        'INSERT INTO inscrits (nom, prenom, annee_naissance, id_ceinture, Poids, id_account, id_competition)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $insertStmt->execute([$nom, $prenom, $anneeNaissance, $idCeinture, $poids, $idAccount, $idCompetition]);
    $pdo->commit();
    unset($_SESSION['pending_family_child_registration']);
    header('Location: ../../competitions.php?success=inscrit');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Competition registration error: ' . $e->getMessage());
    unset($_SESSION['pending_family_child_registration']);
    header('Location: ../../competitions.php?alert=' . urlencode(
        $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Une erreur serveur est survenue.'
    ));
    exit;
}
