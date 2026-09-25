<?php
require_once __DIR__ . '/../general/session_start_pwa.php';
require_once __DIR__ . '/../general/db.php';
require_once __DIR__ . '/../general/access_check.php';
require_once __DIR__ . '/family_helpers.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$userId = (int) $_SESSION['id'];
jcm_ensure_family_schema($pdo);
$familyId = jcm_get_or_create_family_for_account($pdo, $userId);
$competitionId = (int) ($_POST['competition_id'] ?? 0);

if ($competitionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Identifiant de compétition manquant.']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT cp.id, cp.firstname, cp.lastname, cp.annee_naissance, cp.Poids, c.ceinture,
        EXISTS(
            SELECT 1 FROM inscrits i
            WHERE i.id_account = ?
              AND i.id_competition = ?
              AND i.nom = cp.firstname
              AND i.prenom = cp.lastname
              AND i.annee_naissance = cp.annee_naissance
              AND i.id_ceinture = cp.id_ceinture
        ) AS registered
     FROM child_profiles cp
     JOIN ceintures c ON cp.id_ceinture = c.id
     WHERE cp.family_id = ?
     ORDER BY cp.lastname, cp.firstname"
);
$stmt->execute([$userId, $competitionId, $familyId]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'children' => $children]);
