<?php

function jcm_ensure_announcement_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS jcm_announcements (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            message TEXT NOT NULL,
            important TINYINT(1) NOT NULL DEFAULT 0,
            created_by INT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES account(id) ON DELETE SET NULL,
            INDEX idx_jcm_announcements_created (created_at, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS jcm_announcement_images (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            announcement_id INT NOT NULL,
            image_name VARCHAR(80) NOT NULL,
            FOREIGN KEY (announcement_id) REFERENCES jcm_announcements(id) ON DELETE CASCADE,
            INDEX idx_jcm_announcement_images_announcement (announcement_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS jcm_announcement_views (
            announcement_id INT NOT NULL,
            account_id INT NOT NULL,
            seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (announcement_id, account_id),
            FOREIGN KEY (announcement_id) REFERENCES jcm_announcements(id) ON DELETE CASCADE,
            FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE CASCADE,
            INDEX idx_jcm_announcement_views_account (account_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function jcm_announcement_upload_dir(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'announcements';
}

function jcm_announcement_image_url(string $imageName): string
{
    return 'img/announcements/' . rawurlencode(basename($imageName));
}

function jcm_render_announcement_notes(array $announcements, array $unseenIds = [], string $context = 'home'): string
{
    $tilts = [-2.4, 1.8, -1.2, 2.6, 0.9, -2.1, 1.3, -0.7];
    ob_start();
    if ($announcements === []): ?>
        <div class="announcement-wall-empty">
            <i class="bi bi-pin-angle" aria-hidden="true"></i>
            <span>Le tableau est encore vide. Les prochaines nouvelles du club apparaîtront ici.</span>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $index => $announcement):
            $message = (string) $announcement['message'];
            $excerpt = mb_strlen($message) > 125 ? mb_substr($message, 0, 125) . '…' : $message;
            $announcementId = (int) $announcement['id'];
            $isUnseen = in_array($announcementId, $unseenIds, true);
            $createdAt = strtotime((string) $announcement['created_at']);
            ?>
            <button type="button"
                class="announcement-note<?= (int) $announcement['important'] === 1 ? ' announcement-note--important' : '' ?><?= $isUnseen ? ' announcement-note--new' : '' ?>"
                data-announcement-open="<?= $announcementId ?>"
                data-announcement-context="<?= htmlspecialchars($context, ENT_QUOTES, 'UTF-8') ?>"
                style="--note-tilt: <?= $tilts[$index % count($tilts)] ?>deg; --note-depth: <?= max(1, count($announcements) - $index) ?>; --note-delay: <?= min($index, 8) * 90 ?>ms;"
                aria-label="Lire l'annonce du <?= $createdAt ? date('d/m/Y', $createdAt) : '' ?><?= $isUnseen ? ', nouvelle' : '' ?>">
                <span class="announcement-note-pin" aria-hidden="true"></span>
                <?php if ($isUnseen): ?>
                    <span class="announcement-note-new">Nouveau</span>
                <?php endif; ?>
                <?php if ((int) $announcement['important'] === 1): ?>
                    <span class="announcement-note-priority"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i> Important</span>
                <?php endif; ?>
                <span class="announcement-note-date"><?= $createdAt ? date('d/m/Y', $createdAt) : '' ?></span>
                <span class="announcement-note-text"><?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="announcement-note-more">Lire la suite <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
            </button>
        <?php endforeach; ?>
    <?php endif;
    return (string) ob_get_clean();
}
