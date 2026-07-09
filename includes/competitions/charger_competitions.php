<?php

if (isset($_SESSION['id'])) {
    $stmtAdmin = $pdo->prepare('SELECT admin FROM account WHERE id = ?');
    $stmtAdmin->execute([$_SESSION['id']]);
    $r = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
    $_SESSION['admin'] = (int) ($r['admin'] ?? 0);
}

$mois = isset($_GET['mois']) ? (int) $_GET['mois'] : (int) date('n');
$annee = isset($_GET['annee']) ? (int) $_GET['annee'] : (int) date('Y');

if ($mois < 1) {
    $mois = 12;
    $annee--;
}
if ($mois > 12) {
    $mois = 1;
    $annee++;
}

$moisPrecedent = $mois - 1;
$anneePrecedente = $annee;
if ($moisPrecedent < 1) {
    $moisPrecedent = 12;
    $anneePrecedente--;
}
$moisSuivant = $mois + 1;
$anneeSuivante = $annee;
if
($moisSuivant > 12) {
    $moisSuivant = 1;
    $anneeSuivante++;
}

function getCompetitionUploadDir(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'competitions';
}

function getCompetitionImageUrl(?string $imageName): string
{
    if (empty($imageName)) {
        return '';
    }

    $rootPath = getCompetitionUploadDir() . DIRECTORY_SEPARATOR . $imageName;
    $legacyPath = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'competitions' . DIRECTORY_SEPARATOR . $imageName;

    if (is_file($rootPath)) {
        return 'img/competitions/' . rawurlencode($imageName);
    }

    if (is_file($legacyPath)) {
        return 'includes/competitions/img/competitions/' . rawurlencode($imageName);
    }

    return 'img/competitions/' . rawurlencode($imageName);
}

$nomsMois = [
    1 => 'Janvier',
    2 => 'Février',
    3 => 'Mars',
    4 => 'Avril',
    5 => 'Mai',
    6 => 'Juin',
    7 => 'Juillet',
    8 => 'Août',
    9 => 'Septembre',
    10 => 'Octobre',
    11 => 'Novembre',
    12 => 'Décembre'
];
$nomsJoursCourts = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
$nomsJoursLongs = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

function formatDateFr(string $dateStr): string
{
    global $nomsJoursLongs, $nomsMois;
    $ts = strtotime($dateStr);
    $jourSemaine = $nomsJoursLongs[(int) date('N', $ts) - 1];
    $jour = (int) date('j', $ts);
    $moisN = (int) date('n', $ts);
    $an = date('Y', $ts);
    return "$jourSemaine $jour " . $nomsMois[$moisN] . " $an";
}

function isCompetitionRegistrationOpen(?string $competitionDate, ?string $deadlineDate): bool
{
    if (empty($competitionDate)) {
        return false;
    }

    $today = date('Y-m-d');
    $effectiveDeadline = !empty($deadlineDate) ? $deadlineDate : date('Y-m-d', strtotime($competitionDate . ' -7 days'));

    return $today < $effectiveDeadline && $today < $competitionDate;
}

$premierJourTs = mktime(0, 0, 0, $mois, 1, $annee);
$nbJoursDansMois = (int) date('t', $premierJourTs);
$decalageDebut = (int) date('N', $premierJourTs) - 1; // 0 = mois commence un lundi

$aujourdHui = date('Y-m-d');
$estMoisCourant = ((int) date('n') === $mois && (int) date('Y') === $annee);

$deleteOldCompetitionsStmt = $pdo->prepare(
    "DELETE FROM competitions WHERE date < DATE_SUB(CURDATE(), INTERVAL 3 MONTH)"
);
$deleteOldCompetitionsStmt->execute();

$stmtMois = $pdo->prepare(
    "SELECT c.id, c.nom, c.lieu, c.informations, c.date, c.date_limite_inscription, c.image,
            GROUP_CONCAT(DISTINCT ci.cible ORDER BY ci.cible SEPARATOR ', ') AS cible_nom
        FROM competitions c
        LEFT JOIN (
            SELECT cc.competition_id AS competition_id, ci2.cible AS cible
            FROM competition_cibles cc
            JOIN cible ci2 ON ci2.id = cc.cible_id
            UNION ALL
            SELECT c2.id AS competition_id, ci3.cible AS cible
            FROM competitions c2
            JOIN cible ci3 ON ci3.id = c2.id_cible
            WHERE c2.id_cible IS NOT NULL
        ) ci ON ci.competition_id = c.id
        WHERE MONTH(c.date) = :mois AND YEAR(c.date) = :annee
        AND c.date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        GROUP BY c.id, c.nom, c.lieu, c.informations, c.date, c.date_limite_inscription, c.image
        ORDER BY c.date ASC"
);
$stmtMois->execute(['mois' => $mois, 'annee' => $annee]);
$competitionsMois = $stmtMois->fetchAll(PDO::FETCH_ASSOC);

$competitionsParJour = [];
foreach ($competitionsMois as $comp) {
    $jour = (int) date('j', strtotime($comp['date']));
    $competitionsParJour[$jour][] = $comp;
}

$stmtAVenir = $pdo->prepare(
    "SELECT c.id, c.nom, c.lieu, c.informations, c.date, c.date_limite_inscription, c.image,
            GROUP_CONCAT(DISTINCT ci.cible ORDER BY ci.cible SEPARATOR ', ') AS cible_nom
        FROM competitions c
        LEFT JOIN (
            SELECT cc.competition_id AS competition_id, ci2.cible AS cible
            FROM competition_cibles cc
            JOIN cible ci2 ON ci2.id = cc.cible_id
            UNION ALL
            SELECT c2.id AS competition_id, ci3.cible AS cible
            FROM competitions c2
            JOIN cible ci3 ON ci3.id = c2.id_cible
            WHERE c2.id_cible IS NOT NULL
        ) ci ON ci.competition_id = c.id
        WHERE c.date >= CURDATE()
        GROUP BY c.id, c.nom, c.lieu, c.informations, c.date, c.date_limite_inscription, c.image
        ORDER BY c.date ASC"
);
$stmtAVenir->execute();
$competitionsAVenir = $stmtAVenir->fetchAll(PDO::FETCH_ASSOC);

$stmtCeintures = $pdo->query("SELECT * FROM ceintures ORDER BY id ASC");
$ceintures = $stmtCeintures->fetchAll(PDO::FETCH_ASSOC);