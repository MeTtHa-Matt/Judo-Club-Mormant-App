<?php
require_once __DIR__ . "/includes/general/access_check.php";
require_once __DIR__ . "/includes/general/notifications.php";
include __DIR__ . '/includes/ceintures/tableaux_ceintures.php';
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
    <title>Passages de Ceintures</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon.png">`r`n    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">`r`n    <link rel="apple-touch-icon" href="/apple-touch-icon.png">`r`n    <link rel="icon" type="image/png" sizes="32x32" href="img/jcm.png">`r`n    <link rel="icon" type="image/png" sizes="192x192" href="img/jcm.png">`r`n    <link rel="shortcut icon" href="img/jcm.png">`r`n    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" href="img/jcm.png">
</head>

<body>
    <?php include __DIR__ . "/includes/general/navbar.php" ?>

    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-award me-2"></i>Programme technique</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider animate-fade-in hero-title">Passages de
                Ceintures</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-4 fw-light mt-4 hero-subtitle">Judo Club de Mormant</p>
        </div>
    </header>

    <main class="container my-5 pt-4">

        <section class="mb-5 scroll-margin">
            <div class="text-center mb-4">
                <h2 class="h1 fw-bold text-uppercase position-relative d-inline-block section-title">
                    Choisis ta <span class="text-judo-red">Ceinture</span>
                </h2>
            </div>
            <p class="reglement-intro text-center mx-auto mb-5">
                Clique sur une ceinture pour découvrir le programme technique, les questions de culture judo et les
                vidéos associées à chaque niveau.
            </p>

            <div class="row g-3 g-md-4 ceinture-grid">
                <?php foreach ($ceintures as $belt): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <button type="button" class="ceinture-btn" data-bs-toggle="modal"
                            data-bs-target="#modal-<?= $belt['id'] ?>">
                            <span class="ceinture-swatch">
                                <img src="img/ceintures/<?= $belt['image'] ?>" alt="Ceinture <?= $belt['nom'] ?>"
                                    loading="lazy">
                            </span>
                            <span class="ceinture-name"><?= $belt['nom'] ?></span>
                            <span class="ceinture-cta">Voir le programme <i class="bi bi-arrow-right-short"></i></span>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </main>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>

    <?php foreach ($ceintures as $belt): ?>
        <div class="modal fade modal-ceinture" id="modal-<?= $belt['id'] ?>" tabindex="-1"
            aria-labelledby="modal-<?= $belt['id'] ?>-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="d-flex align-items-center gap-3">
                            <span class="modal-ceinture-thumb">
                                <img src="img/ceintures/<?= $belt['image'] ?>" alt="Ceinture <?= $belt['nom'] ?>">
                            </span>
                            <h2 class="modal-title h4 mb-0" id="modal-<?= $belt['id'] ?>-label">Ceinture
                                <?= $belt['nom'] ?>
                            </h2>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-0">
                        <ul class="nav nav-tabs ceinture-tabs px-3 pt-3" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab"
                                    data-bs-target="#<?= $belt['id'] ?>-technique" type="button" role="tab">
                                    <i class="bi bi-clipboard2-check me-1"></i>Technique
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#<?= $belt['id'] ?>-culture"
                                    type="button" role="tab">
                                    <i class="bi bi-book me-1"></i>Culture Judo
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#<?= $belt['id'] ?>-videos"
                                    type="button" role="tab">
                                    <i class="bi bi-youtube me-1"></i>Vidéos
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content p-4">

                            <div class="tab-pane fade show active" id="<?= $belt['id'] ?>-technique" role="tabpanel">
                                <div class="tech-section">
                                    <h3 class="tech-section-title"><i class="bi bi-hand-index-thumb-fill"></i> Prises
                                        et immobilisations</h3>
                                    <div class="tech-list">
                                        <?php foreach ($belt['prises'] as $item): ?>
                                            <span class="tech-badge"><?= $item ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="tech-section">
                                    <h3 class="tech-section-title"><i class="bi bi-arrow-repeat"></i> Retournements
                                    </h3>
                                    <div class="tech-list">
                                        <?php foreach ($belt['retournements'] as $item): ?>
                                            <span class="tech-badge"><?= $item ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="tech-section">
                                    <h3 class="tech-section-title"><i class="bi bi-lightning-charge-fill"></i>
                                        Situations de travail</h3>
                                    <div class="tech-list">
                                        <?php foreach ($belt['situations'] as $item): ?>
                                            <span class="tech-badge"><?= $item ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="<?= $belt['id'] ?>-culture" role="tabpanel">
                                <div class="accordion culture-accordion" id="accordion-<?= $belt['id'] ?>">
                                    <?php foreach ($belt['culture'] as $i => $qa): ?>
                                        <div class="accordion-item">
                                            <h4 class="accordion-header" id="heading-<?= $belt['id'] ?>-<?= $i ?>">
                                                <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>"
                                                    type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapse-<?= $belt['id'] ?>-<?= $i ?>"
                                                    aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>"
                                                    aria-controls="collapse-<?= $belt['id'] ?>-<?= $i ?>">
                                                    <?= $qa['q'] ?>
                                                </button>
                                            </h4>
                                            <div id="collapse-<?= $belt['id'] ?>-<?= $i ?>"
                                                class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>"
                                                aria-labelledby="heading-<?= $belt['id'] ?>-<?= $i ?>"
                                                data-bs-parent="#accordion-<?= $belt['id'] ?>">
                                                <div class="accordion-body">
                                                    <i class="bi bi-check-circle-fill text-judo-red me-2"></i><?= $qa['r'] ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="<?= $belt['id'] ?>-videos" role="tabpanel">
                                <div class="video-group">
                                    <h3 class="tech-section-title"><i class="bi bi-emoji-dizzy"></i> Maîtrise des
                                        chutes</h3>
                                    <div class="video-pills">
                                        <?php foreach ($belt['videos']['chutes'] as $n => $url): ?>
                                            <a href="<?= $url ?>" target="_blank" rel="noopener noreferrer" class="video-pill">
                                                <i class="bi bi-play-circle-fill"></i> Vidéo <?= $n + 1 ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php foreach ($belt['videos']['prises'] as $categorie => $urls): ?>
                                    <div class="video-group">
                                        <h3 class="tech-section-title"><i class="bi bi-camera-reels-fill"></i>
                                            <?= $categorie ?>
                                        </h3>
                                        <div class="video-pills">
                                            <?php foreach ($urls as $n => $url): ?>
                                                <a href="<?= $url ?>" target="_blank" rel="noopener noreferrer" class="video-pill">
                                                    <i class="bi bi-play-circle-fill"></i> Vidéo <?= $n + 1 ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>

