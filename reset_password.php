<?php
require_once __DIR__ . "/includes/general/session_start_pwa.php";
require_once __DIR__ . "/includes/general/db.php";
require_once __DIR__ . "/includes/general/access_check.php";
require_once __DIR__ . "/includes/general/notifications.php";

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$tokenValid = false;

if ($token !== '') {
    $tokenHash = hash('sha256', $token);

    try {
        $stmt = $pdo->prepare('SELECT id, reset_token_expires FROM account WHERE reset_token = ?');
        $stmt->execute([$tokenHash]);
        $tokenAccount = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tokenAccount && strtotime($tokenAccount['reset_token_expires']) >= time()) {
            $tokenValid = true;
        }
    } catch (PDOException $e) {
        error_log('Erreur BDD vérification token reset : ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (!$tokenValid) {
        header('Location: recup_mdp.php?alert=' . urlencode('Ce lien a expiré ou est invalide, merci de refaire une demande.'));
        exit;
    }

    if (strlen($password) < 8) {
        header('Location: reset_password.php?token=' . urlencode($token) . '&alert=' . urlencode('Le mot de passe doit contenir au moins 8 caractères.'));
        exit;
    }

    if ($password !== $passwordConfirm) {
        header('Location: reset_password.php?token=' . urlencode($token) . '&alert=' . urlencode('Les deux mots de passe ne correspondent pas.'));
        exit;
    }

    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $update = $pdo->prepare('UPDATE account SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?');
        $update->execute([$hashedPassword, $tokenAccount['id']]);

        header('Location: register.php?success=' . urlencode('Votre mot de passe a été mis à jour, vous pouvez vous connecter.'));
        exit;
    } catch (PDOException $e) {
        error_log('Erreur BDD mise à jour mot de passe : ' . $e->getMessage());
        header('Location: reset_password.php?token=' . urlencode($token) . '&alert=' . urlencode('Une erreur est survenue, merci de réessayer.'));
        exit;
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
    <title>Réinitialiser mon mot de passe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png">
    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png">
    <link rel="shortcut icon" href="img/jcm.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" href="img/jcm.png">
</head>

<body>
    <header>
        <?php include __DIR__ . "/includes/general/navbar.php" ?>
    </header>

    <div class="container min-vh-100 d-flex justify-content-center align-items-center">
        <div class="card border-0 shadow-lg overflow-hidden judo-card"
            style="max-width: 550px; width: 100%; border-radius: 16px;">

            <div class="card-header card-header-judo text-white text-center py-4 border-0">
                <h2 class="fw-bold mt-2 mb-0">Nouveau mot de passe</h2>
            </div>

            <div class="card-body p-4 p-sm-5 bg-white">
                <?php if (!$tokenValid): ?>
                    <div class="bo-alert" style="background:#f8d7da;color:#842029;border-color:#f5c2c7;">
                        <i class="bi bi-x-octagon-fill"></i>
                        Ce lien de réinitialisation est invalide ou a expiré.
                    </div>
                    <div class="text-center mt-4">
                        <a href="recup_mdp.php" class="btn btn-judo-red" style="border-radius: 8px;">
                            Faire une nouvelle demande
                        </a>
                    </div>
                <?php else: ?>
                    <form action="reset_password.php" method="POST">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold text-dark">Nouveau mot de
                                passe</label>
                            <div class="input-group custom-judo-input">
                                <span class="input-group-text bg-light text-secondary border-end-0"><i
                                        class="bi bi-lock"></i></span>
                                <input type="password" class="form-control bg-light border-start-0 ps-0" id="password"
                                    name="password" minlength="8" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirm" class="form-label small fw-semibold text-dark">Confirmer le mot de
                                passe</label>
                            <div class="input-group custom-judo-input">
                                <span class="input-group-text bg-light text-secondary border-end-0"><i
                                        class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control bg-light border-start-0 ps-0"
                                    id="password_confirm" name="password_confirm" minlength="8" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-judo-red w-100 py-2.5 fw-bold shadow-sm"
                            style="border-radius: 8px;">
                            Mettre à jour mon mot de passe
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>

