<?php
include "../general/db.php";

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $pdo->prepare('SELECT * FROM account WHERE email = ?');
$stmt->execute([$email]);
$result = $stmt->fetch();

if ($result && password_verify($password, $result['password'])) {

    if (!$result['email_verified']) {
        header('Location: ../../login.php?alert=' . urlencode('Veuillez vérifier votre email avant de vous connecter.'));
        exit;
    }

    require_once __DIR__ . '/../general/session_start_pwa.php';
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