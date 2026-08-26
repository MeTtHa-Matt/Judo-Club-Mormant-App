<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/security.php';
jcm_require_csrf();

if (!isset($_SESSION['id'])) {
    header('Location: ../../login.php');
    exit;
}

$userId = (int) $_SESSION['id'];
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
            $stmt = $pdo->prepare("INSERT INTO child_profiles (account_id, firstname, lastname, annee_naissance, id_ceinture, Poids) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $firstname, $lastname, $annee_naissance, $id_ceinture, $poidsValue]);
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
            $stmt = $pdo->prepare("UPDATE child_profiles SET firstname = ?, lastname = ?, annee_naissance = ?, id_ceinture = ?, Poids = ? WHERE id = ? AND account_id = ?");
            $stmt->execute([$firstname, $lastname, $annee_naissance, $id_ceinture, $poidsValue, $childId, $userId]);
            $_SESSION['children_flash_success'] = 'Profil enfant mis à jour.';
        }
    }

    if ($action === 'delete_child') {
        $childId = (int) ($_POST['child_id'] ?? 0);
        if (!$childId) {
            $_SESSION['children_flash_error'] = 'Profil enfant introuvable.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM child_profiles WHERE id = ? AND account_id = ?");
            $stmt->execute([$childId, $userId]);
            $_SESSION['children_flash_success'] = 'Profil enfant supprimé.';
        }
    }
}

header('Location: ../../mes_enfants.php');
exit;
