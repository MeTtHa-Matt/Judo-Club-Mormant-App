<?php

require_once __DIR__ . '/session_start_pwa.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/access_check.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$action = trim((string) ($data['action'] ?? ''));
$message = trim((string) ($data['message'] ?? ''));

if (!isset($_SESSION['ia_chat_history']) || !is_array($_SESSION['ia_chat_history'])) {
    $_SESSION['ia_chat_history'] = [];
}

if (!isset($_SESSION['ia_chat_memory']) || !is_string($_SESSION['ia_chat_memory'])) {
    $_SESSION['ia_chat_memory'] = '';
}

if ($action === 'reset') {
    $_SESSION['ia_chat_history'] = [];
    $_SESSION['ia_chat_memory'] = '';
    echo json_encode(['success' => true, 'message' => 'Conversation réinitialisée.']);
    exit;
}

if ($message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Le message est vide.']);
    exit;
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Utilisateur non authentifié.']);
    exit;
}

$userId = (int) $_SESSION['id'];
$stmt = $pdo->prepare('SELECT admin FROM account WHERE id = ?');
$stmt->execute([$userId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$me || (int) $me['admin'] !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès réservé aux administrateurs.']);
    exit;
}

$apiKey = trim((string) getenv('IA_API_KEY'));
if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Clé API IA non configurée.']);
    exit;
}

function buildLightMemory(string $message, string $reply): string
{
    $topic = 'général';

    if (preg_match('/(compte|utilisateur|admin|ban|vérif|email)/i', $message)) {
        $topic = 'comptes';
    } elseif (preg_match('/(compétition|competition|inscription|inscrit|épreuve)/i', $message)) {
        $topic = 'compétitions';
    } elseif (preg_match('/(signal|report|rapport)/i', $message)) {
        $topic = 'signalements';
    } elseif (preg_match('/(enfant|child|profil)/i', $message)) {
        $topic = 'enfants';
    }

    return 'Mémoire légère : sujet courant = ' . $topic . '.';
}

/**
 * Fetch relevant data from database based on question keywords
 */
function fetchRelevantData(PDO $pdo, string $message): string
{
    $lowerMsg = strtolower($message);
    $data = [];

    // Comptes
    if (preg_match('/(compte|utilisateur|admin|vérif|email|ban)/i', $message)) {
        $data[] = "** COMPTES **";
        $data[] = "Total: " . (int) $pdo->query('SELECT COUNT(*) FROM account')->fetchColumn();
        $data[] = "Admins: " . (int) $pdo->query('SELECT COUNT(*) FROM account WHERE admin = 1')->fetchColumn();
        $data[] = "Bannis: " . (int) $pdo->query('SELECT COUNT(*) FROM account WHERE ban = 1')->fetchColumn();
        $data[] = "Non vérifiés: " . (int) $pdo->query('SELECT COUNT(*) FROM account WHERE email_verified = 0')->fetchColumn();
    }

    // Compétitions
    if (preg_match('/(compétition|competition|event|inscription|inscrit)/i', $message)) {
        $data[] = "** COMPÉTITIONS **";
        $data[] = "Total: " . (int) $pdo->query('SELECT COUNT(*) FROM competitions')->fetchColumn();
        
        $nextComp = $pdo->query('SELECT nom, date FROM competitions WHERE date >= CURDATE() ORDER BY date ASC LIMIT 1')->fetch();
        if ($nextComp) {
            $data[] = "Prochaine: " . $nextComp['nom'] . " (" . $nextComp['date'] . ")";
        }
        
        $data[] = "Inscriptions: " . (int) $pdo->query('SELECT COUNT(*) FROM inscrits')->fetchColumn();
    }

    // Enfants
    if (preg_match('/(enfant|child|profil)/i', $message)) {
        $data[] = "** ENFANTS **";
        $data[] = "Profils: " . (int) $pdo->query('SELECT COUNT(*) FROM child_profiles')->fetchColumn();
    }

    // Signalements
    if (preg_match('/(signal|report)/i', $message)) {
        $data[] = "** SIGNALEMENTS **";
        $data[] = "Total: " . (int) $pdo->query('SELECT COUNT(*) FROM signalements_jcm')->fetchColumn();
    }

    // Si aucune catégorie détectée, retourner un résumé compact
    if (empty($data)) {
        $data[] = "Comptes: " . (int) $pdo->query('SELECT COUNT(*) FROM account')->fetchColumn();
        $data[] = "Compétitions: " . (int) $pdo->query('SELECT COUNT(*) FROM competitions')->fetchColumn();
        $data[] = "Inscriptions: " . (int) $pdo->query('SELECT COUNT(*) FROM inscrits')->fetchColumn();
    }

    return implode("\n", $data);
}

function callGroqModel(string $apiKey, array $messages): array
{
    if (!function_exists('curl_init')) {
        return ['error' => 'cURL non disponible.'];
    }

    $payload = json_encode([
        'model' => 'llama-3.1-8b-instant',
        'messages' => $messages,
        'temperature' => 0.5,
        'max_tokens' => 512,
        'top_p' => 1,
        'stream' => false,
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);

    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['error' => 'Erreur de connexion à l\'API.'];
    }

    $decoded = json_decode($response, true);
    if (!$decoded || json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Réponse API invalide.'];
    }

    return ['status' => $httpStatus, 'body' => $decoded];
}

function parseGroqReply(array $response): ?string
{
    if (isset($response['error'])) {
        return null;
    }

    $body = $response['body'];
    if (isset($body['choices'][0]['message']['content'])) {
        return trim($body['choices'][0]['message']['content']);
    }

    return null;
}

// Limite l'historique à 6 messages (3 tours) pour économiser les tokens
$history = array_slice($_SESSION['ia_chat_history'], -6);

// Récupère les données pertinentes de la BD
$dbData = fetchRelevantData($pdo, $message);

// System prompt compact et professionnel
$systemPrompt = <<<'TXT'
Tu es un assistant d'administration pour Judo Club Mormant. Sois concis et professionnel.
- Réponds en français, directement et sans détours
- Utilise les données fournies pour répondre
- Ne dépasse pas 3-4 phrases
- Sois factuel et utile
TXT;

$lightMemory = trim((string) $_SESSION['ia_chat_memory']);
if ($lightMemory !== '') {
    $lightMemory = "\n\nMémoire légère de session :\n" . $lightMemory;
}

$messages = [
    ['role' => 'system', 'content' => $systemPrompt . "\n\nDonnées actuelles :\n" . $dbData . $lightMemory]
];

foreach ($history as $entry) {
    $messages[] = ['role' => $entry['role'], 'content' => $entry['content']];
}

$messages[] = ['role' => 'user', 'content' => $message];

// Enregistre le message utilisateur
$_SESSION['ia_chat_history'][] = ['role' => 'user', 'content' => $message];

// Appel API
$response = callGroqModel($apiKey, $messages);

if (isset($response['error'])) {
    echo json_encode(['success' => false, 'error' => $response['error']]);
    exit;
}

$reply = parseGroqReply($response);
if ($reply === null) {
    echo json_encode(['success' => false, 'error' => 'Impossible de traiter la réponse.']);
    exit;
}

// Mémoire légère et très compacte pour la prochaine interaction
$_SESSION['ia_chat_memory'] = buildLightMemory($message, $reply);

// Enregistre la réponse
$_SESSION['ia_chat_history'][] = ['role' => 'assistant', 'content' => $reply];

echo json_encode(['success' => true, 'reply' => $reply]);
