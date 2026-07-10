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

if ($action === 'reset') {
    $_SESSION['ia_chat_history'] = [];
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
    echo json_encode(['success' => false, 'error' => 'Clé API IA non configurée dans le fichier .env.']);
    exit;
}

function buildSchemaContext(PDO $pdo): string
{
    $stmt = $pdo->prepare(
        "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         ORDER BY TABLE_NAME, ORDINAL_POSITION"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tables = [];
    foreach ($rows as $row) {
        $tables[$row['TABLE_NAME']][] = $row['COLUMN_NAME'];
    }

    $lines = [];
    foreach ($tables as $table => $columns) {
        $lines[] = sprintf('%s: %s', $table, implode(', ', $columns));
    }

    return implode("\n", $lines);
}

function buildTranslationContext(): string
{
    return <<<'TXT'
Traduction des noms français de colonnes et tables vers les noms de la base de données:
- compte / comptes / utilisateur(s) -> account
- administrateur(s) / admin -> admin
- banni(s) / bannis -> ban
- email vérifié -> email_verified
- nom -> firstname
- prénom -> lastname
- mot de passe -> password
- enfant(s) / child_profiles -> child_profiles
- ceinture -> id_ceinture
- poids -> Poids
- inscription(s) / inscrits -> inscrits
- compétition(s) -> competitions
- lieu -> lieu
- date limite d'inscription -> date_limite_inscription
- sujet -> subject
- message -> message
- rapport(s) / signalement(s) -> signalements_jcm
TXT;
}

function buildDatabaseSummary(PDO $pdo): string
{
    $summary = [];

    $summary[] = 'Comptes totaux : ' . (int) $pdo->query('SELECT COUNT(*) FROM account')->fetchColumn();
    $summary[] = 'Administrateurs : ' . (int) $pdo->query('SELECT COUNT(*) FROM account WHERE admin = 1')->fetchColumn();
    $summary[] = 'Comptes bannis : ' . (int) $pdo->query('SELECT COUNT(*) FROM account WHERE ban = 1')->fetchColumn();
    $summary[] = 'Comptes non vérifiés : ' . (int) $pdo->query('SELECT COUNT(*) FROM account WHERE email_verified = 0')->fetchColumn();
    $summary[] = 'Compétitions : ' . (int) $pdo->query('SELECT COUNT(*) FROM competitions')->fetchColumn();
    $summary[] = 'Inscriptions compétitions : ' . (int) $pdo->query('SELECT COUNT(*) FROM inscrits')->fetchColumn();
    $summary[] = 'Profils enfants : ' . (int) $pdo->query('SELECT COUNT(*) FROM child_profiles')->fetchColumn();
    $summary[] = 'Signalisations : ' . (int) $pdo->query('SELECT COUNT(*) FROM signalements_jcm')->fetchColumn();
    $summary[] = 'Liens d’accueil : ' . (int) $pdo->query('SELECT COUNT(*) FROM index_links_jcm')->fetchColumn();

    $nextComps = $pdo->prepare('SELECT nom, lieu, date FROM competitions WHERE date >= CURDATE() ORDER BY date ASC LIMIT 3');
    $nextComps->execute();
    $rows = $nextComps->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) > 0) {
        $summary[] = 'Prochaines compétitions : ' . implode(', ', array_map(function ($row) {
            return sprintf('%s (%s, %s)', $row['nom'], $row['lieu'] ?? 'lieu inconnu', $row['date']);
        }, $rows));
    }

    $recentReports = $pdo->prepare(
        'SELECT s.subject, a.firstname, a.lastname
         FROM signalements_jcm s
         JOIN account a ON s.account_id = a.id
         ORDER BY s.created_at DESC
         LIMIT 3'
    );
    $recentReports->execute();
    $reports = $recentReports->fetchAll(PDO::FETCH_ASSOC);
    if (count($reports) > 0) {
        $summary[] = 'Derniers signalements : ' . implode(', ', array_map(function ($row) {
            return sprintf('%s par %s %s', $row['subject'], $row['firstname'], $row['lastname']);
        }, $reports));
    }

    return implode("\n", $summary);
}

function callGroqModel(string $apiKey, string $prompt): array
{
    if (!function_exists('curl_init')) {
        return ['error' => 'L’extension cURL n’est pas disponible sur ce serveur.'];
    }

    $payload = json_encode([
        'input' => [
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'output_text', 'text' => $prompt],
                ],
            ],
        ],
        'temperature' => 0.2,
        'max_output_tokens' => 800,
    ]);

    $ch = curl_init('https://api.groq.com/v1/models/llama-3.1-8b-instant/outputs');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['error' => 'Erreur cURL : ' . $curlError];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Réponse API invalide : ' . json_last_error_msg(), 'raw' => $response];
    }

    return ['status' => $httpStatus, 'body' => $decoded];
}

function parseGroqReply(array $response): ?string
{
    if (isset($response['error'])) {
        return null;
    }

    $body = $response['body'];
    $candidatePaths = [
        ['output', 0, 'content', 0, 'text'],
        ['outputs', 0, 'content', 0, 'text'],
        ['output', 0, 'content', 0, 'message'],
        ['outputs', 0, 'content', 0, 'message'],
    ];

    foreach ($candidatePaths as $path) {
        $value = $body;
        foreach ($path as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                $value = null;
                break;
            }
        }
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }

    return null;
}

$systemPrompt = <<<'TXT'
Tu es un assistant d'administration pour le site Judo Club Mormant. Tu dois répondre en français.
Tu as accès en lecture aux informations de la base de données du site et tu peux utiliser le schéma, les statistiques et les exemples fournis pour répondre aux questions.
Quand l'administrateur demande un nom de colonne en français, traduis-le vers le nom de colonne anglais correspondant dans la base de données.
Ne modifie pas la base de données et ne propose pas de commandes SQL exécutables dans la réponse.
TXT;

$context = buildSchemaContext($pdo);
$summary = buildDatabaseSummary($pdo);
$translations = buildTranslationContext();

$history = $_SESSION['ia_chat_history'];
$history[] = ['role' => 'user', 'content' => $message];
$history = array_slice($history, -10);
$_SESSION['ia_chat_history'] = $history;

$prompt = $systemPrompt
    . "\n\nSchéma de la base de données :\n" . $context
    . "\n\nRésumé rapide de la base de données :\n" . $summary
    . "\n\nGuide de traduction :\n" . $translations
    . "\n\nConversation :\n";

foreach ($history as $entry) {
    if ($entry['role'] === 'user') {
        $prompt .= "Administrateur : " . $entry['content'] . "\n";
    } else {
        $prompt .= "Assistant IA : " . $entry['content'] . "\n";
    }
}

$prompt .= "Assistant IA :";

$response = callGroqModel($apiKey, $prompt);
if (isset($response['error'])) {
    echo json_encode(['success' => false, 'error' => $response['error']]);
    exit;
}

$reply = parseGroqReply($response);
if ($reply === null) {
    $raw = json_encode($response['body'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo json_encode(['success' => false, 'error' => 'Impossible de lire la réponse du modèle.', 'raw' => $raw]);
    exit;
}

$_SESSION['ia_chat_history'][] = ['role' => 'assistant', 'content' => $reply];

echo json_encode(['success' => true, 'reply' => $reply]);
