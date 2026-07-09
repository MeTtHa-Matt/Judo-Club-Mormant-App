<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
header('Content-Type: application/json; charset=utf-8');

$compId = (int) ($_POST['id_competition'] ?? 0);
if (!$compId) {
    echo json_encode(['success' => false, 'message' => 'Missing competition id']);
    exit;
}

$isAdmin = isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1;
$userId = $_SESSION['id'] ?? null;
$onlyMe = isset($_POST['only_me']) && ($_POST['only_me'] == '1' || $_POST['only_me'] === 'true');

if ($onlyMe || !$isAdmin) {
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT i.*, c.ceinture AS ceinture_nom FROM inscrits i LEFT JOIN ceintures c ON i.id_ceinture = c.id WHERE i.id_competition = ? AND i.id_account = ? ORDER BY i.id ASC");
    $stmt->execute([$compId, $userId]);
} else {
    $stmt = $pdo->prepare("SELECT i.*, c.ceinture AS ceinture_nom, a.firstname, a.lastname FROM inscrits i LEFT JOIN ceintures c ON i.id_ceinture = c.id LEFT JOIN account a ON i.id_account = a.id WHERE i.id_competition = ? ORDER BY i.id ASC");
    $stmt->execute([$compId]);
}
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="p-3">
    <?php if (empty($list)): ?>
        <div class="text-center text-muted py-4">
            <i class="bi bi-people-fill display-6 d-block mb-2 text-judo-red"></i>
            <p class="mb-0">Aucun inscrit pour cette compétition.</p>
        </div>
    <?php else: ?>
        <div class="inscrits-list">
            <?php foreach ($list as $row): ?>
                <?php $canUnsubscribe = $isAdmin || ((string) ($row['id_account'] ?? '') === (string) $userId); ?>
                <article class="inscrit-card">
                    <div class="inscrit-card-header">
                        <div>
                            <h6 class="inscrit-card-name">
                                <?= htmlspecialchars($row['prenom'] . ' ' . $row['nom']) ?>
                            </h6>
                            <p class="inscrit-card-meta">
                                Né en <?= htmlspecialchars($row['annee_naissance']) ?>
                            </p>
                        </div>
                        <?php if (!empty($row['ceinture_nom'])): ?>
                            <span class="inscrit-badge">
                                <?= htmlspecialchars($row['ceinture_nom']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="inscrit-card-details">
                        <div class="inscrit-detail-item">
                            <i class="bi bi-weight text-judo-red"></i>
                            <span><?= htmlspecialchars($row['Poids'] ?? '') ?> kg</span>
                        </div>
                        <?php if ($isAdmin && !$onlyMe): ?>
                            <div class="inscrit-detail-item">
                                <i class="bi bi-person-circle text-judo-red"></i>
                                <span><?= htmlspecialchars(trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''))) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($canUnsubscribe): ?>
                        <div class="mt-3">
                            <button type="button" class="btn btn-judo-red btn-sm unsubscribe-inscription-btn"
                                data-inscription-id="<?= (int) $row['id'] ?>" data-competition-id="<?= (int) $compId ?>"
                                data-only-me="<?= $onlyMe ? '1' : '0' ?>">
                                Désinscrire
                            </button>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
$html = ob_get_clean();

echo json_encode(['success' => true, 'html' => $html]);
exit;
