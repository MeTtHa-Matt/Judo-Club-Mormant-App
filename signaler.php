<?php
require_once __DIR__ . '/includes/general/access_check.php';
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
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png">
    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png">
    <link rel="shortcut icon" href="img/jcm.png">
</head>

<body class="<?= !empty($activeReport) ? 'report-conversation-open report-user-conversation-open' : '' ?>">
    <?php include __DIR__ . '/includes/general/navbar.php'; ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-exclamation-triangle-fill me-2"></i>Support</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Signaler un problème</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Un problème sur le site ? Envoyez-nous un signalement vérifié.</p>
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

                        <?php if ($step === 'verify'): ?>
                        <form method="post" action="signaler.php" novalidate>
                            <input type="hidden" name="action" value="verify_code">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['report_csrf']) ?>">
                            <div class="mb-4">
                                <label for="reportCode" class="form-label fw-semibold">Code reçu par email</label>
                                <input id="reportCode" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control form-control-lg text-center letter-spacing-code" autocomplete="one-time-code" required>
                                <div class="form-text">Le code envoyé à <?= htmlspecialchars((string) ($_SESSION['report_verification']['email'] ?? '')) ?> est valable 15 minutes.</div>
                            </div>
                            <button type="submit" class="btn btn-judo-red btn-lg px-4 w-100"><i class="bi bi-shield-check me-2"></i><?= ($_SESSION['report_verification']['purpose'] ?? '') === 'access' ? 'Vérifier mon adresse email' : 'Confirmer et envoyer' ?></button>
                        </form>
                        <?php else: ?>
                        <form method="post" action="signaler.php" novalidate>
                            <input type="hidden" name="action" value="request_code">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['report_csrf']) ?>">
                            <div class="d-none" aria-hidden="true"><label for="website">Site web</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>

                            <div class="mb-4">
                                <label for="reportSubject" class="form-label fw-semibold">Sujet <span class="text-muted">(facultatif)</span></label>
                                <input id="reportSubject" name="subject" type="text"
                                    class="form-control form-control-lg" placeholder="Sujet du signalement"
                                    value="<?= htmlspecialchars($subject) ?>">
                            </div>
                            <?php if ($isReportUserAuthenticated): ?>
                                <p class="small text-muted mb-4"><i class="bi bi-person-check-fill me-1"></i>Signalement envoyé avec l’adresse de votre compte : <strong><?= htmlspecialchars((string) ($reportAccountEmail ?? 'email indisponible')) ?></strong></p>
                            <?php else: ?>
                                <div class="mb-4">
                                    <label for="reportEmail" class="form-label fw-semibold">Votre email</label>
                                    <input id="reportEmail" name="email" type="email" class="form-control form-control-lg" placeholder="vous@exemple.fr" required>
                                </div>
                            <?php endif; ?>

                            <div class="mb-4">
                                <label for="reportMessage" class="form-label fw-semibold">Message</label>
                                <textarea id="reportMessage" name="message" rows="9"
                                    class="form-control form-control-lg"
                                    placeholder="Expliquez le problème en détail..."
                                    required><?= htmlspecialchars($message) ?></textarea>
                            </div>

                            <div
                                class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                                <div class="text-muted small">
                                    <p class="mb-0"><?= $isReportUserAuthenticated ? 'Votre signalement sera envoyé directement.' : 'Un code vous sera envoyé pour confirmer votre adresse.' ?></p>
                                </div>
                                <button type="submit" class="btn btn-judo-red btn-lg px-4 mx-auto">
                                    <i class="bi <?= $isReportUserAuthenticated ? 'bi-send-fill' : 'bi-envelope-fill' ?> me-2"></i><?= $isReportUserAuthenticated ? 'Envoyer le signalement' : 'Recevoir le code' ?>
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>

                        <?php if ($step === 'report' && $reportVerifiedEmail === ''): ?>
                            <hr class="my-4">
                            <div class="text-center">
                                <h2 class="h5 fw-bold">Consulter un signalement existant</h2>
                                <p class="text-muted small">Vérifiez votre adresse email pour retrouver vos signalements et échanger avec le club.</p>
                            </div>
                            <form method="post" action="signaler.php">
                                <input type="hidden" name="action" value="request_access_code">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['report_csrf']) ?>">
                                <label for="reportAccessEmail" class="form-label fw-semibold">Votre email</label>
                                <div class="input-group">
                                    <input id="reportAccessEmail" name="email" type="email" class="form-control" placeholder="vous@exemple.fr" required>
                                    <button type="submit" class="btn btn-judo-outline"><i class="bi bi-envelope me-1"></i>Recevoir un code</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($step === 'report' && $reportVerifiedEmail !== ''): ?>
                    <section class="card border-0 shadow-sm rounded-4 mt-4 p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h2 class="h4 fw-bold mb-1">Mes signalements</h2>
                                <p class="small text-muted mb-0">Adresse vérifiée : <?= htmlspecialchars($reportVerifiedEmail) ?></p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge text-bg-light border"><?= count($userReports) ?></span>
                                <form method="post" action="signaler.php">
                                    <input type="hidden" name="action" value="change_verified_email">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['report_csrf']) ?>">
                                    <button type="submit" class="btn btn-sm btn-judo-outline">Changer d’adresse</button>
                                </form>
                            </div>
                        </div>
                        <?php if (!$userReports): ?>
                            <p class="text-muted mb-0">Aucun signalement associé à cette adresse email.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($userReports as $report): ?>
                                    <?php $hasUnread = jcm_report_has_unread_messages($report, 'user'); ?>
                                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3 px-0"
                                        href="signaler.php?report=<?= rawurlencode($report['id']) ?>#conversation">
                                        <span>
                                            <strong>Signalement #<?= htmlspecialchars(strtoupper(substr($report['id'], 0, 8))) ?></strong>
                                            <span class="d-block small text-muted"><?= htmlspecialchars((string) ($report['subject'] ?? 'Signalement')) ?></span>
                                        </span>
                                        <span class="d-flex align-items-center gap-2 small text-muted text-nowrap">
                                            <?php if ($hasUnread): ?><span class="report-unread-dot" aria-label="Nouvelle réponse non lue"></span><?php endif; ?>
                                            <?= count($report['messages']) ?> message<?= count($report['messages']) === 1 ? '' : 's' ?><i class="bi bi-chevron-right"></i>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php if ($activeReport): ?>
        <?php
        $reportConversation = $activeReport;
        $reportIsAdmin = false;
        $reportBackUrl = 'signaler.php';
        include __DIR__ . '/includes/general/report_chat_view.php';
        ?>
    <?php endif; ?>

    <footer>
        <?php include __DIR__ . '/includes/general/footer.php' ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/report-chat.js?v=<?= filemtime(__DIR__ . '/js/report-chat.js') ?>"></script>
</body>

</html>
