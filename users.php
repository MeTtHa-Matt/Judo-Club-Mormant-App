<?php
require_once __DIR__ . "/includes/general/access_check.php";
require_once __DIR__ . "/includes/general/db.php";

include __DIR__ . '/includes/general/users.php'
    ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="JCM">
    <title>Gestion des Utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png">
    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png">
    <link rel="shortcut icon" href="img/jcm.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png?v=20260709">
    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png?v=20260709">
    <link rel="apple-touch-icon" sizes="180x180" href="img/jcm.png?v=20260709">
    <link rel="shortcut icon" href="img/jcm.png?v=20260709">
</head>

<body>
    <?php include __DIR__ . '/includes/general/notifications.php'; ?>
    <?php include __DIR__ . "/includes/general/navbar.php" ?>

    <header class="hero-judo profile-hero text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-people-fill me-2"></i>Administration</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Utilisateurs</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Gérez les membres et l'accès au site</p>
        </div>
    </header>

    <main class="container profile-main mt-5">

        <?php if ($flashSuccess): ?>
            <div class="alert-judo alert-judo-success">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flashSuccess) ?>
            </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="alert-judo alert-judo-error">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($flashError) ?>
            </div>
        <?php endif; ?>

        <div class="bo-toolbar">
            <a href="admin.php" class="bo-btn bo-btn-outline">
                <i class="bi bi-arrow-left"></i> Retour a l'administration
            </a>
        </div>

        <section
            class="maintenance-card card border-0 shadow-sm rounded-4 p-4 mb-5 <?= $maintenanceOn ? 'maintenance-active' : '' ?>">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="maintenance-icon">
                        <i class="bi bi-cone-striped"></i>
                    </div>
                    <div>
                        <h3 class="h5 fw-bold mb-1">Mode maintenance</h3>
                        <p class="text-muted small mb-0">
                            <span class="status-dot <?= $maintenanceOn ? 'status-dot-on' : 'status-dot-off' ?>"></span>
                            <?= $maintenanceOn ? "Le site est actuellement en maintenance" : "Le site est actuellement en ligne" ?>
                        </p>
                    </div>
                </div>
                <form action="users.php" method="POST"
                    onsubmit="return confirm('<?= $maintenanceOn ? 'Désactiver' : 'Activer' ?> le mode maintenance du site ?');">
                    <input type="hidden" name="action" value="toggle_maintenance">
                    <button type="submit" class="btn <?= $maintenanceOn ? 'btn-judo-outline' : 'btn-judo-red' ?>">
                        <i
                            class="bi bi-power me-2"></i><?= $maintenanceOn ? "Désactiver la maintenance" : "Activer la maintenance" ?>
                    </button>
                </form>
            </div>
        </section>

        <section>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <h2 class="h2 fw-bold text-uppercase section-title mb-0">Liste des <span
                        class="text-judo-red">Membres</span></h2>
                <form action="users.php" method="GET" class="users-search" id="usersSearchForm">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i
                                class="bi bi-search text-judo-red"></i></span>
                        <input type="text" name="q" id="usersSearchInput"
                            class="form-control profile-input border-start-0"
                            placeholder="Rechercher un nom, prénom, email..." value="<?= htmlspecialchars($search) ?>"
                            autocomplete="off">
                    </div>
                </form>
            </div>

            <div id="usersResults">
                <?= renderUserResults($users, $search, $userId) ?>
            </div>
        </section>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="js/users-search.js?v=<?php echo filemtime('js/users-search.js'); ?>"></script>
    <?php include __DIR__ . '/includes/general/footer.php'; ?>
</body>

</html>

