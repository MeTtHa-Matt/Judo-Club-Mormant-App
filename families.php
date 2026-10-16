<?php
require_once __DIR__ . '/includes/general/session_start_pwa.php';
require_once __DIR__ . '/includes/general/db.php';
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . '/includes/general/security.php';
require_once __DIR__ . '/includes/account/family_helpers.php';
require_once __DIR__ . '/includes/general/notifications.php';

jcm_require_admin($pdo);
jcm_ensure_family_schema($pdo);
jcm_require_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $familyId = (int) ($_POST['family_id'] ?? 0);

    try {
        if ($familyId <= 0) {
            throw new InvalidArgumentException('Famille introuvable.');
        }

        if ($action === 'remove_member') {
            $memberId = (int) ($_POST['member_id'] ?? 0);
            if ($memberId <= 0) {
                throw new InvalidArgumentException('Membre introuvable.');
            }

            $pdo->beginTransaction();
            $lockFamily = $pdo->prepare('SELECT id FROM families WHERE id = ? FOR UPDATE');
            $lockFamily->execute([$familyId]);
            if (!$lockFamily->fetchColumn()) {
                throw new InvalidArgumentException('Famille introuvable.');
            }

            $memberCount = $pdo->prepare('SELECT COUNT(*) FROM family_members WHERE family_id = ?');
            $memberCount->execute([$familyId]);
            if ((int) $memberCount->fetchColumn() <= 1) {
                throw new InvalidArgumentException('Supprimez la famille pour retirer son dernier membre.');
            }

            $removeMember = $pdo->prepare('DELETE FROM family_members WHERE family_id = ? AND account_id = ?');
            $removeMember->execute([$familyId, $memberId]);
            if ($removeMember->rowCount() === 0) {
                throw new InvalidArgumentException('Ce membre ne fait pas partie de cette famille.');
            }

            $pdo->commit();
            header('Location: families.php?success=' . urlencode('Le membre a été retiré de la famille.'));
            exit;
        }

        if ($action === 'delete_family') {
            $pdo->beginTransaction();
            $lockFamily = $pdo->prepare('SELECT id FROM families WHERE id = ? FOR UPDATE');
            $lockFamily->execute([$familyId]);
            if (!$lockFamily->fetchColumn()) {
                throw new InvalidArgumentException('Famille introuvable.');
            }

            $detachChildren = $pdo->prepare('UPDATE child_profiles SET family_id = NULL WHERE family_id = ?');
            $detachChildren->execute([$familyId]);
            $deleteFamily = $pdo->prepare('DELETE FROM families WHERE id = ?');
            $deleteFamily->execute([$familyId]);

            $pdo->commit();
            header('Location: families.php?success=' . urlencode('La famille a été supprimée. Les profils enfants ont été conservés.'));
            exit;
        }

        throw new InvalidArgumentException('Action invalide.');
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Location: families.php?alert=' . urlencode($e->getMessage()));
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Family administration error: ' . $e->getMessage());
        header('Location: families.php?alert=' . urlencode('Une erreur est survenue lors de la gestion de la famille.'));
        exit;
    }
}

$families = $pdo->query(
    'SELECT f.id, f.created_at, f.created_by,
            creator.firstname AS creator_firstname, creator.lastname AS creator_lastname,
            (SELECT COUNT(*) FROM family_members fm WHERE fm.family_id = f.id) AS member_count,
            (SELECT COUNT(*) FROM child_profiles cp WHERE cp.family_id = f.id) AS child_count
     FROM families f
     LEFT JOIN account creator ON creator.id = f.created_by
     ORDER BY f.id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

$membersByFamily = [];
$memberStmt = $pdo->query(
    'SELECT fm.family_id, fm.account_id, fm.role, fm.added_by_account_id,
            a.firstname, a.lastname, a.email
     FROM family_members fm
     JOIN account a ON a.id = fm.account_id
     ORDER BY fm.family_id DESC, a.lastname, a.firstname'
);
foreach ($memberStmt->fetchAll(PDO::FETCH_ASSOC) as $member) {
    $membersByFamily[(int) $member['family_id']][] = $member;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <title>Gestion des familles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
</head>

<body>
    <?php include __DIR__ . '/includes/general/navbar.php'; ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge"><span class="badge-text"><i class="bi bi-people-fill me-2"></i>Administration</span></div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Familles</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Consultez et gérez les familles et leurs membres.</p>
        </div>
    </header>

    <main class="container my-5">
        <?php if (empty($families)): ?>
            <div class="alert alert-info">Aucune famille n’est enregistrée.</div>
        <?php else: ?>
            <div class="d-flex flex-column gap-4">
                <?php foreach ($families as $family): ?>
                    <?php $familyId = (int) $family['id']; ?>
                    <section class="card border-0 shadow-sm rounded-4 p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h2 class="h4 fw-bold mb-1">Famille #<?= $familyId ?></h2>
                                <p class="text-muted mb-0">
                                    Créée par
                                    <?= htmlspecialchars(trim(($family['creator_firstname'] ?? '') . ' ' . ($family['creator_lastname'] ?? '')) ?: 'Compte supprimé') ?>
                                    · <?= (int) $family['member_count'] ?> membre(s)
                                    · <?= (int) $family['child_count'] ?> profil(s) enfant
                                </p>
                            </div>
                            <form method="post" action="families.php"
                                onsubmit="return confirm('Supprimer cette famille ? Les comptes resteront actifs et les profils enfants seront conservés, mais détachés de la famille.');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(jcm_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="delete_family">
                                <input type="hidden" name="family_id" value="<?= $familyId ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash me-1"></i>Supprimer la famille
                                </button>
                            </form>
                        </div>

                        <?php $members = $membersByFamily[$familyId] ?? []; ?>
                        <?php if (empty($members)): ?>
                            <p class="text-muted mb-0">Cette famille ne contient aucun membre.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($members as $member): ?>
                                    <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-3 px-0">
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($member['firstname'] . ' ' . $member['lastname']) ?></div>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars($member['email']) ?>
                                                · <?= htmlspecialchars($member['role']) ?>
                                                <?php if ((int) $member['account_id'] === (int) $family['created_by']): ?>
                                                    · Créateur
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (count($members) > 1): ?>
                                            <form method="post" action="families.php"
                                                onsubmit="return confirm('Retirer ce compte de la famille ?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(jcm_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="action" value="remove_member">
                                                <input type="hidden" name="family_id" value="<?= $familyId ?>">
                                                <input type="hidden" name="member_id" value="<?= (int) $member['account_id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Retirer</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>
