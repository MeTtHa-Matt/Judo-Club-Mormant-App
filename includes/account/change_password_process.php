<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
require_once __DIR__ . '/../general/access_check.php';
require_once __DIR__ . '/../general/mailer.php';
require_once __DIR__ . '/../general/security.php';
jcm_require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../forgot_password.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!jcm_rate_limit('reset-ip:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 5, 3600)
    || !jcm_rate_limit('reset-account:' . strtolower($email), 3, 3600)) {
    header('Location: ../../recup_mdp.php?alert=' . urlencode('Trop de demandes. Réessayez plus tard.'));
    exit;
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../forgot_password.php?alert=' . urlencode('Merci de saisir une adresse email valide.'));
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, firstname, email FROM account WHERE email = ?');
    $stmt->execute([$email]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($account) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // valable 1h

        $update = $pdo->prepare('UPDATE account SET reset_token = ?, reset_token_expires = ? WHERE id = ?');
        $update->execute([$tokenHash, $expiresAt, $account['id']]);

        $result = sendPasswordResetEmail($account['email'], $account['firstname'], $token);

        if (!$result['success']) {
            error_log('Echec envoi mail reset password pour ' . $email . ' : ' . ($result['error'] ?? 'inconnu'));
        }
    }
} catch (PDOException $e) {
    error_log('Erreur BDD reset password : ' . $e->getMessage());
}

header('Location: ../../recup_mdp.php?success=' . urlencode('Si un compte existe avec cette adresse, un email de réinitialisation vient de vous être envoyé.'));
exit;