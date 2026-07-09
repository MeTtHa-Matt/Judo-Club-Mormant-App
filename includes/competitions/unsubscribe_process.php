<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['id'];
$inscriptionId = (int) ($_POST['id_inscrit'] ?? 0);
$competitionId = (int) ($_POST['id_competition'] ?? 0);
$isAdmin = isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1;

if ($inscriptionId <= 0 || $competitionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

$stmt = $pdo->prepare("SELECT id_account, id_competition FROM inscrits WHERE id = ?");
$stmt->execute([$inscriptionId]);
$inscription = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inscription) {
    echo json_encode(['success' => false, 'message' => 'Inscription introuvable']);
    exit;
}

if (!$isAdmin && (int) $inscription['id_account'] !== $userId) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ((int) $inscription['id_competition'] !== $competitionId) {
    echo json_encode(['success' => false, 'message' => 'Compétition incohérente']);
    exit;
}

$stmt = $pdo->prepare("SELECT date, date_limite_inscription FROM competitions WHERE id = ?");
$stmt->execute([$competitionId]);
$competitionData = $stmt->fetch(PDO::FETCH_ASSOC);
$competitionDate = $competitionData['date'] ?? null;
$registrationDeadline = $competitionData['date_limite_inscription'] ?? null;

if (!$isAdmin) {
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $effectiveDeadline = !empty($registrationDeadline)
        ? $registrationDeadline
        : date('Y-m-d', strtotime($competitionDate . ' -7 days'));

    if ($today >= $effectiveDeadline || $today >= $competitionDate) {
        echo json_encode([
            'success' => false,
            'deadline_passed' => true,
            'message' => 'La date butoir de désinscription est dépassée.',
        ]);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("DELETE FROM inscrits WHERE id = ? AND id_competition = ?");
    $stmt->execute([$inscriptionId, $competitionId]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
