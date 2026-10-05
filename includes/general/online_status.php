<?php
require_once __DIR__ . '/session_start_pwa.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

header('Content-Type: application/json; charset=utf-8');

$userId = isset($_SESSION['id']) ? (int) $_SESSION['id'] : null;

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    jcm_require_csrf();

    $requestedPath = (string) ($_POST['page'] ?? '');
    $pagePath = parse_url($requestedPath, PHP_URL_PATH);
    $page = basename(is_string($pagePath) ? rtrim($pagePath, '/') : '');
    if ($requestedPath !== '' && $page === '') {
        $page = 'index.php';
    }
    if ($page !== '' && preg_match('/^[A-Za-z0-9._-]{1,120}$/', $page)) {
        try {
            $stmt = $pdo->prepare('UPDATE account SET last_activity = NOW(), last_page_path = ?, last_page_at = NOW() WHERE id = ?');
            $stmt->execute(['/' . $page, $userId]);
        } catch (PDOException $exception) {
            $stmt = $pdo->prepare('UPDATE account SET last_activity = NOW() WHERE id = ?');
            $stmt->execute([$userId]);
        }
    } else {
        $stmt = $pdo->prepare('UPDATE account SET last_activity = NOW() WHERE id = ?');
        $stmt->execute([$userId]);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// If GET -> return list of online user ids (active within last 5 minutes)
$stmt = $pdo->prepare("SELECT id FROM account WHERE last_activity >= (NOW() - INTERVAL 5 MINUTE)");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
echo json_encode(['success' => true, 'online' => array_map('intval', $rows)]);
exit;

?>
