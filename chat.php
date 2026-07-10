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

    <main class="container-fluid pb-5" style="height: 100vh; display: flex; flex-direction: column;">
        <section class="flex-grow-1 d-flex flex-column" style="max-width: 1200px; margin: 0 auto; width: 100%; padding: 2rem 1rem;">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">Assistant IA Llama 3.1</h2>
                    <p class="mb-0 text-muted small">Conversez avec votre assistant d'administration</p>
                </div>
                <a href="admin.php" class="bo-btn bo-btn-outline">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <!-- Chat Container -->
            <div class="d-flex flex-column flex-grow-1 bg-white rounded-3 shadow-sm" style="min-height: 500px; max-height: 70vh; border: 1px solid #e0e0e0;">
                
                <!-- Messages Area -->
                <div id="iaChatMessages" class="flex-grow-1 p-4 overflow-y-auto" style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="d-flex justify-content-center">
                        <div class="bg-light rounded-3 px-4 py-3 text-center" style="max-width: 500px;">
                            <div class="mb-2">
                                <i class="bi bi-database" style="font-size: 2rem; color: #4a90e2;"></i>
                            </div>
                            <p class="mb-0 fw-semibold">Assistant d'Administration</p>
                            <p class="mb-0 small text-muted mt-2">
                                Posez vos questions sur la base de données : comptes, compétitions, inscriptions, 
                                signalements... Les données seront recherchées en temps réel pour vous.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div style="border-top: 1px solid #e0e0e0;"></div>

                <!-- Input Area -->
                <div class="p-4" style="background-color: #f9f9f9;">
                    <form id="iaChatForm" class="d-flex flex-column gap-2">
                        <div class="position-relative">
                            <textarea 
                                id="iaChatInput" 
                                class="form-control" 
                                placeholder="Posez votre question... (Shift+Entrée pour nouvelle ligne, Entrée pour envoyer)"
                                rows="2"
                                style="padding-right: 50px; resize: none; max-height: 120px;"
                            ></textarea>
                            <button 
                                type="submit" 
                                id="iaChatSendBtn"
                                class="btn btn-primary rounded-circle"
                                style="position: absolute; right: 8px; bottom: 8px; width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;"
                                title="Envoyer (Entrée)"
                            >
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span id="iaChatStatus" class="small text-muted"></span>
                            <button type="button" id="iaChatReset" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Nouvelle conversation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <script src="js/chat.js?v=<?php echo filemtime('js/chat.js'); ?>" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>