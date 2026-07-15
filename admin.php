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

function fetchCount(PDO $pdo, string $query)
{
    return (int) $pdo->query($query)->fetchColumn();
}

$totalMembers = fetchCount($pdo, 'SELECT COUNT(*) FROM account');
$totalAdmins = fetchCount($pdo, 'SELECT COUNT(*) FROM account WHERE admin = 1');
$totalBanned = fetchCount($pdo, 'SELECT COUNT(*) FROM account WHERE ban = 1');
$totalCompetitions = fetchCount($pdo, 'SELECT COUNT(*) FROM competitions');
$totalRegistrations = fetchCount($pdo, 'SELECT COUNT(*) FROM inscrits');
$totalChildren = fetchCount($pdo, 'SELECT COUNT(*) FROM child_profiles');
$totalReports = fetchCount($pdo, 'SELECT COUNT(*) FROM signalements_jcm');
$totalLinks = fetchCount($pdo, 'SELECT COUNT(*) FROM index_links_jcm');
$acceptedEmails = fetchCount($pdo, 'SELECT COUNT(*) FROM account WHERE accept_email = 1');
$notVerified = fetchCount($pdo, 'SELECT COUNT(*) FROM account WHERE email_verified = 0');
$upcomingCompetitions = fetchCount($pdo, 'SELECT COUNT(*) FROM competitions WHERE date >= CURDATE()');
$maintenanceOn = (bool) fetchCount($pdo, 'SELECT maintenance FROM account LIMIT 1');

$recentReports = $pdo->query(
    'SELECT s.subject, a.firstname, a.lastname, s.created_at
     FROM signalements_jcm s
     JOIN account a ON s.account_id = a.id
     ORDER BY s.created_at DESC
     LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Administration JCM</title>
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
                <span class="badge-text"><i class="bi bi-shield-lock-fill me-2"></i>Back-office</span>
            </div>
            <h1 class="display-5 fw-bolder text-uppercase tracking-wider hero-title">Administration</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-6 fs-md-5 fw-light mt-3 hero-subtitle px-2">
                Accédez aux actions administratives et aux statistiques clés du site.
            </p>
        </div>
    </header>

    <main class="container pb-5">
        <div class="bo-admin-shell">

            <!-- Sur mobile : le menu apparaît APRÈS le tableau de bord (order-2).
                 À partir de 992px : il reprend sa place naturelle à gauche (order-lg-1). -->
            <aside class="bo-sidebar mt-4 mt-lg-5 order-2 order-lg-1">
                <div class="bo-sidebar-head">
                    <h2 class="bo-sidebar-title">Menu admin</h2>
                    <button class="bo-sidebar-toggle" type="button" data-bs-toggle="collapse"
                        data-bs-target="#sidebarLinks" aria-expanded="true" aria-controls="sidebarLinks">
                        <i class="bi bi-list"></i>
                    </button>
                </div>

                <div class="bo-sidebar-nav collapse show" id="sidebarLinks">
                    <a href="admin.php" class="bo-sidebar-link active" aria-current="page">
                        <span>Tableau de bord</span>
                        <i class="bi bi-speedometer2"></i>
                    </a>
                    <a href="users.php" class="bo-sidebar-link">
                        <span>Utilisateurs</span>
                        <i class="bi bi-people-fill"></i>
                    </a>
                    <a href="mailing.php" class="bo-sidebar-link">
                        <span>Envoyer un mail</span>
                        <i class="bi bi-envelope-fill"></i>
                    </a>
                    <a href="gerer_competitions.php" class="bo-sidebar-link">
                        <span>Compétitions</span>
                        <i class="bi bi-trophy-fill"></i>
                    </a>
                    <a href="gerer_index_liens.php" class="bo-sidebar-link">
                        <span>Liens d'accueil</span>
                        <i class="bi bi-link-45deg"></i>
                    </a>
                    <a href="chat.php" class="bo-sidebar-link">
                        <span>Assistant IA</span>
                        <i class="bi bi-robot"></i>
                    </a>
                </div>

                <div class="bo-sidebar-section">
                    <button class="bo-sidebar-section-toggle collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#sidebarExtras" aria-expanded="false" aria-controls="sidebarExtras">
                        Autres pages
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="bo-sidebar-links collapse" id="sidebarExtras">
                        <a href="profile.php"><i class="bi bi-person-circle me-2"></i>Mon profil</a>
                        <a href="signaler.php"><i class="bi bi-flag-fill me-2"></i>Signaler un problème</a>
                        <a href="index.php"><i class="bi bi-house-fill me-2"></i>Retour au site</a>
                    </div>
                </div>
            </aside>

            <section class="order-1 order-lg-2">
                <div class="bo-toolbar">
                    <h2>Tableau de bord</h2>
                    <a href="index.php" class="bo-btn bo-btn-outline">
                        <i class="bi bi-arrow-left"></i> Retour au site
                    </a>
                </div>

                <?php if ($maintenanceOn): ?>
                    <div class="bo-alert bo-alert-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Le site est actuellement en mode maintenance.
                    </div>
                <?php endif; ?>

                <div class="bo-stat-grid">
                    <div class="bo-stat-card">
                        <div class="bo-stat-icon"><i class="bi bi-people-fill"></i></div>
                        <h3>Comptes</h3>
                        <p>Membres inscrits</p>
                        <p class="bo-stat-number"><?= $totalMembers ?></p>
                    </div>
                    <div class="bo-stat-card">
                        <div class="bo-stat-icon"><i class="bi bi-shield-fill-check"></i></div>
                        <h3>Administrateurs</h3>
                        <p>Comptes avec droits admin</p>
                        <p class="bo-stat-number"><?= $totalAdmins ?></p>
                    </div>
                    <div class="bo-stat-card">
                        <div class="bo-stat-icon"><i class="bi bi-slash-circle-fill"></i></div>
                        <h3>Comptes bannis</h3>
                        <p>Accès restreint</p>
                        <p class="bo-stat-number"><?= $totalBanned ?></p>
                    </div>
                    <div class="bo-stat-card">
                        <div class="bo-stat-icon"><i class="bi bi-trophy-fill"></i></div>
                        <h3>Compétitions</h3>
                        <p>Événements enregistrés</p>
                        <p class="bo-stat-number"><?= $totalCompetitions ?></p>
                    </div>
                    <div class="bo-stat-card">
                        <div class="bo-stat-icon"><i class="bi bi-clipboard-check-fill"></i></div>
                        <h3>Inscriptions</h3>
                        <p>Participants inscrits</p>
                        <p class="bo-stat-number"><?= $totalRegistrations ?></p>
                    </div>
                    <div class="bo-stat-card">
                        <div class="bo-stat-icon"><i class="bi bi-flag-fill"></i></div>
                        <h3>Signalisations</h3>
                        <p>Messages reçus</p>
                        <p class="bo-stat-number"><?= $totalReports ?></p>
                    </div>
                    <div class="bo-stat-card">
                        <div class="bo-stat-icon"><i class="bi bi-link-45deg"></i></div>
                        <h3>Liens d'accueil</h3>
                        <p>Éléments configurés</p>
                        <p class="bo-stat-number"><?= $totalLinks ?></p>
                    </div>
                    <div class="bo-stat-card">
                        <div class="bo-stat-icon"><i class="bi bi-envelope-fill"></i></div>
                        <h3>Courriels autorisés</h3>
                        <p>Destinataires potentiels</p>
                        <p class="bo-stat-number"><?= $acceptedEmails ?></p>
                    </div>
                </div>

                <div class="bo-cta-grid mt-4">
                    <div class="bo-cta-card">
                        <h3>Statut rapide</h3>
                        <div class="bo-stat-list">
                            <div class="bo-stat-item">
                                <span>Compétitions à venir</span>
                                <strong><?= $upcomingCompetitions ?></strong>
                            </div>
                            <div class="bo-stat-item">
                                <span>Comptes non vérifiés</span>
                                <strong><?= $notVerified ?></strong>
                            </div>
                            <div class="bo-stat-item">
                                <span>Profils enfants enregistrés</span>
                                <strong><?= $totalChildren ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="bo-cta-card">
                        <h3>Actions rapides</h3>
                        <div class="bo-cta-actions">
                            <a href="users.php"><span><i class="bi bi-people-fill me-2"></i>Voir les
                                    utilisateurs</span><i class="bi bi-chevron-right"></i></a>
                            <a href="gerer_competitions.php"><span><i class="bi bi-trophy-fill me-2"></i>Gérer les
                                    compétitions</span><i class="bi bi-chevron-right"></i></a>
                            <a href="mailing.php"><span><i class="bi bi-envelope-fill me-2"></i>Envoyer un
                                    mail</span><i class="bi bi-chevron-right"></i></a>
                            <a href="gerer_index_liens.php"><span><i class="bi bi-link-45deg me-2"></i>Modifier les
                                    liens</span><i class="bi bi-chevron-right"></i></a>
                        </div>
                    </div>
                </div>

                <div class="bo-card mt-4">
                    <div class="bo-card-head">
                        <h3>Derniers signalements</h3>
                        <?php if (count($recentReports) > 0): ?>
                            <span class="bo-sidebar-title-count" style="font-size:.75rem;color:#8a8272;">
                                <?= count($recentReports) ?> affiché<?= count($recentReports) > 1 ? 's' : '' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="bo-card-body">
                        <?php if (count($recentReports) === 0): ?>
                            <div class="bo-empty">
                                <i class="bi bi-inbox"></i>
                                Aucun signalement récent.
                            </div>
                        <?php else: ?>
                            <div class="bo-stat-list">
                                <?php foreach ($recentReports as $report): ?>
                                    <div class="bo-report-item">
                                        <span class="bo-report-subject">
                                            <i class="bi bi-flag-fill me-1"></i>
                                            <?= htmlspecialchars($report['subject']) ?>
                                        </span>
                                        <div class="bo-report-meta">
                                            <span><?= htmlspecialchars($report['firstname'] . ' ' . $report['lastname']) ?></span>
                                            <span class="bo-report-date">
                                                <?= htmlspecialchars((new DateTime($report['created_at']))->format('d/m/Y')) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <?php include __DIR__ . '/includes/general/footer.php'; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>