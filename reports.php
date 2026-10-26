<?php
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . '/includes/general/security.php';
require_once __DIR__ . '/includes/general/notifications.php';

if (!isset($pdo)) {
    require_once __DIR__ . '/includes/general/db.php';
}
jcm_require_admin($pdo);

require_once __DIR__ . '/includes/general/reports_admin.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <title>Signalements | Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png">
</head>
<body class="<?= $reportConversation ? 'report-conversation-open' : '' ?>">
<?php include __DIR__ . '/includes/general/navbar.php'; ?>
<header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
    <div class="hero-pattern"></div>
    <div class="container position-relative text-white py-5">
        <div class="hero-badge"><span class="badge-text"><i class="bi bi-chat-square-text-fill me-2"></i>Administration</span></div>
        <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Signalements</h1>
        <div class="hero-divider"></div>
        <p class="lead fs-5 fw-light mt-3 hero-subtitle">Les échanges vérifiés avec les visiteurs du site</p>
    </div>
</header>
<main class="container my-5 report-admin-main">
    <div class="report-admin-inbox">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div><span class="text-muted small text-uppercase fw-bold">Boîte de réception</span><h2 class="h3 fw-bold mb-0"><?= count($reports) ?> signalement<?= count($reports) > 1 ? 's' : '' ?></h2></div>
        <span class="badge text-bg-light border px-3 py-2"><?= count($reports) ?> conversation<?= count($reports) > 1 ? 's' : '' ?></span>
    </div>
    <?php if (!empty($reportLoadError)): ?>
        <div class="alert alert-danger" role="alert">Impossible de charger certains signalements. Consultez les journaux du serveur.</div>
    <?php endif; ?>
    <?php if (!$reports): ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-inbox display-4 d-block mb-3"></i><p class="mb-0">Aucun signalement reçu.</p></div>
    <?php else: ?>
        <div class="report-threads mt-4">
        <?php foreach ($reports as $report): ?>
            <?php
            $lastMessage = end($report['messages']);
            $hasUnread = jcm_report_has_unread_messages($report, 'admin');
            $lastMessageText = trim((string) ($lastMessage['message'] ?? ''));
            if ($lastMessageText === '' && !empty($lastMessage['attachments'])) {
                $lastMessageText = 'Image jointe';
            }
            ?>
            <a class="report-thread card border-0 shadow-sm mb-3 text-decoration-none" href="reports.php?report=<?= rawurlencode($report['id']) ?>#conversation">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 p-4">
                    <div class="report-thread-content flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="report-thread-icon" aria-hidden="true"><i class="bi bi-folder2-open"></i></span>
                            <strong class="report-thread-subject"><?= htmlspecialchars((string) ($report['subject'] ?? 'Signalement')) ?></strong>
                            <span class="report-thread-id">#<?= htmlspecialchars(strtoupper(substr($report['id'], 0, 8))) ?></span>
                        </div>
                        <div class="report-thread-meta"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars((string) ($report['email'] ?? '')) ?>
                            <span aria-hidden="true">·</span> <?= htmlspecialchars(date('d/m/Y à H:i', strtotime((string) ($report['created_at'] ?? 'now')))) ?></div>
                        <div class="report-thread-preview text-truncate"><?= htmlspecialchars($lastMessageText) ?></div>
                    </div>
                    <span class="report-thread-trailing">
                        <?php if ($hasUnread): ?><span class="report-unread-dot" aria-label="Nouveau message non lu"></span><?php endif; ?>
                        <span class="report-thread-arrow" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
    </div>
    <?php if ($reportConversation): ?>
        <?php include __DIR__ . '/includes/general/report_chat_view.php'; ?>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/general/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/report-chat.js?v=<?= filemtime(__DIR__ . '/js/report-chat.js') ?>"></script>
</body>
</html>
