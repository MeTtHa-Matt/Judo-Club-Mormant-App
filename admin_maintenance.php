<?php
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . '/includes/general/security.php';
jcm_require_admin($pdo);

$flashSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    jcm_require_csrf();
    if (($_POST['action'] ?? '') !== 'toggle_maintenance') {
        http_response_code(400);
        exit('Action invalide.');
    }

    $currentValue = $pdo->query('SELECT maintenance FROM account LIMIT 1')->fetchColumn();
    if ($currentValue === false) {
        throw new RuntimeException('Impossible de lire le mode maintenance : aucun compte trouvé.');
    }

    $newMaintenance = (int) $currentValue === 1 ? 0 : 1;
    $pdo->prepare('UPDATE account SET maintenance = ?')->execute([$newMaintenance]);
    $status = $newMaintenance === 1 ? 'activated' : 'deactivated';
    header('Location: admin_maintenance.php?status=' . $status, true, 303);
    exit;
}

$maintenanceValue = $pdo->query('SELECT maintenance FROM account LIMIT 1')->fetchColumn();
if ($maintenanceValue === false) {
    throw new RuntimeException('Impossible de lire le mode maintenance : aucun compte trouvé.');
}
$maintenanceOn = (int) $maintenanceValue === 1;

if (($_GET['status'] ?? '') === 'activated') {
    $flashSuccess = 'Le mode maintenance est activé. Le site est inaccessible aux membres non-admin.';
} elseif (($_GET['status'] ?? '') === 'deactivated') {
    $flashSuccess = 'Le mode maintenance est désactivé. Le site est de nouveau accessible à tous.';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <title>Maintenance | Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png">
</head>

<body>
    <?php include __DIR__ . '/includes/general/navbar.php'; ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-cone-striped me-2"></i>Paramètres</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Mode maintenance</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Contrôlez l’accès des membres au site.</p>
        </div>
    </header>

    <main class="container profile-main mt-5 pb-5">
        <?php if ($flashSuccess): ?>
            <div class="alert-judo alert-judo-success" role="status">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <section class="maintenance-card card border-0 shadow-sm rounded-4 p-4 p-md-5 <?= $maintenanceOn ? 'maintenance-active' : '' ?>">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="maintenance-icon" aria-hidden="true"><i class="bi bi-cone-striped"></i></div>
                    <div>
                        <h2 class="h4 fw-bold mb-2">État du site</h2>
                        <p class="text-muted mb-0">
                            <span class="status-dot <?= $maintenanceOn ? 'status-dot-on' : 'status-dot-off' ?>"></span>
                            <?= $maintenanceOn ? 'Le site est actuellement en maintenance.' : 'Le site est actuellement en ligne.' ?>
                        </p>
                    </div>
                </div>
                <form action="admin_maintenance.php" method="post"
                    onsubmit="return confirm('<?= $maintenanceOn ? 'Désactiver' : 'Activer' ?> le mode maintenance du site ?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(jcm_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="toggle_maintenance">
                    <button type="submit" class="btn <?= $maintenanceOn ? 'btn-judo-outline' : 'btn-judo-red' ?>">
                        <i class="bi bi-power me-2" aria-hidden="true"></i><?= $maintenanceOn ? 'Désactiver la maintenance' : 'Activer la maintenance' ?>
                    </button>
                </form>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>

</html>
