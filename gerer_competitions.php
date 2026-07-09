<?php
require_once __DIR__ . '/includes/general/session_start_pwa.php';
require_once __DIR__ . '/includes/general/db.php';
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . "/includes/general/notifications.php";

include __DIR__ . '/includes/competitions/gerer_competitions.php';
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
    <title>Gérer les compétitions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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
    <?php include __DIR__ . "/includes/general/navbar.php"; ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-gear-fill me-2"></i>Admin</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Gérer les compétitions</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Ajoutez, modifiez ou supprimez les compétitions du club.
            </p>
        </div>
    </header>

    <main class="container pb-5">
        <div class="bo-toolbar">
            <h2>Compétitions</h2>
            <a href="competitions.php" class="bo-btn bo-btn-outline">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="bo-alert"><i class="bi bi-check-circle-fill"></i> Opération effectuée.</div>
        <?php endif; ?>

        <div class="bo-grid">
            <div class="bo-card">
                <div class="bo-card-head">
                    <h3><?= $editing ? 'Modifier' : 'Ajouter' ?></h3>
                </div>
                <div class="bo-card-body">
                    <form method="post" action="gerer_competitions.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
                        <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?= (int) $editData['id'] ?>">
                        <?php endif; ?>

                        <div class="bo-field">
                            <label for="f-nom">Nom</label>
                            <input id="f-nom" class="form-control" name="nom" required
                                value="<?= htmlspecialchars($editData['nom'] ?? '') ?>">
                        </div>

                        <div class="bo-field">
                            <label for="f-lieu">Lieu</label>
                            <input id="f-lieu" class="form-control" name="lieu"
                                value="<?= htmlspecialchars($editData['lieu'] ?? '') ?>">
                        </div>

                        <div class="bo-field">
                            <label for="cible-picker">Catégories cibles</label>
                            <div class="bo-cible-picker" id="cible-picker">
                                <div class="bo-cible-picker-head">
                                    <span class="bo-cible-picker-title">
                                        <i class="bi bi-people-fill me-2"></i>Choisir une ou plusieurs catégories
                                    </span>
                                    <span class="bo-cible-picker-count"><?= count($cibles) ?> catégories</span>
                                </div>
                                <div class="bo-cible-options">
                                    <?php foreach ($cibles as $c): ?>
                                        <div class="bo-cible-option">
                                            <input class="form-check-input" type="checkbox" name="id_cibles[]"
                                                id="cible-<?= (int) $c['id'] ?>" value="<?= (int) $c['id'] ?>"
                                                <?= in_array((int) $c['id'], $selectedEditCibleIds, true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="cible-<?= (int) $c['id'] ?>">
                                                <?= htmlspecialchars($c['cible']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="bo-field">
                            <label for="f-date">Date</label>
                            <input id="f-date" type="date" name="date" class="form-control"
                                value="<?= htmlspecialchars($editData['date'] ?? '') ?>">
                        </div>

                        <div class="bo-field">
                            <label for="f-date-limite">Date limite d'inscription</label>
                            <input id="f-date-limite" type="date" name="date_limite_inscription" class="form-control"
                                value="<?= htmlspecialchars($editData['date_limite_inscription'] ?? '') ?>" required>
                            <p class="bo-help">Par défaut, 7 jours avant la date de la compétition.</p>
                        </div>

                        <div class="bo-field">
                            <label for="f-info">Informations</label>
                            <textarea id="f-info" name="informations" class="form-control"
                                rows="3"><?= htmlspecialchars($editData['informations'] ?? '') ?></textarea>
                        </div>

                        <div class="bo-field">
                            <label for="f-image">Image</label>
                            <?php if (!empty($editData['image'])): ?>
                                <div class="mb-2">
                                    <img src="img/competitions/<?= htmlspecialchars($editData['image']) ?>" alt=""
                                        class="bo-thumb">
                                </div>
                            <?php endif; ?>
                            <input id="f-image" type="file" class="form-control" name="image_file" accept="image/*">
                            <p class="bo-help">Formats autorisés : jpg, png, gif. Max 5 Mo.</p>
                        </div>

                        <div class="bo-form-actions">
                            <button class="bo-btn bo-btn-primary" type="submit">
                                <i class="bi <?= $editing ? 'bi-check-lg' : 'bi-plus-lg' ?>"></i>
                                <?= $editing ? 'Enregistrer' : 'Ajouter' ?>
                            </button>
                            <?php if ($editing): ?>
                                <a href="gerer_competitions.php" class="bo-btn bo-btn-outline">Annuler</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bo-card">
                <div class="bo-card-head">
                    <h3>Liste des compétitions</h3>
                    <span class="bo-help mb-0"><?= count($competitions) ?> au total</span>
                </div>
                <div class="bo-card-body">
                    <?php if (empty($competitions)): ?>
                        <div class="bo-empty">
                            <i class="bi bi-inbox"></i>
                            Aucune compétition enregistrée pour le moment.
                        </div>
                    <?php else: ?>
                        <div class="bo-table-wrap">
                            <table class="bo-table">
                                <thead>
                                    <tr>
                                        <th>id</th>
                                        <th>date</th>
                                        <th>nom</th>
                                        <th>lieu</th>
                                        <th>cible</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($competitions as $c): ?>
                                        <tr>
                                            <td class="bo-id" data-label="Id"><?= (int) $c['id'] ?></td>
                                            <td class="bo-date" data-label="Date"><?= htmlspecialchars($c['date']) ?></td>
                                            <td data-label="Nom"><?= htmlspecialchars($c['nom']) ?></td>
                                            <td data-label="Lieu"><?= htmlspecialchars($c['lieu']) ?></td>
                                            <td data-label="Cible"><?= htmlspecialchars($c['cible_nom'] ?? '—') ?></td>
                                            <td data-label="Actions">
                                                <div class="bo-actions-cell">
                                                    <a href="gerer_competitions.php?edit=<?= (int) $c['id'] ?>"
                                                        class="bo-btn bo-btn-outline bo-btn-sm">
                                                        <i class="bi bi-pencil"></i> Modifier
                                                    </a>
                                                    <form method="post" action="gerer_competitions.php"
                                                        onsubmit="return confirm('Supprimer cette compétition ?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                                        <button class="bo-btn bo-btn-dark bo-btn-sm" type="submit">
                                                            <i class="bi bi-trash"></i> Supprimer
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
            const competitionDateInput = document.getElementById('f-date');
            const deadlineInput = document.getElementById('f-date-limite');
            if (!competitionDateInput || !deadlineInput) return;

            const applyDefaultDeadline = () => {
                if (!competitionDateInput.value) return;
                if (deadlineInput.dataset.userEdited === '1' && deadlineInput.value) return;
                const [year, month, day] = competitionDateInput.value.split('-').map(Number);
                const competitionDate = new Date(year, month - 1, day);
                competitionDate.setDate(competitionDate.getDate() - 7);
                const formatted = [competitionDate.getFullYear(), String(competitionDate.getMonth() + 1).padStart(2, '0'), String(competitionDate.getDate()).padStart(2, '0')].join('-');
                deadlineInput.value = formatted;
            };

            competitionDateInput.addEventListener('change', applyDefaultDeadline);
            deadlineInput.addEventListener('input', function () {
                deadlineInput.dataset.userEdited = '1';
            });
            if (!deadlineInput.value) {
                applyDefaultDeadline();
            }
        });
    </script>
</body>

</html>

