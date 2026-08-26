<?php
include "../general/db.php";
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/security.php';

jcm_require_csrf();

$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if (!jcm_rate_limit('login-ip:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 30, 900)
    || !jcm_rate_limit('login-account:' . strtolower($email), 10, 900)) {
    header('Location: ../../login.php?alert=' . urlencode('Trop de tentatives. Réessayez dans quelques minutes.'));
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM account WHERE email = ?');
$stmt->execute([$email]);
$result = $stmt->fetch();

if ($result && password_verify($password, $result['password'])) {

    if (!$result['email_verified']) {
        header('Location: ../../login.php?alert=' . urlencode('Veuillez vérifier votre email avant de vous connecter.'));
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['id'] = $result['id'];
    $_SESSION["firstname"] = $result['firstname'];
    $_SESSION["lastname"] = $result['lastname'];
    $_SESSION['pdp'] = !empty($result['pdp']) ? basename($result['pdp']) : 'pdp_base.png';
    $_SESSION['admin'] = (int) ($result['admin'] ?? 0);
    $_SESSION['reglement_accepte'] = (int) ($result['reglement_accepte'] ?? 0);

    create_persistent_login_token($pdo, $result['id']);

    $redirect = $_SESSION['reglement_accepte'] === 1 ? '../../index.php?success=' . urlencode('Connexion réussie !') : '../../reglement_accept.php';

    header('Location: ' . $redirect);
    exit;
} else {
    header('Location: ../../login.php?alert=' . urlencode('Mauvais login ou mot de passe !'));
    exit;
}