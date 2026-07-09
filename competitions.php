<?php

require_once __DIR__ . '/includes/general/session_start_pwa.php';
require_once __DIR__ . '/includes/general/db.php';
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . "/includes/general/notifications.php";

include __DIR__ . '/includes/competitions/charger_competitions.php';
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
    <title>Compétitions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" href="img/jcm.png">
</head>

<body data-is-admin="<?= isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1 ? '1' : '0' ?>">
    <?php include __DIR__ . "/includes/general/navbar.php" ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-trophy me-2"></i>Calendrier sportif</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Compétitions</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Retrouvez toutes les dates de la saison</p>
            <?php if (isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1): ?>
                <div class="mt-3">
                    <a href="gerer_competitions.php" class="btn btn-judo-red btn-lg">Gérer les compétitions</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="container my-5 pt-4">
        <section id="calendrier" class="mb-5 scroll-margin">
            <div class="text-center mb-4">
                <h2 class="h1 fw-bold text-uppercase position-relative d-inline-block section-title">
                    Calendrier <span class="text-judo-red">Mensuel</span>
                </h2>
            </div>

            <div class="card border-0 shadow-sm rounded-4 calendar-card">
                <div class="calendar-toolbar">
                    <a class="calendar-nav-btn"
                        href="?mois=<?= $moisPrecedent ?>&annee=<?= $anneePrecedente ?>#calendrier"
                        aria-label="Mois précédent">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <div class="calendar-current-month">
                        <?= $nomsMois[$mois] ?> <span class="text-judo-red"><?= $annee ?></span>
                    </div>
                    <a class="calendar-nav-btn" href="?mois=<?= $moisSuivant ?>&annee=<?= $anneeSuivante ?>#calendrier"
                        aria-label="Mois suivant">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>

                <div class="calendar-grid">
                    <?php foreach ($nomsJoursCourts as $nomJour): ?>
                        <div class="calendar-weekday"><?= $nomJour ?></div>
                    <?php endforeach; ?>

                    <?php for ($i = 0; $i < $decalageDebut; $i++): ?>
                        <div class="calendar-day calendar-day-empty"></div>
                    <?php endfor; ?>

                    <?php for ($jour = 1; $jour <= $nbJoursDansMois; $jour++):
                        $dateJourStr = sprintf('%04d-%02d-%02d', $annee, $mois, $jour);
                        $estAujourdHui = $estMoisCourant && ((int) date('j') === $jour);
                        $compsDuJour = $competitionsParJour[$jour] ?? [];
                        $nbComps = count($compsDuJour);

                        if ($nbComps > 0) {
                            $itemsHtml = '';
                            foreach ($compsDuJour as $comp) {
                                $itemsHtml .= '<li class="upcoming-item" data-bs-toggle="modal" data-bs-target="#modalCompetition"'
                                    . ' data-nom="' . htmlspecialchars($comp['nom']) . '"'
                                    . ' data-lieu="' . htmlspecialchars($comp['lieu'] ?? '') . '"'
                                    . ' data-date="' . htmlspecialchars(formatDateFr($comp['date'])) . '"'
                                    . ' data-date-iso="' . htmlspecialchars($comp['date']) . '"'
                                    . ' data-cible="' . htmlspecialchars($comp['cible_nom'] ?? '') . '"'
                                    . ' data-informations="' . htmlspecialchars($comp['informations'] ?? '') . '"'
                                    . ' data-image="' . htmlspecialchars(getCompetitionImageUrl($comp['image'])) . '"'
                                    . ' data-registration-open="' . (isCompetitionRegistrationOpen($comp['date'] ?? null, $comp['date_limite_inscription'] ?? null) ? '1' : '0') . '"'
                                    . ' data-id="' . (int) $comp['id'] . '" role="button" tabindex="0">'
                                    . '<span class="upcoming-name">' . htmlspecialchars($comp['nom']) . '</span>'
                                    . '<i class="bi bi-chevron-right upcoming-arrow"></i>'
                                    . '</li>';
                            }
                            $jourModalContent = '<ul class="reglement-list upcoming-list mb-0">' . $itemsHtml . '</ul>';
                        } else {
                            $jourModalContent = '<div class="p-4 text-center text-muted"><i class="bi bi-calendar-x fs-2 d-block mb-2 text-judo-red"></i>Aucune compétition programmée ce jour.</div>';
                        }
                        ?>
                        <div class="calendar-day<?= $estAujourdHui ? ' calendar-day-today' : '' ?><?= $nbComps > 0 ? ' calendar-day-has-event' : '' ?>"
                            data-bs-toggle="modal" data-bs-target="#modalJourCompetitions"
                            data-jour-date="<?= htmlspecialchars(formatDateFr($dateJourStr)) ?>"
                            data-jour-content="<?= htmlspecialchars($jourModalContent) ?>" role="button" tabindex="0">
                            <div class="calendar-day-header">
                                <span class="calendar-day-number"><?= $jour ?></span>
                            </div>
                            <?php if ($nbComps > 0): ?>
                                <div class="calendar-day-event-count-wrap">
                                    <span class="calendar-day-event-count"
                                        aria-label="<?= $nbComps ?> événement<?= $nbComps > 1 ? 's' : '' ?>">
                                        <?= $nbComps ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="calendar-day-events">
                                <?php foreach (array_slice($compsDuJour, 0, 2) as $comp): ?>
                                    <button type="button" class="competition-pill" data-bs-toggle="modal"
                                        data-bs-target="#modalCompetition" data-nom="<?= htmlspecialchars($comp['nom']) ?>"
                                        data-lieu="<?= htmlspecialchars($comp['lieu'] ?? '') ?>"
                                        data-date="<?= htmlspecialchars(formatDateFr($comp['date'])) ?>"
                                        data-date-iso="<?= htmlspecialchars($comp['date']) ?>"
                                        data-cible="<?= htmlspecialchars($comp['cible_nom'] ?? '') ?>"
                                        data-informations="<?= htmlspecialchars($comp['informations'] ?? '') ?>"
                                        data-image="<?= htmlspecialchars(getCompetitionImageUrl($comp['image'])) ?>"
                                        data-registration-open="<?= isCompetitionRegistrationOpen($comp['date'] ?? null, $comp['date_limite_inscription'] ?? null) ? '1' : '0' ?>"
                                        data-id="<?= (int) $comp['id'] ?>">
                                        <?= htmlspecialchars($comp['nom']) ?>
                                    </button>
                                <?php endforeach; ?>
                                <?php if ($nbComps > 2): ?>
                                    <span class="competition-pill-more">+<?= $nbComps - 2 ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </section>

        <section id="a-venir" class="mb-5 scroll-margin">
            <div class="text-center mb-4">
                <h2 class="h1 fw-bold text-uppercase position-relative d-inline-block section-title">
                    Évènements à <span class="text-judo-red">Venir</span>
                </h2>
            </div>

            <div class="card border-0 shadow-sm rounded-4 reglement-card">
                <?php if (empty($competitionsAVenir)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-calendar-x fs-2 d-block mb-2 text-judo-red"></i>
                        Aucune compétition programmée pour le moment.
                    </div>
                <?php else: ?>
                    <div class="upcoming-list">
                        <?php foreach ($competitionsAVenir as $comp): ?>
                            <div class="upcoming-item" data-bs-toggle="modal" data-bs-target="#modalCompetition"
                                data-nom="<?= htmlspecialchars($comp['nom']) ?>"
                                data-lieu="<?= htmlspecialchars($comp['lieu'] ?? '') ?>"
                                data-date="<?= htmlspecialchars(formatDateFr($comp['date'])) ?>"
                                data-date-iso="<?= htmlspecialchars($comp['date']) ?>"
                                data-cible="<?= htmlspecialchars($comp['cible_nom'] ?? '') ?>"
                                data-informations="<?= htmlspecialchars($comp['informations'] ?? '') ?>"
                                data-image="<?= htmlspecialchars(getCompetitionImageUrl($comp['image'])) ?>"
                                data-registration-open="<?= isCompetitionRegistrationOpen($comp['date'] ?? null, $comp['date_limite_inscription'] ?? null) ? '1' : '0' ?>"
                                data-id="<?= (int) $comp['id'] ?>" role="button" tabindex="0">
                                <div class="upcoming-date">
                                    <i class="bi bi-calendar-event me-2 text-judo-red"></i><?= formatDateFr($comp['date']) ?>
                                </div>
                                <div class="upcoming-name"><?= htmlspecialchars($comp['nom']) ?></div>
                                <i class="bi bi-chevron-right upcoming-arrow"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <div class="modal fade modal-ceinture" id="modalCompetition" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCompetitionNom">Compétition</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4">
                    <img id="modalCompetitionImage" src="" alt="" class="img-fluid rounded-3 mb-3 d-none">
                    <p class="mb-2"><i class="bi bi-calendar-event me-2 text-judo-red"></i><strong>Date : </strong>
                        <span id="modalCompetitionDate"></span>
                    </p>
                    <p class="mb-2 d-none" id="modalCompetitionLieuWrapper">
                        <i class="bi bi-geo-alt-fill me-2 text-judo-red"></i><strong>Lieu : </strong>
                        <span id="modalCompetitionLieu"></span>
                    </p>
                    <p class="mb-3 d-none" id="modalCompetitionCibleWrapper">
                        <i class="bi bi-people-fill me-2 text-judo-red"></i><strong>Catégorie : </strong>
                        <span class="badge bg-dark" id="modalCompetitionCible"></span>
                    </p>
                    <p class="reglement-text mb-0 d-none" id="modalCompetitionInfosWrapper">
                        <span id="modalCompetitionInfos"></span>
                    </p>
                    <div class="modal-competition-main-actions mt-4">
                        <?php if (!isset($_SESSION['id'])): ?>
                            <a id="modalCompetitionRegisterLink" href="register.php"
                                class="btn btn-judo-outline-white modal-competition-action-btn w-100">S'inscrire</a>
                        <?php else: ?>
                            <button type="button" id="modalCompetitionRegisterBtn"
                                class="btn btn-judo-red modal-competition-action-btn w-100">S'inscrire</button>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="modalCompetitionId" value="">
                </div>
                <div class="modal-footer modal-competition-footer">
                    <?php if (isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1): ?>
                        <button type="button" id="modalCompetitionViewInscritsAllBtn"
                            class="btn btn-judo-outline modal-competition-action-btn">Voir les inscrits</button>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['id'])): ?>
                        <button type="button" id="modalCompetitionViewInscritsMineBtn"
                            class="btn btn-judo-outline modal-competition-action-btn">Voir mes inscriptions</button>
                    <?php endif; ?>
                    <button type="button"
                        class="btn btn-judo-outline modal-competition-action-btn modal-competition-close-btn"
                        data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-ceinture" id="modalRegistrationChoice" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">S'inscrire</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3">Choisissez votre mode d'inscription pour <strong
                            id="modalRegistrationChoiceCompetitionName"></strong>.</p>
                    <div class="d-grid gap-3">
                        <button type="button" id="choiceManualInscriptionBtn" class="btn btn-judo-red">Inscrire
                            manuellement</button>
                        <button type="button" id="choiceChildInscriptionBtn" class="btn btn-judo-outline-dark">Inscrire
                            mes enfants</button>
                    </div>
                    <input type="hidden" id="registrationChoiceCompetitionId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-judo-outline" data-bs-dismiss="modal">Annuler</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-ceinture" id="modalChildProfiles" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mes enfants</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="modalChildProfilesContent" class="child-profiles-list"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-judo-outline" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-ceinture" id="modalJourCompetitions" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalJourCompetitionsTitle">Compétitions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-0" id="modalJourCompetitionsBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-judo-outline" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-inscrits" id="modalInscrits" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Inscrits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-3">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-judo-outline" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalUnsubscribeDeadlinePassed" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Désinscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Veuillez prévenir le coach que vous serez absent à la compétition, date butoir de
                        désinscription dépassée.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-judo-red" data-bs-dismiss="modal">J'ai compris</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalInscription" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Inscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="post" action="includes/competitions/inscrire_process.php">
                    <div class="modal-body">
                        <p>Inscription à : <strong id="modalInscriptionCompetitionName"></strong></p>
                        <input type="hidden" name="id_competition" id="inscription_competition_id" value="">
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input name="nom" id="inscription_nom" class="form-control" required
                                value="<?= htmlspecialchars($_SESSION['lastname'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prénom</label>
                            <input name="prenom" id="inscription_prenom" class="form-control" required
                                value="<?= htmlspecialchars($_SESSION['firstname'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Année de naissance</label>
                            <input name="annee_naissance" id="inscription_annee" type="number" min="1900"
                                max="<?= date('Y') ?>" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ceinture</label>
                            <select name="id_ceinture" id="inscription_ceinture" class="form-select">
                                <option value="">-- Choisir --</option>
                                <?php foreach ($ceintures as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['ceinture']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Poids (kg)</label>
                            <input name="Poids" id="inscription_poids" type="number" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-judo-red">Envoyer</button>
                        <button type="button" class="btn btn-judo-outline" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="js/competitions.js"></script>
    <script src="js/modal.js"></script>
</body>

</html>