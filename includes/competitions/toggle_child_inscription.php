<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$userId = (int) $_SESSION['id'];
$competitionId = (int) ($_POST['competition_id'] ?? 0);
$childId = (int) ($_POST['child_id'] ?? 0);

if ($competitionId <= 0 || $childId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
    exit;
}

$stmt = $pdo->prepare("SELECT firstname, lastname, annee_naissance, id_ceinture, Poids FROM child_profiles WHERE id = ? AND account_id = ?");
$stmt->execute([$childId, $userId]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    echo json_encode(['success' => false, 'message' => 'Profil enfant introuvable.']);
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
    echo json_encode(['success' => true, 'registered' => false]);
    exit;
}
$isAdmin = isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1;

if (!$existing && !$isAdmin) {
    $stmt = $pdo->prepare("SELECT date FROM competitions WHERE id = ?");
    $stmt->execute([$competitionId]);
    $competitionDate = $stmt->fetchColumn();

    if (!$competitionDate) {
        echo json_encode(['success' => false, 'message' => 'Compétition introuvable.']);
        exit;
    }

    $today = new DateTimeImmutable('today');
    $limitDate = (new DateTimeImmutable($competitionDate))->sub(new DateInterval('P7D'));
    if ($today >= $limitDate) {
        echo json_encode([
            'success' => false,
            'message' => "L'inscription est fermée une semaine avant la date de la compétition.",
        ]);
        exit;
    }
}
$insert = $pdo->prepare("INSERT INTO inscrits (nom, prenom, annee_naissance, id_ceinture, Poids, id_account, id_competition) VALUES (?, ?, ?, ?, ?, ?, ?)");
$insert->execute([
    $child['firstname'],
    $child['lastname'],
    $child['annee_naissance'],
    $child['id_ceinture'],
    $child['Poids'] !== null ? $child['Poids'] : null,
    $userId,
    $competitionId,
]);

echo json_encode(['success' => true, 'registered' => true]);
