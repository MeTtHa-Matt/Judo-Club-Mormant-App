<?php
require_once __DIR__ . '/session_start_pwa.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/access_check.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/report_storage.php';

function jcm_report_messages_with_attachment_urls(array $messages, string $reportId): array
{
    foreach ($messages as &$message) {
        foreach ($message['attachments'] ?? [] as &$attachment) {
            $attachment['url'] = 'includes/general/report_attachment.php?report_id='
                . rawurlencode($reportId) . '&file=' . rawurlencode((string) ($attachment['file'] ?? ''));
        }
        unset($attachment);
    }
    unset($message);

    return $messages;
}

function jcm_remove_uploaded_report_images(string $reportId, array $images): void
{
    $imageDirectory = jcm_report_directory() . '/' . $reportId . '/images';
    foreach ($images as $image) {
        $path = $imageDirectory . '/' . ($image['file'] ?? '');
        if (is_file($path) && !unlink($path)) {
            error_log('Unable to clean up an unattached report image: ' . basename($path));
        }
    }
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

if ($method === 'POST') {
    jcm_require_csrf();
}

$reportId = strtolower(trim((string) (($method === 'GET' ? $_GET : $_POST)['report_id'] ?? '')));
if (!preg_match('/^[a-f0-9]{24}$/', $reportId)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Identifiant de signalement invalide.']);
    exit;
}

$userId = (int) ($_SESSION['id'] ?? 0);
$adminCheck = $pdo->prepare('SELECT admin FROM account WHERE id = ? LIMIT 1');
$adminCheck->execute([$userId]);
$isAdmin = $userId > 0 && (int) $adminCheck->fetchColumn() === 1;
$verifiedEmail = strtolower(trim((string) ($_SESSION['report_verified_email'] ?? '')));

try {
    $report = jcm_load_report($reportId);
} catch (Throwable $exception) {
    error_log('Report conversation read error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible de charger la conversation.']);
    exit;
}

if ($report === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Signalement introuvable.']);
    exit;
}
if (!$isAdmin && ($verifiedEmail === '' || !hash_equals((string) ($report['email'] ?? ''), $verifiedEmail))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Vous n’êtes pas autorisé à accéder à ce signalement.']);
    exit;
}

if ($method === 'GET') {
    echo json_encode([
        'success' => true,
        'messages' => jcm_report_messages_with_attachment_urls($report['messages'], $reportId),
    ]);
    exit;
}

$message = trim((string) ($_POST['message'] ?? ''));
if (mb_strlen($message) > 4000) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Le message est invalide (4 000 caractères maximum).']);
    exit;
}

$uploadedImages = [];
if (isset($_FILES['images']) && is_array($_FILES['images']['name'] ?? null)) {
    $files = $_FILES['images'];
    $fileCount = count($files['name']);
    if ($fileCount > 5) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Vous pouvez joindre jusqu’à 5 images par message.']);
        exit;
    }

    $mimeExtensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $totalSize = 0;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $validUploads = [];

    foreach ($files['name'] as $index => $originalName) {
        $error = (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($files['tmp_name'][$index] ?? '')) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Une image n’a pas pu être reçue. Réessayez.']);
            exit;
        }

        $size = (int) ($files['size'][$index] ?? 0);
        $totalSize += $size;
        if ($size <= 0 || $size > 5 * 1024 * 1024 || $totalSize > 15 * 1024 * 1024) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Chaque image doit faire moins de 5 Mo, avec 15 Mo maximum par message.']);
            exit;
        }

        $mime = $finfo->file($files['tmp_name'][$index]);
        $imageInfo = getimagesize($files['tmp_name'][$index]);
        if (!is_string($mime) || !isset($mimeExtensions[$mime]) || $imageInfo === false || $imageInfo['mime'] !== $mime
            || $imageInfo[0] > 10000 || $imageInfo[1] > 10000 || $imageInfo[0] * $imageInfo[1] > 40000000) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Image invalide ou trop grande. Formats acceptés : JPEG, PNG, GIF et WebP (40 mégapixels maximum).']);
            exit;
        }

        $validUploads[] = [
            'tmp_name' => $files['tmp_name'][$index],
            'mime' => $mime,
            'extension' => $mimeExtensions[$mime],
            'name' => mb_substr(basename((string) $originalName), 0, 180),
        ];
    }

    $imageDirectory = jcm_report_directory() . '/' . $reportId . '/images';
    if ($validUploads && !is_dir($imageDirectory) && !mkdir($imageDirectory, 0750, true) && !is_dir($imageDirectory)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Impossible de préparer le stockage des images.']);
        exit;
    }

    foreach ($validUploads as $upload) {
        $filename = bin2hex(random_bytes(16)) . '.' . $upload['extension'];
        if (!move_uploaded_file($upload['tmp_name'], $imageDirectory . '/' . $filename)) {
            jcm_remove_uploaded_report_images($reportId, $uploadedImages);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Impossible d’enregistrer une image jointe.']);
            exit;
        }
        $uploadedImages[] = [
            'file' => $filename,
            'mime' => $upload['mime'],
            'name' => $upload['name'],
        ];
    }
}

if ($message === '' && !$uploadedImages) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Écrivez un message ou joignez au moins une image.']);
    exit;
}

try {
    $updatedReport = jcm_update_report($reportId, static function (array $report) use ($isAdmin, $message, $uploadedImages): array {
        $sender = $isAdmin ? 'admin' : 'user';
        $report['messages'][] = [
            'sender' => $sender,
            'message' => $message,
            'created_at' => jcm_report_timestamp(),
            'attachments' => $uploadedImages,
        ];

        return $report;
    });
} catch (Throwable $exception) {
    error_log('Report conversation error: ' . $exception->getMessage());
    jcm_remove_uploaded_report_images($reportId, $uploadedImages);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible d’enregistrer le message. Réessayez.']);
    exit;
}

if ($updatedReport === null) {
    jcm_remove_uploaded_report_images($reportId, $uploadedImages);
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Signalement introuvable.']);
    exit;
}

$lastMessage = end($updatedReport['messages']);
$lastMessage = jcm_report_messages_with_attachment_urls([$lastMessage], $reportId)[0];
echo json_encode(['success' => true, 'message' => $lastMessage]);
