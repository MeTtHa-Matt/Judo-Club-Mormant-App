<?php
require_once __DIR__ . '/session_start_pwa.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$userId = isset($_SESSION['id']) ? (int) $_SESSION['id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    // Update last_activity on account row
    $stmt = $pdo->prepare("UPDATE account SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true]);
    exit;
}

// If GET -> return list of online user ids (active within last 5 minutes)
$stmt = $pdo->prepare("SELECT id FROM account WHERE last_activity >= (NOW() - INTERVAL 5 MINUTE)");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
echo json_encode(['success' => true, 'online' => array_map('intval', $rows)]);
exit;

?>
