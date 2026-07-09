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
}

$maintenanceOn = (bool) $pdo->query("SELECT maintenance FROM account LIMIT 1")->fetchColumn();

$search = trim($_GET['q'] ?? '');

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
        "SELECT id, firstname, lastname, email, pdp, admin, ban FROM account WHERE %s ORDER BY lastname, firstname",
        implode(' OR ', $conditions)
    );
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} else {
    $stmt = $pdo->query("SELECT id, firstname, lastname, email, pdp, admin, ban FROM account ORDER BY lastname, firstname");
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
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden users-table-card">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 custom-judo-table">
                                    <thead class="table-dark text-uppercase small">
                                        <tr>
                                            <th scope="col" class="ps-4 py-3">Membre</th>
                                            <th scope="col" class="py-3 d-none d-md-table-cell">Statut</th>
                                            <th scope="col" class="py-3 text-end pe-4">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $u): ?>
                                                <?php $isSelf = ((int) $u['id'] === $userId); ?>
                                                <tr>
                                                    <td class="ps-4" data-label="Membre">
                                                        <div class="d-flex align-items-center gap-2 flex-wrap">
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
                                                                <div class="d-flex align-items-center gap-1 flex-wrap mt-1 user-status-inline">
                                                                    <?php if ((int) $u['admin'] === 1): ?>
                                                                            <span class="badge-admin badge-sm"><i class="bi bi-shield-fill-check"></i>Admin</span>
                                                                    <?php else: ?>
                                                                            <span class="badge bg-light text-dark border badge-sm">Membre</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="d-none d-md-table-cell" data-label="Statut">
                                                        <?php if ((int) $u['admin'] === 1): ?>
                                                                <span class="badge-admin"><i class="bi bi-shield-fill-check me-1"></i>Admin</span>
                                                        <?php else: ?>
                                                                <span class="badge bg-light text-dark border">Membre</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end pe-4" data-label="Actions">
                                                        <div class="d-flex justify-content-end align-items-center gap-1 user-action-buttons">
                                                            <form action="users.php" method="POST"
                                                                onsubmit="return confirm('<?= $u['ban'] ? 'Débannir' : 'Bannir' ?> <?= htmlspecialchars(addslashes($u['firstname'] . ' ' . $u['lastname'])) ?> ?');">
                                                                <input type="hidden" name="action" value="toggle_ban">
                                                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-user-ban"
                                                                    <?= $isSelf ? 'disabled title="Vous ne pouvez pas vous bannir vous-même"' : '' ?>>
                                                                    <?= $u['ban'] ? "Débannir" : "Bannir" ?>
                                                                </button>
                                                            </form>
                                                            <form action="users.php" method="POST"
                                                                onsubmit="return confirm('<?= $u['admin'] ? 'Retirer les droits admin de' : 'Passer' ?> <?= htmlspecialchars(addslashes($u['firstname'] . ' ' . $u['lastname'])) ?> <?= $u['admin'] ? '' : 'administrateur' ?> ?');">
                                                                <input type="hidden" name="action" value="toggle_admin">
                                                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-user-admin"
                                                                    <?= $isSelf ? 'disabled title="Vous ne pouvez pas modifier votre propre statut"' : '' ?>>
                                                                    <?= $u['admin'] ? "Rétrograder" : "Passer admin" ?>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                <?php endif;
    return ob_get_clean();
}

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'html' => renderUserResults($users, $search, $userId)]);
    exit;
}