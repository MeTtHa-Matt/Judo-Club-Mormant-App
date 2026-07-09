<?php
include "../general/db.php";
include "../general/mailer.php";

$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$email = $_POST['email'];
$password = $_POST['password'];
$accept_email = $_POST['accept_email_toggle'] == 1 ? 1 : 0;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
    $extensionImage = pathinfo($_FILES['pdp']['name'], PATHINFO_EXTENSION);
    $nouveauNom = uniqid() . "." . $extensionImage;
    $cheminImage = "../../img/pdps/" . $nouveauNom;

    if (move_uploaded_file($_FILES['pdp']['tmp_name'], $cheminImage)) {
        $stmt = $pdo->prepare("UPDATE account SET pdp = ? WHERE email = ?");
        $stmt->execute([$nouveauNom, $email]);
    }
}

$emailResult = sendVerificationEmail($email, $firstname, $token);

if ($emailResult['success']) {
    header('Location: ../../login.php?success=' . urlencode('Compte créé ! Vérifiez votre boîte mail pour l\'activer.'));
} else {
    error_log('sendVerificationEmail failed : ' . $emailResult['error']);
    header('Location: ../../login.php?alert=' . urlencode('Compte créé mais l\'email de vérification n\'a pas pu être envoyé. Contactez un administrateur.'));
}
exit();