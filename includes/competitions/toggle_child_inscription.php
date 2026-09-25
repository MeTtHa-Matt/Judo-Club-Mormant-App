<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
require_once __DIR__ . '/../general/access_check.php';
require_once __DIR__ . '/../general/security.php';
require_once __DIR__ . '/../account/family_helpers.php';
jcm_require_csrf();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$userId = (int) $_SESSION['id'];
jcm_ensure_family_schema($pdo);
$familyId = jcm_get_or_create_family_for_account($pdo, $userId);
$competitionId = (int) ($_POST['competition_id'] ?? 0);
$childId = (int) ($_POST['child_id'] ?? 0);
$adminCheck = $pdo->prepare('SELECT admin FROM account WHERE id = ? LIMIT 1');
$adminCheck->execute([$userId]);
$isAdmin = (int) $adminCheck->fetchColumn() === 1;
$_SESSION['admin'] = $isAdmin ? 1 : 0;

if ($competitionId <= 0 || $childId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
    exit;
}

$stmt = $pdo->prepare("SELECT firstname, lastname, annee_naissance, id_ceinture, Poids FROM child_profiles WHERE id = ? AND family_id = ?");
$stmt->execute([$childId, $familyId]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    echo json_encode(['success' => false, 'message' => 'Profil enfant introuvable.']);
    exit;
}

$stmt = $pdo->prepare("SELECT date, date_limite_inscription FROM competitions WHERE id = ?");
$stmt->execute([$competitionId]);
$competition = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$competition) {
    echo json_encode(['success' => false, 'message' => 'Compétition introuvable.']);
    exit;
}

$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$effectiveDeadline = !empty($competition['date_limite_inscription'])
    ? $competition['date_limite_inscription']
    : date('Y-m-d', strtotime($competition['date'] . ' -7 days'));

if (!$isAdmin && ($today >= $effectiveDeadline || $today >= $competition['date'])) {
    echo json_encode([
        'success' => false,
        'deadline_passed' => true,
        'message' => 'Les inscriptions et désinscriptions sont fermées pour cette compétition.',
    ]);
    exit;
}

$checkQuery = "SELECT id FROM inscrits WHERE id_account = ? AND id_competition = ? AND nom = ? AND prenom = ? AND annee_naissance = ? AND id_ceinture = ?";
$params = [$userId, $competitionId, $child['firstname'], $child['lastname'], $child['annee_naissance'], $child['id_ceinture']];

$stmt = $pdo->prepare($checkQuery);
$stmt->execute($params);
$existing = $stmt->fetchColumn();

if ($existing) {
    $delete = $pdo->prepare("DELETE FROM inscrits WHERE id = ?");
    $delete->execute([$existing]);
    $remaining = $pdo->prepare("SELECT 1 FROM inscrits WHERE id_account = ? AND id_competition = ? LIMIT 1");
    $remaining->execute([$userId, $competitionId]);
    echo json_encode([
        'success' => true,
        'registered' => false,
        'has_inscription' => (bool) $remaining->fetchColumn(),
    ]);
    exit;
}

if ($child['Poids'] === null || $child['Poids'] === '') {
    echo json_encode(['success' => false, 'message' => 'Le poids de cet enfant doit être renseigné avant l’inscription.']);
    exit;
}

try {
    $insert = $pdo->prepare("INSERT INTO inscrits (nom, prenom, annee_naissance, id_ceinture, Poids, id_account, id_competition) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert->execute([
        $child['firstname'],
        $child['lastname'],
        $child['annee_naissance'],
        $child['id_ceinture'],
        $child['Poids'],
        $userId,
        $competitionId,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Impossible de modifier cette inscription pour le moment.']);
    exit;
}

$hasInscription = $pdo->prepare("SELECT 1 FROM inscrits WHERE id_account = ? AND id_competition = ? LIMIT 1");
$hasInscription->execute([$userId, $competitionId]);

echo json_encode([
    'success' => true,
    'registered' => true,
    'has_inscription' => (bool) $hasInscription->fetchColumn(),
]);
