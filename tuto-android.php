<?php
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . '/includes/general/notifications.php';
require_once __DIR__ . '/includes/general/install_tutorials.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="JCM">
    <title>Tutoriel Android</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png">
    <link rel="shortcut icon" href="img/jcm.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include __DIR__ . '/includes/general/navbar.php'; ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-android2 me-2"></i>Installer sur Android</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider animate-fade-in hero-title">Tutoriel Android</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-4 fw-light mt-4 hero-subtitle">Judo Club de Mormant</p>
        </div>
    </header>

    <main class="container my-5 pt-4">
        <section class="mb-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 install-card">
                <div class="text-center mb-4">
                    <h2 class="h1 fw-bold text-uppercase position-relative d-inline-block section-title">
                        Installer sur <span class="text-judo-red">Android</span>
                    </h2>
                    <p class="text-muted mt-3 mb-0 mx-auto install-intro">
                        Depuis Chrome, ajoutez cette page à votre écran d’accueil pour accéder rapidement au site comme une application.
                    </p>
                </div>
                <?= jcm_render_tutorial('android'); ?>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>
