<?php
require_once __DIR__ . '/session_start_pwa.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/access_check.php';
require_once __DIR__ . '/security.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

jcm_require_csrf();

$input = json_decode(file_get_contents('php://input'), true);
$acceptEmail = isset($input['accept_email']) ? (int) $input['accept_email'] : null;

if ($acceptEmail !== 0 && $acceptEmail !== 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valeur invalide.']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE account SET accept_email = ? WHERE id = ?');
    $stmt->execute([$acceptEmail, $_SESSION['id']]);

    echo json_encode(['success' => true, 'accept_email' => $acceptEmail]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour.']);
}