<?php
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . "/includes/general/notifications.php";
require_once __DIR__ . '/includes/general/mailer.php';

include __DIR__ . '/includes/general/signaler.php';
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
    <title>Signaler un problème</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="img/jcm.png">
</head>
<body>
    <?php include __DIR__ . '/includes/general/navbar.php'; ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-exclamation-triangle-fill me-2"></i>Support</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Signaler un problème</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Prévenez-nous d'un disfonctionnement du site ou du comportement malsain d'un utilisateur. Maximum 3 signalements par semaine.</p>
        </div>
    </header>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-4 p-lg-5">
                        <?php if ($flashSuccess): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flashSuccess) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($flashError): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-octagon-fill me-2"></i><?= htmlspecialchars($flashError) ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="signaler.php" novalidate>
                            <input type="hidden" name="action" value="send_report">

                            <div class="mb-4">
                                <label for="reportSubject" class="form-label fw-semibold">Sujet</label>
                                <input id="reportSubject" name="subject" type="text" class="form-control form-control-lg" placeholder="Sujet du signalement" required value="<?= htmlspecialchars($subject) ?>">
                            </div>

                            <div class="mb-4">
                                <label for="reportMessage" class="form-label fw-semibold">Message</label>
                                <textarea id="reportMessage" name="message" rows="9" class="form-control form-control-lg" placeholder="Expliquez le problème en détail..." required><?= htmlspecialchars($message) ?></textarea>
                            </div>

                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                                <div class="text-muted small">
                                    <p class="mb-1"><strong>Limite :</strong> 3 signalements par semaine.</p>
                                    <p class="mb-0">Nous répondons rapidement à chaque message reçu.</p>
                                </div>
                                <button type="submit" class="btn btn-judo-red btn-lg px-4 mx-auto">
                                    <i class="bi bi-envelope-fill me-2"></i>Envoyer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <?php include __DIR__ . '/includes/general/footer.php' ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
