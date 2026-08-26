<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
    exit;
}

jcm_require_csrf();
require_database_connection();

$token = $_COOKIE[get_persistent_login_cookie_name()] ?? null;
if (!empty($token) && is_string($token)) {
    clear_persistent_login_token($GLOBALS['pdo'], $token);
}

if (isset($_SESSION['firstname'])) {
    // mark user offline by clearing last_activity
    if (isset($_SESSION['id'])) {
        try {
            $pdo->prepare('UPDATE account SET last_activity = NULL WHERE id = ?')->execute([(int) $_SESSION['id']]);
        } catch (Exception $e) {
            // ignore DB errors
        }
    }
    session_unset();
    session_destroy();
    header('Location:../../index.php?info=Vous avez bien été déconnecté');
    exit();
}