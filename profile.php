<?php
require_once __DIR__ . "/includes/general/access_check.php";
require_once __DIR__ . "/includes/general/notifications.php";
require_once __DIR__ . "/includes/general/db.php";

include __DIR__ . "/includes/general/profile.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="JCM">
    <title>Mon Profil</title>
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
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png?v=20260709">
    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png?v=20260709">
    <link rel="apple-touch-icon" sizes="180x180" href="img/jcm.png?v=20260709">
    <link rel="shortcut icon" href="img/jcm.png?v=20260709">
</head>

<body>
    <?php include __DIR__ . "/includes/general/navbar.php" ?>

    <header class="hero-judo profile-hero text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-person-fill me-2"></i>Espace Membre</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Mon Profil</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Gérez vos informations et vos inscriptions</p>
        </div>
    </header>

    <main class="container profile-main mt-5">

        <div class="profile-identity-card card border-0 shadow-sm rounded-4">
            <div class="profile-avatar-frame">
                <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Photo de profil" class="profile-avatar">
                <label for="avatarInput" class="profile-avatar-edit" title="Changer la photo">
                    <i class="bi bi-camera-fill"></i>
                </label>
            </div>
            <p class="text-muted small mt-2 mb-2 fst-italic">Cliquez sur le logo "<i class="bi bi-camera-fill"></i>" pour modifier votre photo de profil.</p>
            <div class="profile-identity-body">
                <h2 class="h4 fw-bold mb-1">
                    <?= htmlspecialchars($account['firstname'] . ' ' . $account['lastname']) ?>
                    <?php if ((int) $account['admin'] === 1): ?>
                        <span class="badge-admin"><i class="bi bi-shield-fill-check me-1"></i>Admin</span>
                    <?php endif; ?>
                </h2>
                <p class="text-muted mb-0"><i
                        class="bi bi-envelope-fill me-2 text-judo-red"></i><?= htmlspecialchars($account['email']) ?>
                </p>
            </div>
            <form action="profile.php" method="POST" enctype="multipart/form-data" class="d-none" id="avatarForm">
                <input type="hidden" name="action" value="update_avatar">
                <input type="file" name="pdp" id="avatarInput" accept=".jpg,.jpeg,.png,.webp"
                    onchange="document.getElementById('avatarForm').submit()">
            </form>
        </div>

        <?php if ($flashSuccess): ?>
            <div class="alert-judo alert-judo-success">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flashSuccess) ?>
            </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="alert-judo alert-judo-error">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($flashError) ?>
            </div>
        <?php endif; ?>

        <div class="row g-4 mt-1">

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 profile-card">
                    <h3 class="h5 fw-bold mb-4"><i class="bi bi-person-vcard me-2 text-judo-red"></i>Informations
                        personnelles</h3>
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="action" value="update_info">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-uppercase">Prénom</label>
                            <input type="text" name="firstname" class="form-control profile-input"
                                value="<?= htmlspecialchars($account['firstname']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-uppercase">Nom</label>
                            <input type="text" name="lastname" class="form-control profile-input"
                                value="<?= htmlspecialchars($account['lastname']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-judo-red">Enregistrer les modifications</button>
                    </form>

                    <hr class="my-4">

                    <div class="jcm-toggle-row">
                        <div class="jcm-toggle-text">
                            <i class="bi bi-envelope-fill"></i>
                            <div>
                                <p class="jcm-toggle-title mb-0">Recevoir les emails du club</p>
                                <p class="jcm-toggle-desc mb-0">Actus, convocations et infos importantes par email.</p>
                            </div>
                        </div>
                        <label class="jcm-switch" for="accept_email_toggle">
                            <input type="checkbox" id="accept_email_toggle" class="jcm-switch-input"
                                <?= (int) ($account['accept_email'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <span class="jcm-switch-track"><span class="jcm-switch-thumb"></span></span>
                        </label>
                    </div>
                </div>
            </div>

            <script src="js/profile-toggle.js?v=<?php echo filemtime('js/profile-toggle.js'); ?>" defer></script>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 profile-card">
                    <h3 class="h5 fw-bold mb-4"><i class="bi bi-shield-lock me-2 text-judo-red"></i>Sécurité</h3>
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="action" value="update_password">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-uppercase">Mot de passe actuel</label>
                            <input type="password" name="current_password" class="form-control profile-input" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-uppercase">Nouveau mot de passe</label>
                            <input type="password" name="new_password" class="form-control profile-input" minlength="8"
                                required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-uppercase">Confirmer le mot de passe</label>
                            <input type="password" name="confirm_password" class="form-control profile-input"
                                minlength="8" required>
                        </div>
                        <button type="submit" class="btn btn-judo-outline">Changer le mot de passe</button>
                    </form>
                </div>
            </div>

        </div>

        <section class="mt-5">
            <div class="text-center mb-4">
                <h2 class="h2 fw-bold text-uppercase section-title">Mes <span class="text-judo-red">Inscriptions</span>
                </h2>
            </div>

            <?php if (count($inscriptions) === 0): ?>
                <div class="profile-empty-state text-center">
                    <i class="bi bi-clipboard-x display-5 text-judo-red"></i>
                    <p class="mt-3 mb-0 text-muted">Vous n'êtes inscrit à aucune compétition pour le moment.</p>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 custom-judo-table">
                            <thead class="table-dark text-uppercase small">
                                <tr>
                                    <th scope="col" class="ps-4 py-3">Compétition</th>
                                    <th scope="col" class="py-3">Date</th>
                                    <th scope="col" class="py-3">Lieu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inscriptions as $i): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?= htmlspecialchars($i['nom']) ?></td>
                                        <td><?= htmlspecialchars((new DateTime($i['date']))->format('d/m/Y')) ?></td>
                                        <td><?= htmlspecialchars($i['lieu'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 profile-card" style="border: 1px solid #f5c2c7;">
                    <h3 class="h5 fw-bold mb-3 text-judo-red"><i class="bi bi-exclamation-octagon-fill me-2"></i>Zone
                        dangereuse</h3>
                    <p class="text-muted small mb-4">
                        La suppression de votre compte est <strong>définitive et irréversible</strong>. Toutes vos données
                        personnelles, inscriptions et historiques seront effacés.
                    </p>
                    <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal"
                        data-bs-target="#modal-delete-account">
                        <i class="bi bi-trash3 me-2"></i>Supprimer mon compte
                    </button>
                </div>
            </div>
        </section>

    </main>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>
    
    <div class="modal fade" id="modal-delete-account" tabindex="-1" aria-labelledby="modal-delete-account-label"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #b3222b 0%, #7a1218 100%); border: none;">
                        <h2 class="modal-title h5 text-white fw-bold mb-0" id="modal-delete-account-label">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmer la suppression
                    </h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="fw-semibold mb-3">
                        Cette action est <span class="text-judo-red">définitive</span>. Toutes vos données seront
                        supprimées et vous serez déconnecté(e) immédiatement.
                    </p>
                    <form action="includes/account/delete_account_process.php" method="POST" id="form-delete-account">
                        <div class="mb-3">
                            <label for="delete_password" class="form-label small fw-semibold text-uppercase">
                                Confirmez avec votre mot de passe
                            </label>
                            <input type="password" id="delete_password" name="password" class="form-control profile-input"
                                placeholder="Votre mot de passe" required>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-outline-dark flex-fill" data-bs-dismiss="modal">
                                Annuler
                            </button>
                            <button type="submit" class="btn btn-danger flex-fill">
                                <i class="bi bi-trash3 me-1"></i>Supprimer définitivement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>
</html>

