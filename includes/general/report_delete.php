<?php
require_once __DIR__ . '/access_check.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/report_storage.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

jcm_require_admin($pdo);
jcm_require_csrf();

$reportId = strtolower(trim((string) ($_POST['report_id'] ?? '')));
try {
    if (!jcm_delete_report($reportId)) {
        header('Location: ../../reports.php?alert=' . rawurlencode('Ce signalement est introuvable.'));
        exit;
    }
} catch (Throwable $exception) {
    error_log('Report deletion error: ' . $exception->getMessage());
    header('Location: ../../reports.php?alert=' . rawurlencode('Impossible de supprimer cette conversation. Réessayez.'));
    exit;
}

header('Location: ../../reports.php?success=' . rawurlencode('La conversation de signalement a été supprimée.'));
exit;
