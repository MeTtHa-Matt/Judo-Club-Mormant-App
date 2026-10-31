<?php
require_once __DIR__ . '/access_check.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/announcements.php';

function jcm_redirect_announcement_admin(?string $success, ?string $error): void
{
    if ($success !== null) {
        $_SESSION['announcement_flash_success'] = $success;
    }
    if ($error !== null) {
        $_SESSION['announcement_flash_error'] = $error;
    }
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Méthode non autorisée.');
}

jcm_require_csrf();
$action = $_POST['action'] ?? '';

if ($action === 'mark_seen') {
    $userId = (int) ($_SESSION['id'] ?? 0);
    $accountStmt = $pdo->prepare('SELECT id FROM account WHERE id = ? LIMIT 1');
    $accountStmt->execute([$userId]);
    if ($userId <= 0 || !$accountStmt->fetchColumn()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['success' => false, 'message' => 'Connexion requise.']));
    }
    jcm_ensure_announcement_schema($pdo);
    header('Content-Type: application/json; charset=utf-8');
    $announcementIds = $_POST['announcement_ids'] ?? [];
    if (!is_array($announcementIds)) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Liste d’annonces invalide.']));
    }
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO jcm_announcement_views (announcement_id, account_id)
         SELECT id, ? FROM jcm_announcements WHERE id = ?'
    );
    foreach ($announcementIds as $announcementId) {
        if (!is_string($announcementId) && !is_int($announcementId)) {
            continue;
        }
        $validatedId = filter_var($announcementId, FILTER_VALIDATE_INT);
        if ($validatedId !== false && $validatedId > 0) {
            $stmt->execute([$userId, $validatedId]);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete') {
    jcm_require_admin($pdo);
    header('Content-Type: application/json; charset=utf-8');
    $announcementId = filter_var($_POST['announcement_id'] ?? null, FILTER_VALIDATE_INT);
    if ($announcementId === false || $announcementId === null || $announcementId <= 0) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Annonce invalide.']));
    }

    jcm_ensure_announcement_schema($pdo);
    $uploadDir = jcm_announcement_upload_dir();
    try {
        $pdo->beginTransaction();
        $announcementStmt = $pdo->prepare('SELECT id FROM jcm_announcements WHERE id = ? FOR UPDATE');
        $announcementStmt->execute([$announcementId]);
        if (!$announcementStmt->fetchColumn()) {
            $pdo->rollBack();
            http_response_code(404);
            exit(json_encode(['success' => false, 'message' => 'Cette annonce n’existe plus.']));
        }

        $imagesStmt = $pdo->prepare('SELECT image_name FROM jcm_announcement_images WHERE announcement_id = ?');
        $imagesStmt->execute([$announcementId]);
        $imageNames = $imagesStmt->fetchAll(PDO::FETCH_COLUMN);

        $deleteStmt = $pdo->prepare('DELETE FROM jcm_announcements WHERE id = ?');
        $deleteStmt->execute([$announcementId]);
        $pdo->commit();

        foreach ($imageNames as $imageName) {
            $imagePath = $uploadDir . DIRECTORY_SEPARATOR . basename((string) $imageName);
            if (is_file($imagePath) && !unlink($imagePath)) {
                error_log('Impossible de supprimer une image d’annonce : ' . $imagePath);
            }
        }

        echo json_encode(['success' => true]);
        exit;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Erreur de suppression d’annonce : ' . $exception->getMessage());
        http_response_code(500);
        exit(json_encode(['success' => false, 'message' => 'L’annonce n’a pas pu être supprimée. Réessayez.']));
    }
}

if ($action === 'create') {
    $userId = jcm_require_admin($pdo);
    jcm_ensure_announcement_schema($pdo);
} else {
    jcm_redirect_announcement_admin(null, 'Action inconnue.');
}

$message = trim((string) ($_POST['message'] ?? ''));
$important = isset($_POST['important']) && $_POST['important'] === '1' ? 1 : 0;
if ($message === '' || mb_strlen($message) > 10000) {
    jcm_redirect_announcement_admin(null, 'Le texte de l’annonce doit contenir entre 1 et 10 000 caractères.');
}

$files = [];
if (isset($_FILES['images']) && is_array($_FILES['images']['name'] ?? null)) {
    $imageCount = count($_FILES['images']['name']);
    if ($imageCount > 5) {
        jcm_redirect_announcement_admin(null, 'Vous pouvez joindre au maximum 5 images.');
    }

    $totalFileSize = 0;
    $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    for ($index = 0; $index < $imageCount; $index++) {
        $errorCode = (int) ($_FILES['images']['error'][$index] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($errorCode !== UPLOAD_ERR_OK) {
            jcm_redirect_announcement_admin(null, 'Une image n’a pas pu être téléversée. Vérifiez sa taille et réessayez.');
        }

        $temporaryPath = (string) ($_FILES['images']['tmp_name'][$index] ?? '');
        $fileSize = (int) ($_FILES['images']['size'][$index] ?? 0);
        $totalFileSize += $fileSize;
        if (
            !is_uploaded_file($temporaryPath)
            || $fileSize < 1
            || $fileSize > 1 * 1024 * 1024
            || $totalFileSize > 5 * 1024 * 1024
        ) {
            jcm_redirect_announcement_admin(null, 'Formats acceptés : JPEG, PNG ou WebP, jusqu’à 1 Mo par image et 5 Mo au total.');
        }
        $mime = $temporaryPath !== '' ? $fileInfo->file($temporaryPath) : false;
        $dimensions = $temporaryPath !== '' ? @getimagesize($temporaryPath) : false;
        if (
            !is_string($mime)
            || !isset($allowedMimes[$mime])
            || $dimensions === false
        ) {
            jcm_redirect_announcement_admin(null, 'Formats acceptés : JPEG, PNG ou WebP, jusqu’à 1 Mo par image.');
        }

        $files[] = ['temporary_path' => $temporaryPath, 'extension' => $allowedMimes[$mime]];
    }
}

$uploadDir = jcm_announcement_upload_dir();
$storedFiles = [];
try {
    if ($files !== [] && !is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Impossible de créer le dossier des images d’annonces.');
    }

    foreach ($files as $file) {
        $filename = 'ann_' . bin2hex(random_bytes(16)) . '.' . $file['extension'];
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file['temporary_path'], $destination)) {
            throw new RuntimeException('Impossible d’enregistrer une image d’annonce.');
        }
        $storedFiles[] = $filename;
    }

    $pdo->beginTransaction();
    $insert = $pdo->prepare('INSERT INTO jcm_announcements (message, important, created_by) VALUES (?, ?, ?)');
    $insert->execute([$message, $important, $userId]);
    $announcementId = (int) $pdo->lastInsertId();

    if ($storedFiles !== []) {
        $insertImage = $pdo->prepare('INSERT INTO jcm_announcement_images (announcement_id, image_name) VALUES (?, ?)');
        foreach ($storedFiles as $filename) {
            $insertImage->execute([$announcementId, $filename]);
        }
    }
    $pdo->commit();
    jcm_redirect_announcement_admin('L’annonce a été publiée.', null);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    foreach ($storedFiles as $filename) {
        $path = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path) && !unlink($path)) {
            error_log('Impossible de supprimer une image d’annonce après échec : ' . $path);
        }
    }
    error_log('Erreur de publication d’annonce : ' . $exception->getMessage());
    jcm_redirect_announcement_admin(null, 'L’annonce n’a pas pu être enregistrée. Réessayez ou contactez un administrateur.');
}
