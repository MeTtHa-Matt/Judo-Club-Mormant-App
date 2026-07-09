<?php
require_once __DIR__ . '/includes/general/session_start_pwa.php';
require_once __DIR__ . "/includes/general/notifications.php";
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
    <title>Site en maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon.png">`r`n    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">`r`n    <link rel="apple-touch-icon" href="/apple-touch-icon.png">`r`n    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png">`r`n    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png">`r`n    <link rel="shortcut icon" href="img/jcm.png">`r`n</head>

<body>
    <main class="container py-5">
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <h1 class="display-6 fw-bold text-judo-red">Site en maintenance</h1>
            <p class="lead mt-4">Le site est temporairement indisponible. Nous revenons très bientôt.</p>
            <a href="index.php" class="btn btn-judo-outline mt-3">Retour à l'accueil</a>
        </div>
    </main>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>

</body>

</html>

