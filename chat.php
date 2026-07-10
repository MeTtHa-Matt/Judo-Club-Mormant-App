<?php
require_once __DIR__ . '/includes/general/session_start_pwa.php';
require_once __DIR__ . '/includes/general/db.php';
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . '/includes/general/notifications.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['id'];
$stmt = $pdo->prepare('SELECT admin FROM account WHERE id = ?');
$stmt->execute([$userId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$me || (int) $me['admin'] !== 1) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="JCM">
    <title>Assistant IA - Administration JCM</title>
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
</head>

<body class="bo-page mt-5 pt-5">
    <?php include __DIR__ . '/includes/general/navbar.php'; ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-robot-fill me-2"></i>Assistant IA</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Assistant IA</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Un assistant conversationnel pour les administrateurs,
                connecté à la base de données du site et capable de traduire les noms de colonnes de l'anglais vers
                le français.</p>
        </div>
    </header>

    <main class="container pb-5">
        <section class="mx-auto" style="max-width: 1024px;">
            <div class="bo-toolbar mt-5 mb-3 justify-content-between align-items-start">
                <div>
                    <h2>Conversation IA</h2>
                    <p class="mb-0 text-muted">Posez plusieurs questions et échangez avec l'assistant sans perdre le
                        contexte.</p>
                </div>
                <a href="admin.php" class="bo-btn bo-btn-outline mt-3 mt-md-0">
                    <i class="bi bi-arrow-left"></i> Retour à l'administration
                </a>
            </div>

            <div class="bo-card">
                <div class="bo-card-head">
                    <h3>Chat IA</h3>
                </div>
                <div class="bo-card-body">
                    <div class="ia-chat-shell">
                        <div id="iaChatMessages" class="ia-chat-messages">
                            <div class="ia-chat-message ia-chat-message-system">Bonjour administrateur, je suis votre
                                assistant IA. Posez une question sur les comptes, les compétitions, les inscriptions,
                                les signalements ou toute autre donnée du site.</div>
                        </div>

                        <form id="iaChatForm" class="ia-chat-form">
                            <div class="bo-field">
                                <label for="iaChatInput">Votre question</label>
                                <textarea id="iaChatInput" class="form-control ia-chat-input" rows="4"
                                    placeholder="Ex : Combien de comptes sont encore non vérifiés ?"
                                    required></textarea>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-stretch gap-2">
                                <button type="submit" class="bo-btn bo-btn-primary flex-grow-1">Envoyer</button>
                                <button type="button" id="iaChatReset" class="bo-btn bo-btn-outline">Réinitialiser
                                    la conversation</button>
                            </div>
                            <div class="mt-3">
                                <span id="iaChatStatus" class="ia-chat-status"></span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="js/chat.js?v=<?php echo filemtime('js/chat.js'); ?>" defer></script>
</body>

</html>