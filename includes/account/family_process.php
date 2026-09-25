<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
require_once __DIR__ . '/../general/security.php';
require_once __DIR__ . '/family_helpers.php';

jcm_require_csrf();

if (!isset($_SESSION['id'])) {
    header('Location: ../../login.php');
    exit;
}

$userId = (int) $_SESSION['id'];
$action = $_POST['action'] ?? '';

try {
    jcm_ensure_family_schema($pdo);

    if ($action === 'join_family') {
        $email = trim((string) ($_POST['family_email'] ?? ''));
        if ($email === '') {
            $_SESSION['children_flash_error'] = 'Renseignez l’email du parent à lier à la famille.';
            header('Location: ../../mes_enfants.php');
            exit;
        }

        $targetStmt = $pdo->prepare('SELECT id, firstname, lastname FROM account WHERE email = ? LIMIT 1');
        $targetStmt->execute([$email]);
        $target = $targetStmt->fetch(PDO::FETCH_ASSOC);

        if (!$target) {
            $_SESSION['children_flash_error'] = 'Aucun compte trouvé avec cet email.';
            header('Location: ../../mes_enfants.php');
            exit;
        }

        $targetId = (int) $target['id'];
        if ($targetId === $userId) {
            $_SESSION['children_flash_error'] = 'Vous êtes déjà dans votre propre famille.';
            header('Location: ../../mes_enfants.php');
            exit;
        }

        $sourceFamilyId = jcm_get_or_create_family_for_account($pdo, $userId);
        $targetFamilyId = jcm_get_account_family_id($pdo, $targetId);
        if ($targetFamilyId !== null && (int) $targetFamilyId === (int) $sourceFamilyId) {
            $_SESSION['children_flash_success'] = 'Ce compte est déjà lié à votre famille.';
            header('Location: ../../mes_enfants.php');
            exit;
        }

        $familyId = jcm_link_account_to_family($pdo, $userId, $targetId);
        $_SESSION['children_flash_success'] = 'Le compte a bien été ajouté à votre famille. Les enfants sont maintenant partagés.';
        $_SESSION['family_id'] = $familyId;
        header('Location: ../../mes_enfants.php');
        exit;
    }

    if ($action === 'remove_family_member') {
        $memberAccountId = (int) ($_POST['member_id'] ?? 0);
        if ($memberAccountId <= 0) {
            $_SESSION['children_flash_error'] = 'Compte à retirer introuvable.';
            header('Location: ../../mes_enfants.php');
            exit;
        }

        $familyId = jcm_get_account_family_id($pdo, $userId);
        if ($familyId === null) {
            $_SESSION['children_flash_error'] = 'Vous n’êtes pas rattaché à une famille.';
            header('Location: ../../mes_enfants.php');
            exit;
        }

        if (!jcm_can_remove_family_member($pdo, $userId, $familyId, $memberAccountId)) {
            $_SESSION['children_flash_error'] = 'Vous ne pouvez retirer que les membres que vous avez ajoutés à votre famille.';
            header('Location: ../../mes_enfants.php');
            exit;
        }

        if (jcm_remove_family_member($pdo, $userId, $familyId, $memberAccountId)) {
            $_SESSION['children_flash_success'] = 'Le membre a bien été retiré de la famille.';
        } else {
            $_SESSION['children_flash_error'] = 'Impossible de retirer ce membre pour le moment.';
        }

        header('Location: ../../mes_enfants.php');
        exit;
    }

    $_SESSION['children_flash_error'] = 'Action de famille invalide.';
    header('Location: ../../mes_enfants.php');
    exit;
} catch (Throwable $e) {
    error_log('Family process error: ' . $e->getMessage());
    $_SESSION['children_flash_error'] = 'Erreur technique lors de la gestion de la famille.';
    header('Location: ../../mes_enfants.php');
    exit;
}
