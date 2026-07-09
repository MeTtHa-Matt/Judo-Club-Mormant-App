<?php

$jcmIconMap = [
    'success' => 'bi-check-circle-fill',
    'alert' => 'bi-exclamation-triangle-fill',
    'info' => 'bi-info-circle-fill',
];

$jcmLabelMap = [
    'success' => 'Succès',
    'alert' => 'Attention',
    'info' => 'Information',
];

$jcmNotifications = [];
$jcmCustomTitle = isset($_GET['notif_title']) ? trim((string) $_GET['notif_title']) : null;

foreach (['success', 'alert', 'info'] as $jcmType) {
    if (isset($_GET[$jcmType]) && trim((string) $_GET[$jcmType]) !== '') {
        $jcmNotifications[] = [
            'type' => $jcmType,
            'message' => (string) $_GET[$jcmType],
            'title' => $jcmCustomTitle ?: $jcmLabelMap[$jcmType],
        ];
    }
}
?>
<?php if (!headers_sent()): ?>
    <div id="jcm-toast-container" class="jcm-toast-container" aria-live="polite" aria-atomic="true">
        <?php foreach ($jcmNotifications as $notif): ?>
            <?php $icon = $jcmIconMap[$notif['type']]; ?>
            <div class="jcm-toast jcm-toast-<?= htmlspecialchars($notif['type']) ?>" role="status" data-autohide="6000">
                <div class="jcm-toast-icon"><i class="bi <?= htmlspecialchars($icon) ?>"></i></div>
                <div class="jcm-toast-body">
                    <p class="jcm-toast-title"><?= htmlspecialchars($notif['title']) ?></p>
                    <p class="jcm-toast-message"><?= htmlspecialchars($notif['message']) ?></p>
                </div>
                <button type="button" class="jcm-toast-close" aria-label="Fermer la notification">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="jcm-toast-progress"></div>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="js/notifications.js?v=<?php echo @filemtime(__DIR__ . '/../../js/notifications.js') ?: time(); ?>"
        defer></script>
<?php endif; ?>