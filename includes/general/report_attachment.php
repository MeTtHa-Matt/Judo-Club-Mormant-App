<?php
require_once __DIR__ . '/session_start_pwa.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/access_check.php';
require_once __DIR__ . '/report_storage.php';

$reportId = strtolower(trim((string) ($_GET['report_id'] ?? '')));
$filename = (string) ($_GET['file'] ?? '');
if (!preg_match('/^[a-f0-9]{24}$/', $reportId)
    || !preg_match('/^[a-f0-9]{32}\.(?:jpg|png|gif|webp)$/', $filename)) {
    http_response_code(404);
    exit;
}

$userId = (int) ($_SESSION['id'] ?? 0);
$adminCheck = $pdo->prepare('SELECT admin FROM account WHERE id = ? LIMIT 1');
$adminCheck->execute([$userId]);
$isAdmin = $userId > 0 && (int) $adminCheck->fetchColumn() === 1;
$report = jcm_load_report($reportId);
$verifiedEmail = strtolower(trim((string) ($_SESSION['report_verified_email'] ?? '')));
if ($report === null
    || (!$isAdmin && ($verifiedEmail === '' || !hash_equals((string) ($report['email'] ?? ''), $verifiedEmail)))) {
    http_response_code(403);
    exit;
}

$attachmentFound = false;
foreach ($report['messages'] as $message) {
    foreach ($message['attachments'] ?? [] as $attachment) {
        if (($attachment['file'] ?? '') === $filename) {
            $attachmentFound = true;
            break 2;
        }
    }
}

$path = jcm_report_directory() . '/' . $reportId . '/images/' . $filename;
if (!$attachmentFound || !is_file($path)) {
    http_response_code(404);
    exit;
}

$imageInfo = getimagesize($path);
$mime = is_array($imageInfo) ? ($imageInfo['mime'] ?? '') : '';
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="image.' . pathinfo($filename, PATHINFO_EXTENSION) . '"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
readfile($path);
