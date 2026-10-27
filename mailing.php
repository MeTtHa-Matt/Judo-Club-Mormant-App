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
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Préparez vos destinataires, rédigez et retrouvez vos envois.</p>
        </div>
    </header>

    <main class="container">
        <div class="mailing-shell mx-auto">
            <section class="mailing-compose-card">
                <?php if ($flashSuccess): ?>
                    <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($flashError): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form id="mailForm" method="post" action="mailing.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(jcm_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="send_mail">
                    <input type="hidden" name="message_html" id="messageHtml">

                    <div class="mailing-recipients">
                        <div class="mailing-recipient-row">
                            <label for="toInput">À</label>
                            <div class="mailing-token-field" data-recipient-field="to">
                                <div class="mailing-chips" aria-live="polite"></div>
                                <input id="toInput" type="text" autocomplete="off" placeholder="E-mail, all, admin ou nadmin">
                            </div>
                        </div>
                        <div class="mailing-suggestions" id="mailingSuggestions" role="listbox" hidden></div>
                    </div>

                    <div class="mailing-subject-wrap">
                        <label for="mailSubject" class="form-label">Objet</label>
                        <input id="mailSubject" name="subject" class="form-control"
                            placeholder="Donnez un objet à votre message" maxlength="255" required>
                    </div>

                    <div class="mailing-content-wrap">
                        <div class="mailing-message-heading">
                            <label class="form-label mb-0">Message</label>
                            <span>Rédigez votre annonce</span>
                        </div>
                        <div class="mailing-message-box">
                            <div id="toolbar" class="mailing-toolbar">
                                <div class="ql-formats">
                                    <button type="button" class="ql-bold" aria-label="Gras" title="Gras"></button>
                                    <button type="button" class="ql-italic" aria-label="Italique" title="Italique"></button>
                                    <button type="button" class="ql-underline" aria-label="Souligné" title="Souligné"></button>
                                </div>
                                <span class="mailing-toolbar-separator" aria-hidden="true"></span>
                                <div class="ql-formats mailing-format-group">
                                    <select class="ql-size" aria-label="Taille du texte">
                                        <option value="small">Petit</option>
                                        <option selected>Moyen</option>
                                        <option value="large">Grand</option>
                                        <option value="huge">Très grand</option>
                                    </select>
                                    <select class="ql-color" aria-label="Couleur"></select>
                                </div>
                                <span class="mailing-toolbar-separator" aria-hidden="true"></span>
                                <div class="ql-formats">
                                    <button type="button" class="ql-image" aria-label="Insérer une image" title="Insérer une image"></button>
                                </div>
                            </div>
                            <div id="editor" class="ql-container ql-snow mailing-editor"
                                aria-label="Éditeur de texte enrichi"></div>
                        </div>
                        <input type="file" id="imageInput" accept="image/*" hidden>
                    </div>

                    <div class="mailing-compose-footer">
                        <button type="submit" class="mail-submit-btn btn btn-danger">
                            <i class="bi bi-send-fill me-2"></i>Envoyer le message
                        </button>
                    </div>
                </form>
            </section>

            <section class="mailing-history-card" aria-labelledby="mailHistoryTitle">
                <div class="mailing-history-heading">
                    <div>
                        <span class="mailing-eyebrow"><i class="bi bi-clock-history me-2"></i>Suivi</span>
                        <h2 id="mailHistoryTitle">Historique des envois</h2>
                    </div>
                    <span class="mailing-history-count"><?= count($mailingHistory) ?> récent<?= count($mailingHistory) > 1 ? 's' : '' ?></span>
                </div>
                <?php if (empty($mailingHistory)): ?>
                    <div class="mailing-history-empty">
                        <i class="bi bi-envelope-open"></i>
                        <p>Aucun mail envoyé pour le moment.</p>
                    </div>
                <?php else: ?>
                    <div class="mailing-history-list">
                        <?php foreach ($mailingHistory as $history): ?>
                            <?php
                            $preview = trim(html_entity_decode(strip_tags((string) $history['message_html']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                            $hasImage = preg_match('/<img\b/i', (string) $history['message_html']) === 1;
                            $statusLabel = match ($history['status']) {
                                'sent' => 'Envoyé',
                                'failed' => 'Échec',
                                default => 'En cours',
                            };
                            ?>
                            <details class="mailing-history-item">
                                <summary>
                                        <span class="mailing-history-icon"><i class="bi bi-envelope-paper"></i></span>
                                        <span class="mailing-history-main">
                                            <strong>
                                                <?= htmlspecialchars($history['subject'], ENT_QUOTES, 'UTF-8') ?>
                                                <?php if ($hasImage): ?>
                                                    <i class="bi bi-paperclip mailing-history-attachment" role="img" aria-label="Image jointe" title="Image jointe"></i>
                                                <?php endif; ?>
                                            </strong>
                                            <small><?= htmlspecialchars(date('d/m/Y à H:i', strtotime($history['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                                · <?= htmlspecialchars(trim((string) $history['sender_firstname'] . ' ' . (string) $history['sender_lastname']), ENT_QUOTES, 'UTF-8') ?></small>
                                        </span>
                                        <span class="mailing-history-status status-<?= htmlspecialchars($history['status'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <i class="bi bi-chevron-down mailing-history-chevron"></i>
                                </summary>
                                <div class="mailing-history-details">
                                        <p><strong>À :</strong> <?= htmlspecialchars(implode(', ', $history['to_emails'] ?? []), ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php if (!empty($history['cc_emails'])): ?>
                                            <p><strong>Cc :</strong> <?= htmlspecialchars(implode(', ', $history['cc_emails']), ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php endif; ?>
                                        <p class="mailing-history-preview"><?= htmlspecialchars($preview, ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer>
        <?php include __DIR__ . '/includes/general/footer.php' ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.min.js"></script>
    <script>
        window.mailingContacts = <?= json_encode($mailingContacts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="js/mailing-editor.js?v=<?= filemtime(__DIR__ . '/js/mailing-editor.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>
