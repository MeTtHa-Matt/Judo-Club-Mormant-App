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
    <meta name="mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-title" content="JCM">
    <title>Inscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
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
    <header>
        <?php include __DIR__ . "/includes/general/navbar.php" ?>
    </header>

    <main>
        <div class="container d-flex justify-content-center align-items-start"
            style="padding-top: 8.5rem; padding-bottom: 2rem; min-height: 100vh;">
            <div class="card border-0 shadow-lg overflow-hidden judo-card"
                style="max-width: 550px; width: 100%; border-radius: 16px; margin-top: 0;">

                <div class="card-header card-header-judo text-white text-center py-4 border-0">
                    <i class="bi bi-person-plus-fill fs-1"></i>
                    <h2 class="fw-bold mt-2 mb-0">Créer un compte</h2>
                    <p class="text-white-50 small mb-0">Rejoignez le Judo Club Mormant</p>
                </div>

                <div class="card-body p-4 p-sm-5 bg-white">
                    <form action="includes/account/register_process.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(jcm_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="firstname" class="form-label small fw-semibold text-dark">Prénom</label>
                                <div class="input-group custom-judo-input">
                                    <span class="input-group-text bg-light text-secondary border-end-0"><i
                                            class="bi bi-person"></i></span>
                                    <input type="text" class="form-control bg-light border-start-0 ps-0" id="firstname"
                                        name="firstname" placeholder="John" required autocomplete="given-name">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label for="lastname" class="form-label small fw-semibold text-dark">Nom</label>
                                <div class="input-group custom-judo-input">
                                    <span class="input-group-text bg-light text-secondary border-end-0"><i
                                            class="bi bi-person-vcard"></i></span>
                                    <input type="text" class="form-control bg-light border-start-0 ps-0" id="lastname"
                                        name="lastname" placeholder="Doe" required autocomplete="family-name">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold text-dark">Adresse Email</label>
                            <div class="input-group custom-judo-input">
                                <span class="input-group-text bg-light text-secondary border-end-0"><i
                                        class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control bg-light border-start-0 ps-0" id="email"
                                    name="email" placeholder="john.doe@exemple.com" required autocomplete="email">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold text-dark">Mot de passe</label>
                            <div class="input-group custom-judo-input">
                                <span class="input-group-text bg-light text-secondary border-end-0"><i
                                        class="bi bi-envelope"></i></span>
                                <input type="password" class="form-control bg-light border-start-0 ps-0" id="password"
                                    name="password" placeholder="********" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="pdp" class="form-label small fw-semibold text-dark">Photo de profil</label>
                            <div class="input-group custom-judo-input">
                                <span class="input-group-text bg-light text-secondary border-end-0"><i
                                        class="bi bi-image"></i></span>
                                <input type="file" class="form-control bg-light border-start-0 ps-0" id="pdp" name="pdp"
                                    accept="image/*">
                            </div>
                            <div class="form-text text-muted small">Format recommandé : JPG, PNG (Max. 5Mo)</div>
                        </div>

                        <hr class="my-4">

                        <div class="jcm-toggle-row mb-3">
                            <div class="jcm-toggle-text">
                                <i class="bi bi-envelope-fill"></i>
                                <div>
                                    <p class="jcm-toggle-title mb-0">Recevoir les emails du club</p>
                                    <p class="jcm-toggle-desc mb-0">Actus, convocations et infos importantes par email.
                                    </p>
                                </div>
                            </div>
                            <label class="jcm-switch" for="accept_email_toggle">
                                <input type="checkbox" id="accept_email_toggle" name="accept_email_toggle"
                                    class="jcm-switch-input" value="1" checked>
                                <span class="jcm-switch-track"><span class="jcm-switch-thumb"></span></span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-judo-red w-100 py-2.5 fw-bold shadow-sm mb-3"
                            style="border-radius: 8px;">
                            S'inscrire
                        </button>

                        <div class="text-center mt-3">
                            <p class="small text-secondary mb-0">Déjà membre ? <a href="login.php"
                                    class="link-judo fw-semibold text-decoration-none">Connectez-vous</a></p>
                        </div>

                    </form>
                </div>
            </div>
        </div>

    </main>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>

