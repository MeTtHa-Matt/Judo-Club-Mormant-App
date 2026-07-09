<?php
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . "/includes/general/notifications.php";

$token = $_GET['token'] ?? '';
$status = 'error';
$message = 'Lien de vérification invalide.';
$alertClass = 'danger';

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id, verification_token_expires, email_verified FROM account WHERE verification_token = ?");
    $stmt->execute([$token]);
    $account = $stmt->fetch();

    if (!$account) {
        $message = 'Ce lien est invalide ou déjà utilisé.';
    } elseif ($account['email_verified']) {
        $status = 'success';
        $message = 'Votre adresse email est déjà vérifiée. Vous pouvez vous connecter.';
        $alertClass = 'success';
    } elseif (strtotime($account['verification_token_expires']) < time()) {
        $message = 'Ce lien a expiré. Contactez un administrateur pour renvoyer un email.';
    } else {
        $stmt = $pdo->prepare("UPDATE account SET email_verified = 1, verification_token = NULL, verification_token_expires = NULL WHERE id = ?");
        if ($stmt->execute([$account['id']])) {
            $status = 'success';
            $message = 'Email vérifié avec succès ! Vous pouvez vous connecter.';
            $alertClass = 'success';
        } else {
            $message = 'Une erreur est survenue pendant la vérification. Réessayez plus tard.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="JCM">
    <title>Vérification de l’email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png?v=20260709">
    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png?v=20260709">
    <link rel="apple-touch-icon" sizes="180x180" href="img/jcm.png?v=20260709">
    <link rel="shortcut icon" href="img/jcm.png?v=20260709">
</head>

<body>
    <header>
        <?php include __DIR__ . '/includes/general/navbar.php'; ?>
    </header>

    <main>
        <div class="container d-flex justify-content-center align-items-start"
            style="padding-top: 8.5rem; padding-bottom: 2rem; min-height: 100vh;">
            <div class="card border-0 shadow-lg overflow-hidden judo-card"
                style="max-width: 550px; width: 100%; border-radius: 16px; margin-top: 0;">
                <div class="card-header card-header-judo text-white text-center py-4 border-0">
                    <i class="bi <?= $status === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> fs-1"></i>
                    <h2 class="fw-bold mt-2 mb-0">
                        <?= $status === 'success' ? 'Vérification réussie' : 'Vérification impossible' ?></h2>
                </div>
                <div class="card-body p-4 p-sm-5 bg-white">
                    <div class="alert alert-<?= $alertClass ?> mb-4" role="alert">
                        <?= htmlspecialchars($message) ?>
                    </div>
                    <div class="d-grid">
                        <a href="login.php" class="btn btn-judo-red py-2.5 fw-bold shadow-sm"
                            style="border-radius: 8px;">Retour à la page de connexion</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>