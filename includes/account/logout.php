<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_database_connection();

$token = $_COOKIE[get_persistent_login_cookie_name()] ?? null;
if (!empty($token) && is_string($token)) {
    clear_persistent_login_token($GLOBALS['pdo'], $token);
}

if (isset($_SESSION['firstname'])) {
    session_unset();
    session_destroy();
    header('Location:../../index.php?info=Vous avez bien été déconnecté');
    exit();
}