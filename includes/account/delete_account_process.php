<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
require_once __DIR__ . '/../general/access_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../profile.php');
    exit;
}

if (empty($_SESSION['id'])) {
    header('Location: ../../register.php');
    exit;
}

$password = $_POST['password'] ?? '';

if ($password === '') {
    header('Location: ../../profile.php?alert=' . urlencode('Merci de saisir votre mot de passe pour confirmer.'));
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, password FROM account WHERE id = ?');
    $stmt->execute([$_SESSION['id']]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account || !password_verify($password, $account['password'])) {
        header('Location: ../../profile.php?alert=' . urlencode('Mot de passe incorrect, suppression annulée.'));
        exit;
    }

    $delete = $pdo->prepare('DELETE FROM account WHERE id = ?');
    $delete->execute([$account['id']]);
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header('Location: ../../index.php?success=' . urlencode('Votre compte a bien été supprimé.'));
    exit;
} catch (PDOException $e) {
    error_log('Erreur BDD suppression compte : ' . $e->getMessage());
    header('Location: ../../profile.php?alert=' . urlencode('Une erreur est survenue, merci de réessayer.'));
    exit;
}