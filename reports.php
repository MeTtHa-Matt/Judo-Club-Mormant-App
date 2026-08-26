<?php
require_once __DIR__ . '/includes/general/access_check.php';

if (!isset($_SESSION['id']) || (int) ($_SESSION['admin'] ?? 0) !== 1) {
    header('Location: index.php');
    exit;
}

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
<body>
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
<main class="container my-5">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div><span class="text-muted small text-uppercase fw-bold">Boîte de réception</span><h2 class="h3 fw-bold mb-0"><?= count($reports) ?> signalement<?= count($reports) > 1 ? 's' : '' ?></h2></div>
        <span class="badge text-bg-light border px-3 py-2"><?= count($reportThreads) ?> conversation<?= count($reportThreads) > 1 ? 's' : '' ?></span>
    </div>
    <?php if (!$reportThreads): ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-inbox display-4 d-block mb-3"></i><p class="mb-0">Aucun signalement reçu.</p></div>
    <?php else: ?>
        <div class="report-threads">
        <?php foreach ($reportThreads as $email => $thread): ?>
            <section class="report-thread card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 px-4 py-3">
                    <div><i class="bi bi-person-circle text-judo-red me-2"></i><strong><?= htmlspecialchars($email) ?></strong><span class="badge rounded-pill text-bg-light ms-2"><?= count($thread) ?></span></div>
                    <a class="btn btn-sm btn-judo-outline" href="mailto:<?= rawurlencode($email) ?>"><i class="bi bi-reply-fill me-1"></i>Répondre</a>
                </div>
                <div class="card-body px-4 pt-0">
                <?php foreach ($thread as $report): ?>
                    <article class="report-message">
                        <div class="d-flex justify-content-between gap-3 mb-2"><strong><?= htmlspecialchars((string) ($report['subject'] ?? 'Signalement')) ?></strong><time class="small text-muted" datetime="<?= htmlspecialchars((string) ($report['created_at'] ?? '')) ?>"><?= htmlspecialchars(date('d/m/Y à H:i', strtotime((string) ($report['created_at'] ?? 'now')))) ?></time></div>
                        <p class="mb-2 report-message-content"><?= nl2br(htmlspecialchars((string) ($report['message'] ?? ''))) ?></p>
                    </article>
                <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/general/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
