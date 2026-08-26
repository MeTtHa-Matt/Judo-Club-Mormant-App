<?php
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . '/includes/general/db.php';
require_once __DIR__ . '/includes/general/notifications.php';

$userId = (int) $_SESSION['id'];

$stmt = $pdo->query("SELECT id, ceinture FROM ceintures ORDER BY id ASC");
$ceintures = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT cp.*, c.ceinture FROM child_profiles cp JOIN ceintures c ON cp.id_ceinture = c.id WHERE cp.account_id = ? ORDER BY cp.lastname, cp.firstname");
$stmt->execute([$userId]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flashSuccess = $_SESSION['children_flash_success'] ?? null;
$flashError = $_SESSION['children_flash_error'] ?? null;
unset($_SESSION['children_flash_success'], $_SESSION['children_flash_error']);
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
    <title>Mes enfants</title>
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

<body>
    <?php include __DIR__ . '/includes/general/navbar.php'; ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-people-fill me-2"></i>Espace Membre</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Mes enfants</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Enregistrez les profils de vos enfants pour les inscrire
                rapidement aux compétitions.</p>
        </div>
    </header>

    <main class="container my-5">
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success"><i
                    class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="alert alert-danger"><i
                    class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($flashError) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h2 class="h4 fw-bold mb-4"><i class="bi bi-person-plus-fill me-2 text-judo-red"></i>
                        <span id="childrenFormTitle">Ajouter un enfant</span>
                    </h2>
                    <form id="childrenForm" action="includes/account/children_process.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(jcm_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" id="childrenFormAction" value="add_child">
                        <input type="hidden" name="child_id" id="childrenFormChildId" value="0">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Prénom</label>
                            <input type="text" name="firstname" id="childFirstname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nom</label>
                            <input type="text" name="lastname" id="childLastname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Année de naissance</label>
                            <input type="number" name="annee_naissance" id="childAnnee" class="form-control" min="1900"
                                max="<?= date('Y') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ceinture</label>
                            <select name="id_ceinture" id="childCeinture" class="form-select" required>
                                <option value="">-- Choisir --</option>
                                <?php foreach ($ceintures as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['ceinture']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Poids (kg)</label>
                            <input type="number" name="Poids" id="childPoids" class="form-control" min="0" step="1"
                                required>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" id="childrenFormSubmit" class="btn btn-judo-red">Ajouter
                                l'enfant</button>
                            <button type="button" id="childrenFormCancel"
                                class="btn btn-outline-secondary d-none">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h2 class="h4 fw-bold mb-1">Profils enregistrés</h2>
                            <p class="mailing-meta mb-0">Ces profils peuvent être utilisés pour l’inscription rapide aux
                                compétitions.</p>
                        </div>
                        <a href="competitions.php" class="btn btn-outline-secondary">Voir les compétitions</a>
                    </div>

                    <?php if (empty($children)): ?>
                        <div class="profile-empty-state text-center py-5">
                            <i class="bi bi-people-fill display-4 text-judo-red"></i>
                            <p class="mt-3 mb-0 text-muted">Aucun enfant enregistré pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($children as $child): ?>
                                <div
                                    class="list-group-item list-group-item-action d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 py-3 border-bottom">
                                    <div>
                                        <div class="fw-semibold fs-6 mb-1">
                                            <?= htmlspecialchars($child['firstname'] . ' ' . $child['lastname']) ?>
                                        </div>
                                        <div class="small text-muted">
                                            Né en <?= htmlspecialchars($child['annee_naissance']) ?> ·
                                            <?= htmlspecialchars($child['ceinture']) ?>
                                            <?= $child['Poids'] ? ' · ' . htmlspecialchars($child['Poids']) . ' kg' : '' ?>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm edit-child-btn"
                                            data-child='<?= json_encode($child, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>Modifier</button>
                                        <form method="post" action="includes/account/children_process.php"
                                            onsubmit="return confirm('Supprimer ce profil ?');" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(jcm_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="delete_child">
                                            <input type="hidden" name="child_id" value="<?= (int) $child['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editButtons = document.querySelectorAll('.edit-child-btn');
            const cancelButton = document.getElementById('childrenFormCancel');
            const formTitle = document.getElementById('childrenFormTitle');
            const actionInput = document.getElementById('childrenFormAction');
            const childIdInput = document.getElementById('childrenFormChildId');
            const firstnameInput = document.getElementById('childFirstname');
            const lastnameInput = document.getElementById('childLastname');
            const anneeInput = document.getElementById('childAnnee');
            const ceintureSelect = document.getElementById('childCeinture');
            const poidsInput = document.getElementById('childPoids');
            const submitButton = document.getElementById('childrenFormSubmit');

            function resetForm() {
                actionInput.value = 'add_child';
                childIdInput.value = '0';
                formTitle.textContent = 'Ajouter un enfant';
                submitButton.textContent = "Ajouter l'enfant";
                cancelButton.classList.add('d-none');
                firstnameInput.value = '';
                lastnameInput.value = '';
                anneeInput.value = '';
                ceintureSelect.value = '';
                poidsInput.value = '';
            }

            editButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const childData = this.dataset.child ? JSON.parse(this.dataset.child) : null;
                    if (!childData) return;

                    actionInput.value = 'update_child';
                    childIdInput.value = childData.id || '0';
                    formTitle.textContent = 'Modifier le profil de l’enfant';
                    submitButton.textContent = 'Enregistrer les modifications';
                    cancelButton.classList.remove('d-none');
                    firstnameInput.value = childData.firstname || '';
                    lastnameInput.value = childData.lastname || '';
                    anneeInput.value = childData.annee_naissance || '';
                    ceintureSelect.value = childData.id_ceinture || '';
                    poidsInput.value = childData.Poids || '';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });

            if (cancelButton) {
                cancelButton.addEventListener('click', function () {
                    resetForm();
                });
            }
        });
    </script>
</body>

</html>

