<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
require_once __DIR__ . '/../general/security.php';
jcm_require_csrf();

if (!isset($_SESSION['id'])) {
    header('Location: ../../register.php');
    exit;
}

$id_account = (int) $_SESSION['id'];
$nom = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$annee_naissance = $_POST['annee_naissance'] ?? null;
$id_ceinture = $_POST['id_ceinture'] ?? null;
$poids = $_POST['Poids'] ?? null;
$id_competition = (int) ($_POST['id_competition'] ?? 0);

if (!jcm_valid_person_name($nom) || !jcm_valid_person_name($prenom)
    || !filter_var($annee_naissance, FILTER_VALIDATE_INT)
    || (int) $annee_naissance < 1900 || (int) $annee_naissance > (int) date('Y')
    || !filter_var($id_ceinture, FILTER_VALIDATE_INT) || (int) $id_ceinture <= 0
    || filter_var($poids, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 300]]) === false) {
    header('Location: ../../competitions.php?alert=' . urlencode('Données d inscription invalides.'));
    exit;
}

if (empty($nom) || empty($prenom) || empty($annee_naissance) || !$id_competition) {
    header('Location: ../../competitions.php?alert=' . urlencode('Tous les champs sont requis pour l inscription.'));
    exit;
}

$stmt = $pdo->prepare("SELECT date, date_limite_inscription FROM competitions WHERE id = ?");
$stmt->execute([$id_competition]);
$competitionData = $stmt->fetch(PDO::FETCH_ASSOC);
$competitionDate = $competitionData['date'] ?? null;
$registrationDeadline = $competitionData['date_limite_inscription'] ?? null;

if (!$competitionDate) {
    header('Location: ../../competitions.php?alert=' . urlencode('Compétition introuvable.'));
    exit;
}

$adminCheck = $pdo->prepare('SELECT admin FROM account WHERE id = ? LIMIT 1');
$adminCheck->execute([$id_account]);
$isAdmin = (int) $adminCheck->fetchColumn() === 1;
$_SESSION['admin'] = $isAdmin ? 1 : 0;

if (!$isAdmin) {
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $effectiveDeadline = !empty($registrationDeadline) ? $registrationDeadline : date('Y-m-d', strtotime($competitionDate . ' -7 days'));
    if ($today >= $effectiveDeadline || $today >= $competitionDate) {
        header('Location: ../../competitions.php?alert=' . urlencode('Les inscriptions sont fermées pour cette compétition.'));
        exit;
    }
}

$duplicate = $pdo->prepare(
    "SELECT 1 FROM inscrits
     WHERE id_account = ? AND id_competition = ? AND nom = ? AND prenom = ?
       AND annee_naissance = ? AND id_ceinture = ?
     LIMIT 1"
);
$duplicate->execute([$id_account, $id_competition, $nom, $prenom, $annee_naissance, $id_ceinture]);
if ($duplicate->fetchColumn()) {
    header('Location: ../../competitions.php?alert=' . urlencode('Cette personne est déjà inscrite à cette compétition.'));
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO inscrits (nom, prenom, annee_naissance, id_ceinture, Poids, id_account, id_competition) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $prenom, $annee_naissance, $id_ceinture ?: null, $poids ?: null, $id_account, $id_competition]);
    header('Location: ../../competitions.php?success=inscrit');
    exit;
} catch (Exception $e) {
    header('Location: ../../competitions.php?alert=' . urlencode('Une erreur serveur est survenue.'));
    exit;
}
