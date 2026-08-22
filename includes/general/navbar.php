<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<?php $appRoot = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<nav class="navbar navbar-expand-lg fixed-top custom-navbar">
    <div class="container-fluid px-4 custom-navbar-container">

        <a class="navbar-brand fs-4 navbar-brand-custom" href="<?= $appRoot ?>/index.php">
            <img src="img/jcm.png" width="75" height="75" alt="Logo JCM">
        </a>

        <button class="navbar-toggler custom-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto text-center my-2 my-lg-0">
                <li class="nav-item">
                    <a class="nav-link active-judo" aria-current="page" href="<?= $appRoot ?>/index.php">Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $appRoot ?>/competitions.php">Compétitions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $appRoot ?>/ceintures.php">Passages de ceinture</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $appRoot ?>/reglement.php">Règlement intérieur</a>
                </li>
                <?php if (isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Administration</a>
                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item" href="<?= $appRoot ?>/users.php">Utilisateurs</a></li>
                            <li><a class="dropdown-item" href="<?= $appRoot ?>/mailing.php">Envoyer un mail</a></li>
                            <li><a class="dropdown-item" href="<?= $appRoot ?>/gerer_competitions.php">Compétitions</a></li>
                            <li><a class="dropdown-item" href="<?= $appRoot ?>/gerer_index_liens.php">Liens d'accueil</a></li>
                            <li><a class="dropdown-item" href="<?= $appRoot ?>/chat.php">Assistant IA</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>

            <?php if (!isset($_SESSION['firstname'])): ?>
                <div class="d-flex flex-column flex-lg-row gap-2 mt-3 mt-lg-0 align-items-center navbar-buttons-custom">
                    <a href="login.php" class="btn btn-judo-outline me-lg-2">Connexion</a>
                    <a href="register.php" class="btn btn-judo-red">Inscription</a>
                </div>
            <?php else: ?>
                <div class="dropdown profile-dropdown-judo mx-auto mx-lg-0">
                    <?php
                    $navbarPdp = 'pdp_base.png';
                    if (!empty($_SESSION['pdp'])) {
                        $candidate = basename($_SESSION['pdp']);
                        $candidateFile = __DIR__ . '/../../img/pdps/' . $candidate;
                        if (file_exists($candidateFile) && is_file($candidateFile)) {
                            $navbarPdp = $candidate;
                        }
                    }
                    ?>

                    <button id="profileDropdownToggle" class="profile-toggle-judo" type="button"
                        aria-expanded="false">
                        <span class="profile-avatar-wrapper">
                            <img src="img/pdps/<?= htmlspecialchars($navbarPdp) ?>" alt="Avatar"
                                class="profile-avatar-img">
                            <?php if (isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1): ?>
                                <span class="profile-admin-badge"><i class="bi bi-shield-fill-check"></i></span>
                            <?php endif; ?>
                        </span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end profile-menu-judo">
                        <li class="profile-menu-header">
                            <img src="img/pdps/<?= htmlspecialchars($navbarPdp) ?>" alt="Avatar"
                                class="profile-menu-avatar">
                            <div class="profile-menu-info">
                                <span class="profile-menu-name"><?= htmlspecialchars($_SESSION['firstname']) ?></span>
                                <?php if (isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1): ?>
                                    <span class="profile-menu-role">Administrateur</span>
                                <?php else: ?>
                                    <span class="profile-menu-role">Membre</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="profile-menu-close d-lg-none" aria-label="Fermer">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <a class="dropdown-item profile-menu-item" href="<?= $appRoot ?>/profile.php">
                                <i class="bi bi-person-circle"></i><span>Modifier mon profil</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item profile-menu-item" href="<?= $appRoot ?>/mes_enfants.php">
                                <i class="bi bi-people-fill"></i><span>Mes enfants</span>
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item profile-menu-item" href="<?= $appRoot ?>/signaler.php">
                                <i class="bi bi-flag-fill"></i><span>Signaler un problème</span>
                            </a>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <a class="dropdown-item profile-menu-item text-danger" href="includes/account/logout.php">
                                <i class="bi bi-box-arrow-right"></i><span>Se déconnecter</span>
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php if (function_exists('jcm_render_notifications')): ?>
    <?php jcm_render_notifications(); ?>
<?php endif; ?>

<?php if (isset($_SESSION['id'])): ?>
    <script>
        window.JCM = window.JCM || {};
        window.JCM.appRoot = '<?= $appRoot ?>';
    </script>
    <script src="js/users-presence.js?v=<?php echo filemtime(__DIR__ . '/../../js/users-presence.js'); ?>"></script>
<?php endif; ?>

<script src="js/navbar.js"></script>