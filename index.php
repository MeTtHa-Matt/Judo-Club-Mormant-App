<?php
require_once __DIR__ . "/includes/general/access_check.php";
require_once __DIR__ . "/includes/general/notifications.php";

include __DIR__ . '/includes/liens/liens_index_default.php';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png?v=20260709">
    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png?v=20260709">
    <link rel="apple-touch-icon" sizes="180x180" href="img/jcm.png?v=20260709">
    <link rel="shortcut icon" href="img/jcm.png?v=20260709">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="JCM">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Judo Club Mormant</title>
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
</head>

<body>
    <?php include __DIR__ . "/includes/general/navbar.php" ?>

    <header class="hero-judo text-center d-flex align-items-center justify-content-center">

        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-file-earmark-text me-2"></i>Saison 2026-2027</span>
            </div>
            <h1 class="display-3 fw-bolder text-uppercase tracking-wider animate-fade-in hero-title">Judo Club de
                Mormant</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-3 fw-light mt-4 hero-subtitle">Discipline • Respect • Fraternité</p>
            <div class="mt-5">
                <a href="#horaires" class="btn btn-judo-red btn-lg me-3 btn-hero">Voir les horaires</a>
                <a href="#inscriptions" class="btn btn-judo-outline-white btn-lg btn-hero">S'inscrire en ligne</a>
            </div>
        </div>
    </header>

    <main class="pt-4">

        <div class="container my-5">
            <section id="horaires" class="mb-5 scroll-margin">
                <div class="text-center mb-4">
                    <h2 class="h1 fw-bold text-uppercase position-relative d-inline-block section-title">
                        Horaires Saison <span class="text-judo-red">2026-2027</span>
                    </h2>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 custom-judo-table horaires-home-table">
                            <thead class="table-dark text-uppercase small">
                                <tr>
                                    <th scope="col" class="ps-4 py-3 text-center">Catégories & Horaires</th>
                                    <th scope="col" class="py-3 text-center pe-4">Spécificités</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="ps-4 pe-4 text-center">
                                        <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                                            <div class="fw-bold">Baby <span
                                                    class="badge bg-secondary rounded-pill fw-normal ms-2">2023 / 2022 /
                                                    2021</span></div>
                                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                                <span class="badge bg-light text-dark border p-2"><i
                                                        class="bi bi-clock me-1 text-judo-red"></i> Vendredi : 17h15 -
                                                    18h</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center pe-4 text-muted small">—</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 pe-4 text-center">
                                        <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                                            <div class="fw-bold">Pré-poussins <span
                                                    class="badge bg-secondary rounded-pill fw-normal ms-2">2020 /
                                                    2019</span>
                                            </div>
                                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                                <span class="badge bg-light text-dark border p-2"><i
                                                        class="bi bi-clock me-1 text-judo-red"></i> Mardi : 17h15 -
                                                    18h15</span>
                                                <span class="badge bg-light text-dark border p-2"><i
                                                        class="bi bi-clock me-1 text-judo-red"></i> Vendredi : 18h -
                                                    19h</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center pe-4 text-muted small">—</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 pe-4 text-center">
                                        <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                                            <div class="fw-bold">Poussins & Benjamins <span
                                                    class="badge bg-secondary rounded-pill fw-normal ms-2">2018 à
                                                    2015</span>
                                            </div>
                                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                                <span class="badge bg-light text-dark border p-2"><i
                                                        class="bi bi-clock me-1 text-judo-red"></i> Mardi : 18h15 -
                                                    19h15</span>
                                                <span class="badge bg-light text-dark border p-2"><i
                                                        class="bi bi-clock me-1 text-judo-red"></i> Vendredi : 19h -
                                                    20h</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center pe-4 text-muted small">—</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 pe-4 text-center">
                                        <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                                            <div class="fw-bold">Minimes <span
                                                    class="badge bg-secondary rounded-pill fw-normal ms-2">2014 /
                                                    2013</span>
                                            </div>
                                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                                <span class="badge bg-light text-dark border p-2"><i
                                                        class="bi bi-clock me-1 text-judo-red"></i> Mardi : 19h15 -
                                                    20h15</span>
                                                <span class="badge bg-light text-dark border p-2"><i
                                                        class="bi bi-clock me-1 text-judo-red"></i> Vendredi : 20h -
                                                    21h30</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center pe-4 text-judo-red small fw-semibold"><i
                                            class="bi bi-geo-alt-fill me-1"></i>Entrainements extérieurs dès Minime
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-4 pe-4 text-center">
                                        <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                                            <div class="fw-bold">Cadets & Adultes <span
                                                    class="badge bg-secondary rounded-pill fw-normal ms-2">Dès
                                                    2012</span>
                                            </div>
                                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                                <span class="badge bg-light text-dark border p-2"><i
                                                        class="bi bi-clock me-1 text-judo-red"></i> Mardi : 19h15 -
                                                    20h15</span>
                                                <span class="badge bg-light text-dark border p-2"><i
                                                        class="bi bi-clock me-1 text-judo-red"></i> Vendredi : 20h -
                                                    21h30</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center pe-4 text-judo-red small fw-semibold">Accès libre tapis (S/D
                                        9h-13h)
                                        / Prépa UV</td>
                                </tr>
                                <tr class="table-light-judo">
                                    <td class="ps-4 pe-4 text-center">
                                        <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                                            <div class="fw-bold text-dark"><i class="text-warning me-1"></i> Préparation
                                                Physique
                                                <span class="badge bg-dark rounded-pill fw-normal ms-2">Cadets &
                                                    +</span>
                                            </div>
                                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                                <span class="badge bg-light text-dark border p-2"><i
                                                        class="bi bi-clock me-1 text-judo-red"></i> Mardi : 20h15 -
                                                    21h30
                                                    (au dojo)</span>
                                                <span class="badge bg-light text-dark border p-2"><i
                                                        class="bi bi-clock me-1 text-judo-red"></i> Jeudi : 19h30 (au
                                                    stade)</span>
                                                <span class="badge bg-light text-dark border p-2"><i
                                                        class="bi bi-clock me-1 text-judo-red"></i> Samedi : 10h - 11h30
                                                    (au
                                                    dojo)</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center pe-4 text-judo-red small fw-semibold">Renforcement / Cardio
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <div class="container my-5">

            <section id="inscriptions" class="mb-5 scroll-margin">
                <div class="row g-4 text-center">
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm p-4 rounded-4 hover-lift">
                            <div class="icon-shape bg-red-light text-judo-red mx-auto mb-3"><i
                                    class="bi bi-pencil-square fs-3"></i></div>
                            <h3 class="h5 fw-bold"><?= htmlspecialchars($homeInscriptionLink['label']) ?></h3>
                            <p class="text-muted small">Gagnez du temps en effectuant votre réinscription ou première
                                demande directement en ligne.</p>
                            <a href="<?= htmlspecialchars($homeInscriptionLink['url'], ENT_QUOTES, 'UTF-8') ?>"
                                target="_blank" rel="noopener noreferrer"
                                title="<?= htmlspecialchars($homeInscriptionLink['title']) ?>"
                                class="btn btn-judo-red mt-auto"><?= htmlspecialchars($homeInscriptionLink['title']) ?></a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm p-4 rounded-4 hover-lift">
                            <div class="icon-shape bg-dark-light text-dark mx-auto mb-3"><i
                                    class="bi bi-bag-check fs-3"></i></div>
                            <h3 class="h5 fw-bold">Boutique Club</h3>
                            <p class="text-muted small">Commandez vos kimonos, dossards officiels et vêtements aux
                                couleurs du Judo Club Mormant.</p>
                            <div class="d-grid gap-2 mt-auto">
                                <a href="<?= htmlspecialchars($homeKimonosLink['url'], ENT_QUOTES, 'UTF-8') ?>"
                                    target="_blank" rel="noopener noreferrer"
                                    title="<?= htmlspecialchars($homeKimonosLink['title']) ?>"
                                    class="btn btn-judo-outline btn-sm"><?= htmlspecialchars($homeKimonosLink['label']) ?></a>
                                <a href="<?= htmlspecialchars($homeVetementsLink['url'], ENT_QUOTES, 'UTF-8') ?>"
                                    target="_blank" rel="noopener noreferrer"
                                    title="<?= htmlspecialchars($homeVetementsLink['title']) ?>"
                                    class="btn btn-judo-outline btn-sm"><?= htmlspecialchars($homeVetementsLink['label']) ?></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm p-4 rounded-4 hover-lift">
                            <div class="icon-shape bg-warning-light text-warning mx-auto mb-3"><i
                                    class="bi bi-cart3 fs-3"></i></div>
                            <h3 class="h5 fw-bold"><?= htmlspecialchars($homeCraqsLink['label']) ?></h3>
                            <p class="text-muted small">Commandez vos barres de céréales énergétiques Les Craq's
                                directement depuis l'application.</p>
                            <a href="<?= htmlspecialchars($homeCraqsLink['url'], ENT_QUOTES, 'UTF-8') ?>"
                                target="_blank" rel="noopener noreferrer"
                                title="<?= htmlspecialchars($homeCraqsLink['title']) ?>"
                                class="btn btn-dark mt-auto"><?= htmlspecialchars($homeCraqsLink['title']) ?></a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mb-5">
                <div class="text-center mb-4">
                    <h2 class="h2 fw-bold text-uppercase section-title section-title--textured d-inline-block px-3">
                        Documents de <span class="text-judo-red">Santé</span></h2>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100 p-4 border-top border-4 border-dark">
                            <h3 class="h5 fw-bold mb-3 text-dark"><i class="bi bi-person-fill me-2"></i>Je suis Mineur
                            </h3>
                            <p class="text-muted small">Le questionnaire de santé remplace le certificat médical pour
                                les mineurs sous réserve de réponses négatives.</p>
                            <div class="d-grid gap-2 mt-3">
                                <a href="img/Questionnaire Santé Mineur.pdf" download
                                    class="btn btn-outline-dark text-start btn-sm"><i
                                        class="bi bi-download me-2 text-judo-red"></i> Télécharger le questionnaire
                                    santé</a>
                                <a href="img/Attestation Questionnaire Santé Mineur.pdf" download
                                    class="btn btn-outline-dark text-start btn-sm"><i
                                        class="bi bi-download me-2 text-judo-red"></i> Télécharger l'attestation
                                    parentale</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100 p-4 border-top border-4 border-danger">
                            <h3 class="h5 fw-bold mb-3 text-judo-red"><i class="bi bi-person-bounding-box me-2"></i>Je
                                suis Majeur</h3>
                            <p class="text-muted small">Obligatoire pour le renouvellement de licence si le certificat
                                médical précédent a moins de 3 ans.</p>
                            <div class="d-grid gap-2 mt-3">
                                <a href="img/Questionnaire Santé Majeur.pdf" download
                                    class="btn btn-outline-dark text-start btn-sm"><i
                                        class="bi bi-download me-2 text-judo-red"></i> Télécharger le questionnaire
                                    santé</a>
                                <a href="img/Attestation Questionnaire Santé Majeur.pdf" download
                                    class="btn btn-outline-dark text-start btn-sm"><i
                                        class="bi bi-download me-2 text-judo-red"></i> Télécharger l'attestation</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>

        <div class="container my-5">

            <section class="location-section mb-5">
                <div class="text-center mb-5">
                    <h2 class="h1 fw-bold text-uppercase position-relative d-inline-block section-title">
                        Localisation <span class="text-judo-red">Dojo</span>
                    </h2>
                </div>

                <div class="location-card">
                    <div class="row align-items-stretch g-4">
                        <div class="col-lg-5 d-flex flex-column justify-content-center">
                            <div class="location-info-box">
                                <div class="info-icon">
                                    <i class="bi bi-geo-alt-fill"></i>
                                </div>
                                <h3 class="fw-bold mb-3 text-judo-dark">Dojo Teddy Riner</h3>
                                <p class="location-address">
                                    <i class="bi bi-building me-2 text-judo-red"></i>
                                    Complexe sportif Teddy Riner<br>
                                    <span class="ps-4">77720 Mormant, France</span>
                                </p>
                                <p class="location-desc mt-3">
                                    Bienvenue au cœur de notre dojo, où tradition et passion pour le judo se
                                    rencontrent.
                                </p>
                                <a href="https://www.google.com/maps/search/?api=1&query=48.6113915%2C2.8807517"
                                    target="_blank" rel="noopener noreferrer" class="btn btn-judo-red mt-4 w-100">
                                    <i class="bi bi-signpost-2-fill me-2"></i>Ouvrir sur Google Maps
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="map-container">
                                <div class="map-responsive rounded-4 overflow-hidden shadow-lg">
                                    <iframe
                                        src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d2637.919116166964!2d2.8807516999999994!3d48.611391499999996!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zNDjCsDM2JzQxLjAiTiAywrA1Mic1MC43IkU!5e0!3m2!1sfr!2sfr!4v1783070266914!5m2!1sfr!2sfr"
                                        style="border:0;" allowfullscreen="" loading="lazy"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        title="Localisation Dojo Teddy Riner">
                                    </iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>

    </main>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>

