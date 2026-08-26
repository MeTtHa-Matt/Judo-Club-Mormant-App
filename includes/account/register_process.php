<?php
include "../general/db.php";
include "../general/mailer.php";
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/security.php';

jcm_require_csrf();

if (!jcm_rate_limit('register-ip:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 5, 3600)) {
    header('Location: ../../register.php?alert=' . urlencode('Trop de créations de compte. Réessayez plus tard.'));
    exit;
}

cleanupExpiredUnverifiedAccounts($pdo);

$firstname = trim((string) ($_POST['firstname'] ?? ''));
$lastname = trim((string) ($_POST['lastname'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$accept_email = ($_POST['accept_email_toggle'] ?? '') === '1' ? 1 : 0;

if (!jcm_valid_person_name($firstname) || !jcm_valid_person_name($lastname)) {
    header('Location: ../../register.php?alert=' . urlencode('Nom ou prénom invalide.'));
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || strlen($password) < 8 || strlen($password) > 128) {
    header('Location: ../../register.php?alert=' . urlencode('Adresse email invalide.'));
    exit();
}

$stmt = $pdo->prepare('SELECT email FROM account WHERE email = ?');
$stmt->execute([$email]);
$result = $stmt->fetch();

if ($result) {
    header('Location: ../../register.php?alert=' . urlencode('Ce mail est déjà utilisé !'));
    exit;
}

$password = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

$stmt = $pdo->prepare("INSERT INTO account (firstname, lastname, email, password, verification_token, verification_token_expires, accept_email) VALUES (?,?,?,?,?,?,?)");
$stmt->execute([$firstname, $lastname, $email, $password, $token, $expires, $accept_email]);

if (isset($_FILES['pdp']) && $_FILES['pdp']['error'] === UPLOAD_ERR_OK) {
    $tmpPath = $_FILES['pdp']['tmp_name'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpPath);
    $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    $imageInfo = @getimagesize($tmpPath);
    if ($_FILES['pdp']['size'] <= 3 * 1024 * 1024 && isset($allowedMimes[$mime])
        && $imageInfo !== false && $imageInfo[0] <= 4000 && $imageInfo[1] <= 4000) {
        $nouveauNom = 'avatar_' . bin2hex(random_bytes(16)) . '.' . $allowedMimes[$mime];
        $cheminImage = __DIR__ . '/../../img/pdps/' . $nouveauNom;

        if (move_uploaded_file($tmpPath, $cheminImage)) {
            $stmt = $pdo->prepare("UPDATE account SET pdp = ? WHERE email = ?");
            $stmt->execute([$nouveauNom, $email]);
        }
    }
}

$emailResult = sendVerificationEmail($email, $firstname, $token);

if ($emailResult['success']) {
    header('Location: ../../login.php?success=' . urlencode('Compte créé ! Vérifiez votre boîte mail pour l\'activer. (le mail peut se trouver dans vos spams, pensez a les vérifier ! Le mail peut avoir un peu de délai, ne vous inquiétez pas !) Si vous utilisez une boite mail Microsoft (outlook, hotmail, etc...), le mail aura très certainement du délai. Si vous ne l\'avez pas reçu dans les 1h qui suivent, utilisez un autre mail.'));
} else {
    error_log('sendVerificationEmail failed : ' . $emailResult['error']);
    header('Location: ../../login.php?alert=' . urlencode('Compte créé mais l\'email de vérification n\'a pas pu être envoyé. Contactez un administrateur.'));
}
exit();