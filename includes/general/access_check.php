<?php
require_once __DIR__ . '/session_start_pwa.php';

if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}

$currentPage = basename($_SERVER['PHP_SELF']);

$maintenanceActive = (int) $pdo->query('SELECT maintenance FROM account LIMIT 1')->fetchColumn();

if (isset($_SESSION['id'])) {
    $stmt = $pdo->prepare('SELECT ban FROM account WHERE id = ?');
    $stmt->execute([$_SESSION['id']]);
    $ban = (int) $stmt->fetchColumn();

    if ($ban === 1 && $currentPage !== 'ban.php') {
        header('Location: /JCM-App/ban.php');
        exit;
    }
}

$allowedDuringMaintenance = ['login.php', 'register.php', 'verify.php', 'maintenance.php', 'ban.php', 'reglement_accept.php'];
$isAdmin = isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1;

if (
    $maintenanceActive === 1
    && !in_array($currentPage, $allowedDuringMaintenance, true)
    && !$isAdmin
) {
    header('Location: /JCM-App/maintenance.php');
    exit;
}

if (isset($_SESSION['id']) && !isset($_SESSION['reglement_accepte'])) {
    $stmt = $pdo->prepare('SELECT reglement_accepte FROM account WHERE id = ?');
    $stmt->execute([$_SESSION['id']]);
    $_SESSION['reglement_accepte'] = (int) $stmt->fetchColumn();
}

if (
    isset($_SESSION['id'])
    && !in_array($currentPage, $allowedDuringMaintenance, true)
    && $currentPage !== 'reglement_accept.php'
    && !(isset($_SESSION['reglement_accepte']) && (int) $_SESSION['reglement_accepte'] === 1)
    && !$isAdmin
) {
    header('Location: /JCM-App/reglement_accept.php');
    exit;
}
