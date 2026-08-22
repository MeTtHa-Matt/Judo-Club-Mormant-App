<?php
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . '/includes/general/db.php';
require_once __DIR__ . '/includes/general/mailer.php';
require_once __DIR__ . "/includes/general/notifications.php";

include __DIR__ . '/includes/general/mailing.php';
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
    <title>Envoyer un mail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png">
    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png">
    <link rel="shortcut icon" href="img/jcm.png">
</head>

<body>
    <?php include 'includes/general/navbar.php'; ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-envelope-paper-fill me-2"></i>Administration</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Envoyer un mail</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Rédigez un message avec mise en forme, images et envoi
                groupé à tous les membres.</p>
        </div>
    </header>

    <main class="container">
        <div class="mailing-shell mx-auto">
            <div class="card mailing-card">
                <div class="card-body p-4 p-md-5">
                    <div
                        class="mailing-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-4">
                        <div>
                            <span class="badge rounded-pill bg-danger-subtle text-danger fw-semibold mb-2">
                                <i class="bi bi-person-lines-fill me-2"></i>Envoi groupé
                            </span>
                            <h2 class="h3 fw-bold mb-1">Envoyer un mail à tous les membres</h2>
                            <p class="mailing-meta mb-0">Rédigez votre contenu, ajoutez des images et envoyez le message
                                à l’ensemble des comptes enregistrés.</p>
                        </div>
                    </div>

                    <?php if ($flashSuccess): ?>
                        <div class="alert alert-success"><i
                                class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flashSuccess) ?></div>
                    <?php endif; ?>
                    <?php if ($flashError): ?>
                        <div class="alert alert-danger"><i
                                class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($flashError) ?></div>
                    <?php endif; ?>

                    <form id="mailForm" method="post" action="mailing.php">
                        <input type="hidden" name="action" value="send_mail">
                        <input type="hidden" name="message_html" id="messageHtml">

                        <div class="mb-3">
                            <label for="mailSubject" class="form-label fw-semibold">Sujet</label>
                            <input id="mailSubject" name="subject" class="form-control form-control-lg"
                                placeholder="Sujet du mail" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contenu</label>
                            <div id="toolbar" class="mailing-toolbar">
                                <div class="ql-formats">
                                    <button type="button" class="ql-bold"></button>
                                    <button type="button" class="ql-italic"></button>
                                    <button type="button" class="ql-underline"></button>
                                </div>
                                <div class="ql-formats">
                                    <select class="ql-size">
                                        <option value="small">Petit</option>
                                        <option selected>Moyen</option>
                                        <option value="large">Grand</option>
                                        <option value="huge">Très grand</option>
                                    </select>
                                </div>
                                <div class="ql-formats">
                                    <select class="ql-color"></select>
                                    <button type="button" class="ql-image"></button>
                                </div>
                            </div>
                            <div id="editor" class="ql-container ql-snow mailing-editor"
                                aria-label="Éditeur de texte enrichi"></div>
                            <input type="file" id="imageInput" accept="image/*" hidden>
                        </div>

                        <div
                            class="mail-actions d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
                            <p class="mailing-meta mb-0">Les emails seront envoyés à tous les comptes avec une adresse
                                email renseignée.</p>
                            <button type="submit" class="mail-submit-btn btn btn-danger btn-lg">
                                <i class="bi bi-send-fill me-2"></i> Envoyer le mail
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <?php include __DIR__ . '/includes/general/footer.php' ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.min.js"></script>
    <script src="js/mailing-editor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>

