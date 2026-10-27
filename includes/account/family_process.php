<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
require_once __DIR__ . '/../general/access_check.php';
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

    if ($action === 'send_child_family_request' || $action === 'dismiss_child_account_match') {
        $childId = (int) ($_POST['child_id'] ?? 0);
        $targetId = (int) ($_POST['target_account_id'] ?? 0);

        $familyId = jcm_get_account_family_id($pdo, $userId);
        if ($familyId === null) {
            throw new InvalidArgumentException('Vous n’êtes pas rattaché à une famille.');
        }

        $candidates = jcm_get_family_account_match_candidates($pdo, $familyId, $childId);
        $candidate = null;
        foreach ($candidates as $match) {
            if ((int) $match['account_id'] === $targetId) {
                $candidate = $match;
                break;
            }
        }

        if ($candidate === null) {
            $_SESSION['children_flash_error'] = 'Le profil ne correspond plus à ce compte ou celui-ci est déjà dans la famille.';
            header('Location: ../../mes_enfants.php');
            exit;
        }

        $status = $action === 'send_child_family_request' ? 'pending_child' : 'declined_parent';
        $requestStmt = $pdo->prepare(
            'INSERT INTO family_link_requests
                (family_id, child_profile_id, parent_account_id, child_account_id, status)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                family_id = VALUES(family_id),
                parent_account_id = VALUES(parent_account_id),
                status = VALUES(status),
                updated_at = CURRENT_TIMESTAMP'
        );
        $requestStmt->execute([$familyId, $childId, $userId, $targetId, $status]);
        if ($status === 'pending_child') {
            $_SESSION['children_flash_success'] = 'Une demande de rattachement a été envoyée au compte concerné.';
        } else {
            $_SESSION['children_flash_success'] = 'La proposition a été ignorée.';
        }

        if ((int) ($_SESSION['family_match_pending_child_id'] ?? 0) === $childId) {
            unset($_SESSION['family_match_pending_child_id']);
        }
        header('Location: ../../mes_enfants.php');
        exit;
    }

    if ($action === 'respond_child_family_request') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $decision = (string) ($_POST['decision'] ?? '');
        if ($requestId <= 0 || !in_array($decision, ['accept', 'decline'], true)) {
            throw new InvalidArgumentException('Réponse à la demande invalide.');
        }

        $requestStmt = $pdo->prepare(
            'SELECT r.id, r.family_id, r.parent_account_id, r.child_profile_id,
                    cp.firstname AS child_firstname, cp.lastname AS child_lastname,
                    parent.firstname AS parent_firstname, parent.lastname AS parent_lastname
             FROM family_link_requests r
             JOIN child_profiles cp ON cp.id = r.child_profile_id AND cp.family_id = r.family_id
             JOIN account parent ON parent.id = r.parent_account_id
             JOIN family_members fm ON fm.family_id = r.family_id AND fm.account_id = r.parent_account_id
             WHERE r.id = ? AND r.child_account_id = ? AND r.status = "pending_child"
             LIMIT 1'
        );
        $requestStmt->execute([$requestId, $userId]);
        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
        if (!$request) {
            header('Location: ../../index.php?alert=' . urlencode('Cette demande n’est plus disponible.'));
            exit;
        }

        $accountStmt = $pdo->prepare('SELECT firstname, lastname FROM account WHERE id = ?');
        $accountStmt->execute([$userId]);
        $account = $accountStmt->fetch(PDO::FETCH_ASSOC);
        if (!$account || !jcm_person_names_match(
            (string) $request['child_firstname'],
            (string) $request['child_lastname'],
            (string) $account['firstname'],
            (string) $account['lastname']
        )) {
            $staleStmt = $pdo->prepare('UPDATE family_link_requests SET status = "stale" WHERE id = ?');
            $staleStmt->execute([$requestId]);
            header('Location: ../../index.php?alert=' . urlencode('Les noms ne correspondent plus ; demande annulée.'));
            exit;
        }

        if ($decision === 'accept') {
            jcm_link_account_to_family($pdo, (int) $request['parent_account_id'], $userId);
            $newStatus = 'accepted';
            $successMessage = 'Vous avez rejoint la famille de '
                . $request['parent_firstname'] . ' ' . $request['parent_lastname'] . '.';
        } else {
            $newStatus = 'declined_child';
            $successMessage = 'La demande de rattachement a été refusée.';
        }

        $updateRequest = $pdo->prepare(
            'UPDATE family_link_requests SET status = ?
             WHERE child_account_id = ? AND family_id = ? AND status = "pending_child"'
        );
        $updateRequest->execute([$newStatus, $userId, $request['family_id']]);
        header('Location: ../../index.php?success=' . urlencode($successMessage));
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
