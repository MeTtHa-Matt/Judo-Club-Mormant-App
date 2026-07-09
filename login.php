<?php
require_once __DIR__ . "/includes/general/access_check.php";
require_once __DIR__ . "/includes/general/notifications.php";
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
    <title>Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png?v=20260709">
    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png?v=20260709">
    <link rel="apple-touch-icon" sizes="180x180" href="img/jcm.png?v=20260709">
    <link rel="shortcut icon" href="img/jcm.png?v=20260709">
</head>

<body>
    <header>
        <?php include __DIR__ . "/includes/general/navbar.php" ?>
    </header>

    <body>
        <div class="container min-vh-100 d-flex justify-content-center align-items-center">
            <div class="card border-0 shadow-lg overflow-hidden judo-card"
                style="max-width: 550px; width: 100%; border-radius: 16px;">

                <div class="card-header card-header-judo text-white text-center py-4 border-0">
                    <i class="bi bi-person-plus-fill fs-1"></i>
                    <h2 class="fw-bold mt-2 mb-0">Se connecter</h2>
                </div>

                <div class="card-body p-4 p-sm-5 bg-white">
                    <form action="includes/account/login_process.php" method="POST" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold text-dark">Adresse Email</label>
                            <div class="input-group custom-judo-input">
                                <span class="input-group-text bg-light text-secondary border-end-0"><i
                                        class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control bg-light border-start-0 ps-0" id="email"
                                    name="email" placeholder="john.doe@exemple.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold text-dark">Mot de passe</label>
                            <div class="input-group custom-judo-input">
                                <span class="input-group-text bg-light text-secondary border-end-0"><i
                                        class="bi bi-envelope"></i></span>
                                <input type="password" class="form-control bg-light border-start-0 ps-0" id="password"
                                    name="password" placeholder="********" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-judo-red w-100 py-2.5 fw-bold shadow-sm mb-3"
                            style="border-radius: 8px;">
                            Se Connecter
                        </button>

                        <div class="text-center mt-3">
                            <p class="small text-secondary mb-0">Pas encore inscrit ? <a href="register.php"
                                    class="link-judo fw-semibold text-decoration-none">Connectez-vous</a></p>
                        </div>

                        <div class="text-center mt-3">
                            <p class="small text-secondary mb-0">Vous avez oublié votre mot de passe ?</p>
                            <small><a href="recup_mdp.php" class="link-judo fw-semibold text-decoration-none">Changer
                                    mon mot de passe</a></small>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <?php include __DIR__ . '/includes/general/footer.php'; ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
            crossorigin="anonymous"></script>
    </body>

</html>