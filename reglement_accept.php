<?php
require_once __DIR__ . '/includes/general/session_start_pwa.php';
require_once __DIR__ . '/includes/general/db.php';
require_once __DIR__ . '/includes/general/access_check.php';
require_once __DIR__ . '/includes/general/notifications.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare('SELECT reglement_accepte FROM account WHERE id = ?');
$stmt->execute([$_SESSION['id']]);
$reglementAccepte = (int) $stmt->fetchColumn();

if ($reglementAccepte === 1) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accepted = isset($_POST['reglement_accepte']) && $_POST['reglement_accepte'] === '1';
    if ($accepted) {
        $update = $pdo->prepare('UPDATE account SET reglement_accepte = 1 WHERE id = ?');
        $update->execute([$_SESSION['id']]);
        $_SESSION['reglement_accepte'] = 1;
        header('Location: index.php?success=' . urlencode('Règlement accepté.'));
        exit;
    }
}
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
    <title>Acceptation du règlement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    <link rel="manifest" href="manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" href="img/jcm.png">
</head>

<body>
    <?php include __DIR__ . '/includes/general/navbar.php' ?>
    <header class="hero-judo hero-judo-compact text-center d-flex align-items-center justify-content-center">
        <div class="hero-pattern"></div>
        <div class="container position-relative text-white py-5">
            <div class="hero-badge">
                <span class="badge-text"><i class="bi bi-file-earmark-text me-2"></i>Règlement</span>
            </div>
            <h1 class="display-4 fw-bolder text-uppercase tracking-wider hero-title">Acceptation du règlement</h1>
            <div class="hero-divider"></div>
            <p class="lead fs-5 fw-light mt-3 hero-subtitle">Veuillez lire et accepter les règlements pour continuer.
            </p>
        </div>
    </header>

    <main class="container my-5 pt-4">
        <section id="reglement" class="mb-5 scroll-margin reglement-section">
            <div class="text-center mb-4">
                <h2 class="h1 fw-bold text-uppercase position-relative d-inline-block section-title">
                    Conditions d'<span class="text-judo-red">Adhésion</span>
                </h2>
            </div>

            <p class="reglement-intro text-center mx-auto mb-5">
                En s'inscrivant au Judo Club Mormant, chaque adhérent et sa famille s'engagent à respecter
                l'intégralité des articles suivants, condition indispensable au bon fonctionnement du club et à la
                sécurité de tous.
            </p>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden reglement-card">
                <ol class="reglement-list">
                    <li class="reglement-item">
                        <span class="reglement-number">1</span>
                        <p class="reglement-text">L'inscription et la cotisation sont obligatoires pour la pratique
                            des activités dispensées au sein du Judo Club Mormant. Les prix et les règles
                            particulières à chaque activité sont fixés par l'assemblée générale. La licence fédérale
                            dont le prix est fixé par la <strong>F.F.J.D.A.</strong> est obligatoire.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">2</span>
                        <p class="reglement-text">Le certificat médical de non contre-indication à la pratique du
                            judo (en compétition pour ceux qui souhaitent en faire) ou le questionnaire santé (si les
                            réponses ou l'âge n'imposent pas un avis médical) est obligatoire pour tous les
                            adhérents.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">3</span>
                        <p class="reglement-text">Les activités sont encadrées par un enseignant diplômé ou des
                            bénévoles en formation.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">4</span>
                        <p class="reglement-text">Les niveaux du groupe compétition sont définis par le club en
                            fonction des résultats en compétitions et relève de la décision du club.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">5</span>
                        <p class="reglement-text">Les possibilités d'entraînements sur l'extérieur et la
                            participation au cours de préparation physique sont assujettis à l'accord du club.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">6</span>
                        <p class="reglement-text">Deux séances d'essai pourront être effectuées avant l'adhésion.</p>
                    </li>
                    <li class="reglement-item reglement-item-highlight">
                        <span class="reglement-number">7</span>
                        <p class="reglement-text">Pour une question d'assurance, l'inscription en ligne doit être
                            finalisée (et remise du certificat médical ou QS Sport) à la fin de cette période d'essai
                            et au plus tard au <strong>1er octobre</strong>. Une assurance complémentaire (RC et DC)
                            est incluse dans l'adhésion afin de couvrir les activités en commun avec d'autres
                            associations partenaires. L'adhésion au J.C.M. implique l'adhésion automatique, sans
                            supplément de coût, au <strong>Warriors Training Club</strong> (et acceptation du
                            règlement intérieur W.T.C.), association multisports avec qui certains créneaux sont
                            partagés.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">8</span>
                        <p class="reglement-text">Le paiement de la cotisation (en 1 ou 3 fois maximum) est à être
                            réglé lors de l'inscription en ligne.</p>
                    </li>
                    <li class="reglement-item reglement-item-highlight">
                        <span class="reglement-number">9</span>
                        <p class="reglement-text">Aucun remboursement ne pourra être effectué.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">10</span>
                        <p class="reglement-text">Les adhérents et leur famille doivent respecter les règles d'accès
                            et d'utilisation relatives aux installations municipales définies par la mairie de
                            Mormant et les règles sanitaires en vigueur. Les tenues (judogi et/ou tenue de sport) et
                            les adhérents doivent respecter les règles d'hygiène et de sécurité.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">11</span>
                        <p class="reglement-text">Les enfants mineurs sont pris en charge par le club à partir du
                            moment où le responsable du créneau horaire les aura pris sous sa responsabilité à
                            l'intérieur du dojo Teddy Riner et autres lieux d'activités. Les parents doivent donc
                            s'assurer que celui-ci est bien présent avant de laisser leurs enfants. De plus, lorsque
                            la fin du créneau horaire est atteinte, les enfants ne sont plus considérés comme étant
                            sous la responsabilité du club, qu'ils soient ou non encore dans l'enceinte du dojo ou
                            tous autres lieux d'activités. La sortie du dojo et de tous autres lieux d'activités se
                            fait donc sous la seule responsabilité des parents quelles que soient les modalités
                            choisies par eux (seul, accompagné par un tiers, ...).</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">12</span>
                        <p class="reglement-text">Les parents et autres accompagnants (autres enfants, ...) ne sont
                            pas admis aux abords du tatamis pendant les horaires de cours. Toutefois ceux-ci peuvent
                            être occasionnellement tolérés (après validation par le responsable du créneau horaire) à
                            condition que leur présence ne nuise pas au bon déroulement des entraînements (le
                            silence est obligatoire), à la sécurité (capacité d'accueil du dojo) et aux règles
                            sanitaires en vigueur.</p>
                    </li>
                    <li class="reglement-item reglement-item-highlight">
                        <span class="reglement-number">13</span>
                        <p class="reglement-text">Les règlements intérieurs (J.C.M. et W.T.C.) ont la même force
                            obligatoire pour tous les adhérents du club. Nul ne pourra s'y soustraire puisqu'
                            implicitement acceptés lors de l'adhésion. L'adhérent et sa famille s'engagent à
                            respecter les règlements intérieurs ainsi que les conditions d'adhésion sous peine
                            d'exclusion.</p>
                    </li>
                </ol>
            </div>
        </section>

        <section id="reglement-app" class="mb-5 scroll-margin reglement-section">
            <div class="text-center mb-4">
                <h2 class="h1 fw-bold text-uppercase position-relative d-inline-block section-title">
                    Règlement de l'<span class="text-judo-red">Application</span>
                </h2>
            </div>

            <p class="reglement-intro text-center mx-auto mb-5">
                L'utilisation de cette application (site web et/ou application mobile) implique l'acceptation sans
                réserve des règles suivantes, garantissant un usage sûr, légal et respectueux du service par tous
                les utilisateurs.
            </p>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden reglement-card">
                <ol class="reglement-list">
                    <li class="reglement-item">
                        <span class="reglement-number">1</span>
                        <p class="reglement-text">L'utilisateur doit avoir l'âge légal requis ou disposer de
                            l'autorisation d'un représentant légal pour créer un compte et utiliser l'application.
                            Les informations fournies lors de l'inscription doivent être exactes, complètes et
                            tenues à jour.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">2</span>
                        <p class="reglement-text">L'utilisateur est responsable de la confidentialité de ses
                            identifiants de connexion (identifiant, mot de passe) et de toute activité effectuée
                            depuis son compte. Toute utilisation frauduleuse ou suspectée doit être signalée sans
                            délai.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">3</span>
                        <p class="reglement-text">Il est interdit d'utiliser l'application à des fins illégales,
                            frauduleuses ou contraires aux bonnes mœurs, ainsi que de tenter d'accéder sans
                            autorisation à des données, comptes ou systèmes ne vous appartenant pas.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">4</span>
                        <p class="reglement-text">Toute tentative de perturbation du bon fonctionnement de
                            l'application (introduction de virus, attaque informatique, extraction massive de
                            données, contournement des mesures de sécurité, etc.) est strictement interdite et
                            pourra faire l'objet de poursuites.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">5</span>
                        <p class="reglement-text">Les contenus publiés ou transmis via l'application (messages,
                            commentaires, documents, images) doivent respecter la loi, les droits d'autrui et ne
                            doivent contenir aucun propos injurieux, diffamatoire, discriminatoire, violent ou
                            portant atteinte à la vie privée d'un tiers.</p>
                    </li>
                    <li class="reglement-item reglement-item-highlight">
                        <span class="reglement-number">6</span>
                        <p class="reglement-text">Les données personnelles collectées sont traitées conformément à
                            la réglementation en vigueur (RGPD). L'utilisateur dispose d'un droit d'accès, de
                            rectification, de suppression et d'opposition au traitement de ses données, qu'il peut
                            exercer auprès du responsable du traitement.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">7</span>
                        <p class="reglement-text">L'éditeur de l'application met en œuvre les moyens raisonnables
                            pour assurer la disponibilité, la sécurité et le bon fonctionnement du service, sans
                            toutefois garantir une disponibilité continue ni l'absence totale d'erreurs ou
                            d'interruptions (maintenance, cas de force majeure, panne technique, etc.).</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">8</span>
                        <p class="reglement-text">L'éditeur se réserve le droit de suspendre ou de supprimer, sans
                            préavis, tout compte ne respectant pas le présent règlement, la loi ou portant atteinte
                            au bon fonctionnement de l'application ou aux droits d'autres utilisateurs.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">9</span>
                        <p class="reglement-text">Les marques, logos, textes, images et autres éléments présents sur
                            l'application sont protégés par le droit de la propriété intellectuelle. Toute
                            reproduction ou utilisation non autorisée est interdite.</p>
                    </li>
                    <li class="reglement-item reglement-item-highlight">
                        <span class="reglement-number">10</span>
                        <p class="reglement-text">Le présent règlement peut être modifié à tout moment afin de
                            l'adapter aux évolutions légales, techniques ou fonctionnelles de l'application. Les
                            utilisateurs seront informés de toute modification substantielle et pourront être
                            invités à en accepter à nouveau les termes.</p>
                    </li>
                    <li class="reglement-item">
                        <span class="reglement-number">11</span>
                        <p class="reglement-text">En cas de désaccord persistant avec ces conditions, l'utilisateur
                            est invité à cesser toute utilisation de l'application et à en informer les
                            administrateurs afin que son compte soit désactivé.</p>
                    </li>
                </ol>
            </div>
        </section>

        <div class="card border-0 shadow-sm rounded-4 p-4">
            <form method="post" action="reglement_accept.php">
                <div class="bo-cible-option mb-4">
                    <input class="form-check-input" type="checkbox" value="1" id="reglementAcceptCheckbox"
                        name="reglement_accepte">
                    <label class="form-check-label" for="reglementAcceptCheckbox">
                        J'ai lu et accepté ce règlement
                    </label>
                </div>
                <button id="submitReglementBtn" type="submit" class="btn btn-judo-red w-100" disabled>Envoyer</button>
            </form>
        </div>
    </main>

    <?php include __DIR__ . '/includes/general/footer.php'; ?>
    <script>
        const checkbox = document.getElementById('reglementAcceptCheckbox');
        const submitBtn = document.getElementById('submitReglementBtn');
        if (checkbox && submitBtn) {
            checkbox.addEventListener('change', function () {
                submitBtn.disabled = !checkbox.checked;
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>