<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
require_once __DIR__ . '/../general/access_check.php';
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/security.php';
require_once __DIR__ . '/family_helpers.php';
jcm_require_csrf();

if (!isset($_SESSION['id'])) {
    header('Location: ../../login.php');
    exit;
}

$userId = (int) $_SESSION['id'];
jcm_ensure_family_schema($pdo);
$userFamilyId = jcm_get_or_create_family_for_account($pdo, $userId);
$action = $_POST['action'] ?? '';
$flashSuccess = null;
$flashError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_child') {
        $firstname = trim((string) ($_POST['firstname'] ?? ''));
        $lastname = trim((string) ($_POST['lastname'] ?? ''));
        $annee_naissance = trim((string) ($_POST['annee_naissance'] ?? ''));
        $id_ceinture = (int) ($_POST['id_ceinture'] ?? 0);
        $poids = trim((string) ($_POST['Poids'] ?? ''));

        if ($firstname === '' || $lastname === '' || $annee_naissance === '' || !$id_ceinture) {
            $_SESSION['children_flash_error'] = 'Tous les champs obligatoires doivent être remplis.';
        } elseif (!jcm_valid_person_name($firstname) || !jcm_valid_person_name($lastname)) {
            $_SESSION['children_flash_error'] = 'Nom ou prénom invalide.';
        } elseif (!preg_match('/^\d{4}$/', $annee_naissance) || (int) $annee_naissance < 1900 || (int) $annee_naissance > (int) date('Y')) {
            $_SESSION['children_flash_error'] = 'L’année de naissance est invalide.';
        } else {
            $poidsValue = $poids === '' ? null : filter_var($poids, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 300]]);
            if ($poids !== '' && $poidsValue === false) {
                $_SESSION['children_flash_error'] = 'Poids invalide.';
                header('Location: ../../mes_enfants.php');
                exit;
            }
            $stmt = $pdo->prepare("INSERT INTO child_profiles (account_id, family_id, firstname, lastname, annee_naissance, id_ceinture, Poids) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $userFamilyId, $firstname, $lastname, $annee_naissance, $id_ceinture, $poidsValue]);
            $_SESSION['family_match_pending_child_id'] = (int) $pdo->lastInsertId();
            $_SESSION['children_flash_success'] = 'Profil enfant ajouté avec succès.';
        }
    }

    if ($action === 'update_child') {
        $childId = (int) ($_POST['child_id'] ?? 0);
        $firstname = trim((string) ($_POST['firstname'] ?? ''));
        $lastname = trim((string) ($_POST['lastname'] ?? ''));
        $annee_naissance = trim((string) ($_POST['annee_naissance'] ?? ''));
        $id_ceinture = (int) ($_POST['id_ceinture'] ?? 0);
        $poids = trim((string) ($_POST['Poids'] ?? ''));

        if (!$childId || $firstname === '' || $lastname === '' || $annee_naissance === '' || !$id_ceinture) {
            $_SESSION['children_flash_error'] = 'Tous les champs obligatoires doivent être remplis.';
        } elseif (!jcm_valid_person_name($firstname) || !jcm_valid_person_name($lastname)) {
            $_SESSION['children_flash_error'] = 'Nom ou prénom invalide.';
        } elseif (!preg_match('/^\d{4}$/', $annee_naissance) || (int) $annee_naissance < 1900 || (int) $annee_naissance > (int) date('Y')) {
            $_SESSION['children_flash_error'] = 'L’année de naissance est invalide.';
        } else {
            $poidsValue = $poids === '' ? null : filter_var($poids, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 300]]);
            if ($poids !== '' && $poidsValue === false) {
                $_SESSION['children_flash_error'] = 'Poids invalide.';
                header('Location: ../../mes_enfants.php');
                exit;
            }
            $oldProfileStmt = $pdo->prepare('SELECT firstname, lastname FROM child_profiles WHERE id = ? AND family_id = ?');
            $oldProfileStmt->execute([$childId, $userFamilyId]);
            $updatedProfile = $oldProfileStmt->fetch(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare("UPDATE child_profiles SET firstname = ?, lastname = ?, annee_naissance = ?, id_ceinture = ?, Poids = ? WHERE id = ? AND family_id = ?");
            $stmt->execute([$firstname, $lastname, $annee_naissance, $id_ceinture, $poidsValue, $childId, $userFamilyId]);
            if ($updatedProfile !== false) {
                $_SESSION['family_match_pending_child_id'] = $childId;
                $nameChanged = jcm_normalize_person_name((string) $updatedProfile['firstname'])
                    !== jcm_normalize_person_name($firstname)
                    || jcm_normalize_person_name((string) $updatedProfile['lastname'])
                    !== jcm_normalize_person_name($lastname);
                if ($nameChanged) {
                    $invalidateRequests = $pdo->prepare(
                        'UPDATE family_link_requests
                         SET status = "stale"
                         WHERE child_profile_id = ? AND family_id = ?'
                    );
                    $invalidateRequests->execute([$childId, $userFamilyId]);
                }
            }
            $_SESSION['children_flash_success'] = 'Profil enfant mis à jour.';
        }
    }

    if ($action === 'delete_child') {
        $childId = (int) ($_POST['child_id'] ?? 0);
        if (!$childId) {
            $_SESSION['children_flash_error'] = 'Profil enfant introuvable.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM child_profiles WHERE id = ? AND family_id = ?");
            $stmt->execute([$childId, $userFamilyId]);
            $_SESSION['children_flash_success'] = 'Profil enfant supprimé.';
        }
    }
}

header('Location: ../../mes_enfants.php');
exit;
