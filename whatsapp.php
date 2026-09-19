<?php
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . '/includes/general/notifications.php';

$whatsappLink = trim((string) getenv('LIEN_WHATSAPP'));
$whatsappUrlIsValid = filter_var($whatsappLink, FILTER_VALIDATE_URL)
    && in_array(strtolower((string) parse_url($whatsappLink, PHP_URL_SCHEME)), ['http', 'https'], true);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#128c7e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="JCM">
    <title>Communauté WhatsApp | Judo Club de Mormant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png">
    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png">
    <link rel="shortcut icon" href="img/jcm.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include __DIR__ . '/includes/general/navbar.php'; ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-whatsapp me-2"></i>Vie du club</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider animate-fade-in hero-title">Communauté WhatsApp</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-4 fw-light mt-4 hero-subtitle">Judo Club de Mormant</p>
        </div>
    </header>

    <main class="container my-5 pt-4">
        <section class="whatsapp-community-section mb-5" aria-labelledby="whatsapp-title">
            <div class="whatsapp-community-card">
                <div class="whatsapp-community-mark" aria-hidden="true">
                    <i class="bi bi-whatsapp"></i>
                </div>
                <div class="whatsapp-community-content">
                    <p class="whatsapp-community-eyebrow">Restons en contact</p>
                    <h2 id="whatsapp-title">Rejoignez la communauté du club</h2>
                    <p class="whatsapp-community-text">
                        Retrouvez les informations importantes du Judo Club de Mormant, les actualités et les échanges
                        entre adhérents directement sur WhatsApp.
                    </p>

                    <?php if ($whatsappUrlIsValid): ?>
                        <a class="btn btn-whatsapp" href="<?= htmlspecialchars($whatsappLink, ENT_QUOTES, 'UTF-8') ?>"
                            target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-whatsapp me-2" aria-hidden="true"></i>Rejoindre sur WhatsApp
                            <i class="bi bi-arrow-up-right ms-2" aria-hidden="true"></i>
                        </a>
                        <p class="whatsapp-community-note mb-0">
                            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                            Vous serez redirigé vers WhatsApp pour confirmer votre arrivée.
                        </p>
                    <?php else: ?>
                        <div class="alert alert-light border whatsapp-community-alert mb-0" role="status">
                            <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                            Le lien de la communauté sera bientôt disponible.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>
