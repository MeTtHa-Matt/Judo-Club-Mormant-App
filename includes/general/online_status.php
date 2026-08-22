<?php
require_once __DIR__ . '/session_start_pwa.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$userId = isset($_SESSION['id']) ? (int) $_SESSION['id'] : null;

// Debug logging to help diagnose session/auth issues (temporary)
$logLine = sprintf("%s | %s | REMOTE=%s | HTTP_HOST=%s | PHPSESSID=%s | session_id=%s | userId=%s\n",
    date('c'),
    $_SERVER['REQUEST_METHOD'] ?? '-',
    $_SERVER['REMOTE_ADDR'] ?? '-',
    $_SERVER['HTTP_HOST'] ?? '-',
    $_COOKIE[session_name()] ?? '-',
    session_id() ?? '-',
    $userId === null ? 'NULL' : (string)$userId
);
@file_put_contents(sys_get_temp_dir() . '/jcm_online_debug.log', $logLine, FILE_APPEND);

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
