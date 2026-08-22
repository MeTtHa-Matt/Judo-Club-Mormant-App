<?php

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['id'];

$stmt = $pdo->prepare("SELECT id, firstname, lastname, admin FROM account WHERE id = ?");
$stmt->execute([$userId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$me) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ((int) $me['admin'] !== 1) {
    header("Location: index.php");
    exit;
}

$flashSuccess = null;
$flashError = null;

$isAjaxRequest = isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_ban') {
        $targetId = (int) ($_POST['user_id'] ?? 0);

        if ($targetId === $userId) {
            $flashError = "Vous ne pouvez pas vous bannir vous-même.";
        } else {
            $stmt = $pdo->prepare("SELECT ban, firstname, lastname FROM account WHERE id = ?");
            $stmt->execute([$targetId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                $flashError = "Utilisateur introuvable.";
            } else {
                $newBan = $target['ban'] ? 0 : 1;
                $update = $pdo->prepare("UPDATE account SET ban = ? WHERE id = ?");
                $update->execute([$newBan, $targetId]);
                $flashSuccess = $newBan
                    ? htmlspecialchars($target['firstname'] . ' ' . $target['lastname']) . " a été banni."
                    : htmlspecialchars($target['firstname'] . ' ' . $target['lastname']) . " a été débanni.";
            }
        }
    }

    if ($_POST['action'] === 'toggle_admin') {
        $targetId = (int) ($_POST['user_id'] ?? 0);

        if ($targetId === $userId) {
            $flashError = "Vous ne pouvez pas modifier votre propre statut administrateur.";
        } else {
            $stmt = $pdo->prepare("SELECT admin, firstname, lastname FROM account WHERE id = ?");
            $stmt->execute([$targetId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                $flashError = "Utilisateur introuvable.";
            } else {
                $newAdmin = $target['admin'] ? 0 : 1;
                $update = $pdo->prepare("UPDATE account SET admin = ? WHERE id = ?");
                $update->execute([$newAdmin, $targetId]);
                $flashSuccess = $newAdmin
                    ? htmlspecialchars($target['firstname'] . ' ' . $target['lastname']) . " est désormais administrateur."
                    : htmlspecialchars($target['firstname'] . ' ' . $target['lastname']) . " n'est plus administrateur.";
            }
        }
    }

    if ($_POST['action'] === 'verify_email') {
        $targetId = (int) ($_POST['user_id'] ?? 0);

        if ($targetId === $userId) {
            $flashError = "Vous ne pouvez pas valider votre propre adresse e-mail depuis cet écran.";
        } else {
            $stmt = $pdo->prepare("SELECT email_verified, firstname, lastname FROM account WHERE id = ?");
            $stmt->execute([$targetId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                $flashError = "Utilisateur introuvable.";
            } elseif ((int) $target['email_verified'] === 1) {
                $flashError = "L'e-mail est déjà validé.";
            } else {
                $update = $pdo->prepare("UPDATE account SET email_verified = 1 WHERE id = ?");
                $update->execute([$targetId]);
                $flashSuccess = htmlspecialchars($target['firstname'] . ' ' . $target['lastname']) . " a désormais un e-mail validé.";
            }
        }
    }

    if ($_POST['action'] === 'toggle_maintenance') {
        $stmt = $pdo->prepare("SELECT maintenance FROM account WHERE id = ?");
        $stmt->execute([$userId]);
        $current = (int) $stmt->fetchColumn();
        $newMaintenance = $current ? 0 : 1;

        $pdo->prepare("UPDATE account SET maintenance = ?")->execute([$newMaintenance]);
        $flashSuccess = $newMaintenance
            ? "Le mode maintenance est activé. Le site est désormais inaccessible aux membres non-admin."
            : "Le mode maintenance est désactivé. Le site est de nouveau accessible à tous.";
    }

    if ($_POST['action'] === 'delete_account') {
        $targetId = (int) ($_POST['user_id'] ?? 0);

        if ($targetId === $userId) {
            $flashError = "Vous ne pouvez pas supprimer votre propre compte depuis cette interface.";
        } else {
            $stmt = $pdo->prepare("SELECT firstname, lastname FROM account WHERE id = ?");
            $stmt->execute([$targetId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                $flashError = "Utilisateur introuvable.";
            } else {
                $delete = $pdo->prepare("DELETE FROM account WHERE id = ?");
                $delete->execute([$targetId]);
                $flashSuccess = htmlspecialchars($target['firstname'] . ' ' . $target['lastname']) . " a été supprimé.";
            }
        }
    }
}

$maintenanceOn = (bool) $pdo->query("SELECT maintenance FROM account LIMIT 1")->fetchColumn();

$search = trim($_GET['q'] ?? $_POST['q'] ?? '');

if ($search !== '') {
    $cleanSearch = preg_replace('/\s+/', ' ', $search);
    $tokens = array_values(array_filter(explode(' ', $cleanSearch), fn($value) => $value !== ''));
    $like = "%$cleanSearch%";
    $conditions = [
        'email LIKE ?',
        'firstname LIKE ?',
        'lastname LIKE ?',
        "CONCAT(firstname, ' ', lastname) LIKE ?",
        "CONCAT(lastname, ' ', firstname) LIKE ?",
    ];
    $params = [$like, $like, $like, $like, $like];

    if (count($tokens) >= 2) {
        $firstLike = "%{$tokens[0]}%";
        $secondLike = "%{$tokens[1]}%";
        $conditions[] = '(firstname LIKE ? AND lastname LIKE ?)';
        $conditions[] = '(lastname LIKE ? AND firstname LIKE ?)';
        $params[] = $firstLike;
        $params[] = $secondLike;
        $params[] = $secondLike;
        $params[] = $firstLike;

        $conditions[] = '(SOUNDEX(firstname) = SOUNDEX(?) AND SOUNDEX(lastname) = SOUNDEX(?))';
        $conditions[] = '(SOUNDEX(lastname) = SOUNDEX(?) AND SOUNDEX(firstname) = SOUNDEX(?))';
        $params[] = $tokens[0];
        $params[] = $tokens[1];
        $params[] = $tokens[1];
        $params[] = $tokens[0];
    } else {
        $conditions[] = 'SOUNDEX(firstname) = SOUNDEX(?)';
        $conditions[] = 'SOUNDEX(lastname) = SOUNDEX(?)';
        $params[] = $cleanSearch;
        $params[] = $cleanSearch;
    }

    $sql = sprintf(
        "SELECT id, firstname, lastname, email, pdp, admin, ban, email_verified FROM account WHERE %s ORDER BY lastname, firstname",
        implode(' OR ', $conditions)
    );
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} else {
    $stmt = $pdo->query("SELECT id, firstname, lastname, email, pdp, admin, ban, email_verified FROM account ORDER BY lastname, firstname");
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

function renderUserResults(array $users, string $search, int $userId): string
{
    ob_start();
    if (count($users) === 0): ?>
                        <div class="profile-empty-state text-center">
                            <i class="bi bi-person-x display-5 text-judo-red"></i>
                            <p class="mt-3 mb-0 text-muted">Aucun utilisateur ne correspond à cette recherche.</p>
                        </div>
                <?php else: ?>
                        <div class="users-card-list">
                            <?php foreach ($users as $u): ?>
                                <?php $isSelf = ((int) $u['id'] === $userId); ?>
                                <div class="user-card" data-user-id="<?= (int) $u['id'] ?>">
                                    <div class="user-card-header" role="button" tabindex="0" aria-expanded="false">
                                        <div class="d-flex align-items-center gap-3 flex-grow-1">
                                            <?php $value = $u['pdp']; ?>
                                            <img src="<?= htmlspecialchars(!empty($u['pdp']) ? "img/pdps/$value" : 'img/pdps/pdp_base.png') ?>"
                                                alt="" class="table-user-avatar">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="fw-bold"><?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']) ?></span>
                                                    <?php if ($isSelf): ?>
                                                        <span class="badge bg-light text-dark border">Vous</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="user-card-toggle"><i class="bi bi-chevron-down"></i></span>
                                    </div>
                                    <div class="user-card-body">
                                        <div class="user-card-details">
                                            <span class="badge <?= (int) $u['email_verified'] === 1 ? 'badge-active' : 'badge-banned' ?>">
                                                <?= (int) $u['email_verified'] === 1 ? 'Email vérifié' : 'Mail non vérifié' ?>
                                            </span>
                                            <span class="badge <?= (int) $u['ban'] === 1 ? 'badge-banned' : 'badge-active' ?>">
                                                <?= (int) $u['ban'] === 1 ? 'Banni' : 'Actif' ?>
                                            </span>
                                            <?php if ((int) $u['admin'] === 1): ?>
                                                <span class="badge-admin">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border badge-sm">Membre</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="user-card-actions">
                                            <button type="button" class="btn btn-sm btn-user-action btn-user-ban"
                                                data-user-action="toggle_ban"
                                                data-user-id="<?= (int) $u['id'] ?>"
                                                <?= $isSelf ? 'disabled title="Vous ne pouvez pas vous bannir vous-même"' : '' ?>>
                                                <?= (int) $u['ban'] === 1 ? 'Débannir' : 'Bannir' ?>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-user-action btn-user-admin"
                                                data-user-action="toggle_admin"
                                                data-user-id="<?= (int) $u['id'] ?>"
                                                <?= $isSelf ? 'disabled title="Vous ne pouvez pas modifier votre propre statut"' : '' ?>>
                                                <?= (int) $u['admin'] === 1 ? 'Rétrograder' : 'Passer admin' ?>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-user-action btn-user-verify-email"
                                                data-user-action="verify_email"
                                                data-user-id="<?= (int) $u['id'] ?>"
                                                <?= (int) $u['email_verified'] === 1 ? 'disabled' : '' ?>>
                                                Valider le mail
                                            </button>
                                            <button type="button" class="btn btn-sm btn-user-action btn-user-delete btn-outline-danger"
                                                data-user-action="delete_account"
                                                data-user-id="<?= (int) $u['id'] ?>"
                                                <?= $isSelf ? 'disabled title="Vous ne pouvez pas supprimer votre propre compte"' : '' ?>>
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                <?php endif;
    return ob_get_clean();
}

if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'html' => renderUserResults($users, $search, $userId)]);
    exit;
}