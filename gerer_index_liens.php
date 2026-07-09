<?php
require_once __DIR__ . '/includes/general/session_start_pwa.php';
require_once __DIR__ . '/includes/general/db.php';
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . "/includes/general/notifications.php";

include __DIR__ . '/includes/liens/gerer_liens_index.php'
    ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="JCM">
    <title>Gérer les liens d'accueil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png">
    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png">
    <link rel="shortcut icon" href="img/jcm.png">
</head>

<body class="bo-page mt-5 pt-5">
    <?php include __DIR__ . "/includes/general/navbar.php"; ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-link-45deg me-2"></i>Back-office</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Gérer les liens d'accueil</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Modifiez les liens utilisés par les boutons externes
                visibles sur la page d'accueil.</p>
        </div>
    </header>

    <main class="container pb-5">
        <div class="bo-toolbar">
            <a href="admin.php" class="bo-btn bo-btn-outline">
                <i class="bi bi-arrow-left"></i> Retour à l'administration
            </a>
        </div>

        <?php if ($success): ?>
            <div class="bo-alert"><i class="bi bi-check-circle-fill"></i> Les liens ont bien été enregistrés.</div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="bo-alert" style="background:#fff3cd;color:#8a6d3b;border-color:#ffeeba;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$hasTable): ?>
            <div class="bo-alert" style="background:#f8d7da;color:#842029;border-color:#f5c2c7;">
                <i class="bi bi-database-fill-x"></i> La table de configuration n'est pas encore présente dans la base.
                Ajoutez la structure depuis db.sql puis rechargez la page.
            </div>
        <?php endif; ?>

        <div class="bo-card">
            <div class="bo-card-head">
                <h3>Configuration des boutons</h3>
                <span class="bo-help mb-0">Chaque bouton utilise un lien unique enregistré dans la base.</span>
            </div>
            <div class="bo-card-body">
                <form method="post" action="gerer_index_liens.php">
                    <input type="hidden" name="action" value="save">

                    <?php foreach ($linksToRender as $link): ?>
                        <div class="border rounded-4 p-4 mb-4">
                            <h4 class="h6 fw-bold mb-3 text-judo-red"><?= htmlspecialchars($link['description']) ?></h4>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="<?= htmlspecialchars($link['key']) ?>_url">URL</label>
                                    <input id="<?= htmlspecialchars($link['key']) ?>_url" class="form-control"
                                        name="<?= htmlspecialchars($link['key']) ?>_url"
                                        value="<?= htmlspecialchars($link['url']) ?>" required>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="bo-form-actions">
                        <button class="bo-btn bo-btn-primary" type="submit">
                            <i class="bi bi-floppy"></i> Enregistrer les liens
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <footer>
        <?php include __DIR__ . '/includes/general/footer.php'; ?>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>

