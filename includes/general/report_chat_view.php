<?php if (!empty($reportConversation)): ?>
    <section class="report-chat card border-0 shadow-sm" id="conversation"
        data-report-id="<?= htmlspecialchars($reportConversation['id']) ?>"
        data-is-admin="<?= $reportIsAdmin ? '1' : '0' ?>">
        <div class="report-chat-header">
            <div>
                <a class="small text-white-50 text-decoration-none" href="<?= htmlspecialchars($reportBackUrl) ?>">
                    <i class="bi bi-arrow-left me-1"></i>Retour aux signalements
                </a>
                <h2 class="h5 text-white fw-bold mb-0 mt-1">Signalement #<?= htmlspecialchars(strtoupper(substr($reportConversation['id'], 0, 8))) ?></h2>
                <div class="small text-white-50"><?= htmlspecialchars($reportConversation['subject'] ?? 'Signalement') ?></div>
            </div>
            <div class="report-chat-header-actions">
                <?php if ($reportIsAdmin): ?>
                    <form method="post" action="includes/general/report_delete.php"
                        onsubmit="return confirm('Supprimer définitivement ce dossier et tous ses messages et images ? Cette action est irréversible.');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(jcm_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="report_id" value="<?= htmlspecialchars($reportConversation['id']) ?>">
                        <button type="submit" class="report-chat-delete" aria-label="Supprimer le dossier de signalement" title="Supprimer le dossier">
                            <i class="bi bi-trash3"></i><span class="d-none d-sm-inline">Supprimer</span>
                        </button>
                    </form>
                <?php else: ?>
                    <i class="bi bi-chat-dots-fill fs-3 text-white-50"></i>
                <?php endif; ?>
            </div>
        </div>
        <div class="report-chat-messages" aria-live="polite">
            <?php foreach ($reportConversation['messages'] as $chatMessage): ?>
                <?php
                $senderIsAdmin = ($chatMessage['sender'] ?? '') === 'admin';
                $isOwnMessage = $senderIsAdmin === $reportIsAdmin;
                $senderLabel = $isOwnMessage ? 'Vous' : ($senderIsAdmin ? 'Administration' : 'Visiteur');
                ?>
                <article class="report-chat-message<?= $isOwnMessage ? ' report-chat-message-own' : '' ?>">
                    <div class="report-chat-sender"><?= htmlspecialchars($senderLabel) ?></div>
                    <?php if (($chatMessage['message'] ?? '') !== ''): ?>
                        <div class="report-chat-text"><?= nl2br(htmlspecialchars((string) $chatMessage['message'])) ?></div>
                    <?php endif; ?>
                    <?php foreach ($chatMessage['attachments'] ?? [] as $attachment): ?>
                        <?php if (preg_match('/^[a-f0-9]{32}\.(?:jpg|png|gif|webp)$/', (string) ($attachment['file'] ?? ''))): ?>
                            <?php $attachmentUrl = 'includes/general/report_attachment.php?report_id=' . rawurlencode($reportConversation['id']) . '&file=' . rawurlencode($attachment['file']); ?>
                            <a class="report-chat-attachment" href="<?= htmlspecialchars($attachmentUrl) ?>" target="_blank" rel="noopener">
                                <img src="<?= htmlspecialchars($attachmentUrl) ?>" alt="<?= htmlspecialchars((string) ($attachment['name'] ?? 'Image jointe')) ?>" loading="lazy">
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <time datetime="<?= htmlspecialchars((string) ($chatMessage['created_at'] ?? '')) ?>">
                        <?= htmlspecialchars(date('d/m/Y à H:i', strtotime((string) ($chatMessage['created_at'] ?? 'now')))) ?>
                    </time>
                </article>
            <?php endforeach; ?>
        </div>
        <form class="report-chat-form" action="includes/general/report_message.php" method="post" enctype="multipart/form-data" data-no-page-loader>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(jcm_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="report_id" value="<?= htmlspecialchars($reportConversation['id']) ?>">
            <label class="report-chat-attach" for="reportChatImages" aria-label="Joindre des images" title="Joindre des images"><i class="bi bi-image"></i></label>
            <input id="reportChatImages" class="visually-hidden report-chat-file-input" type="file" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
            <div class="report-chat-compose">
                <label class="visually-hidden" for="reportChatMessage">Votre message</label>
                <textarea id="reportChatMessage" name="message" rows="1" maxlength="4000" placeholder="Écrire un message…"></textarea>
                <div class="report-chat-selected-files" aria-live="polite"></div>
            </div>
            <button type="submit" aria-label="Envoyer le message"><i class="bi bi-send-fill"></i></button>
            <div class="report-chat-error text-danger small" role="alert"></div>
        </form>
    </section>
<?php endif; ?>
