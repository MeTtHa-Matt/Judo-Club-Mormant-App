<?php

declare(strict_types=1);

const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';
const INDEXNOW_KEY_FILE = __DIR__ . '/7996da0f364b4dada8f988e2919a625d.txt';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Ce script doit etre execute en ligne de commande.\n");
}

$urls = array_values(array_filter(array_slice($argv, 1), static fn($url): bool => is_string($url) && $url !== ''));

if ($urls === []) {
    fwrite(STDERR, "Usage : php indexnow.php https://www.exemple.fr/page [https://www.exemple.fr/autre-page]\n");
    exit(1);
}

if (count($urls) > 10000) {
    fwrite(STDERR, "IndexNow accepte au maximum 10 000 URL par requete.\n");
    exit(1);
}

if (!is_readable(INDEXNOW_KEY_FILE)) {
    fwrite(STDERR, "Fichier de cle IndexNow introuvable ou illisible.\n");
    exit(1);
}

$key = trim((string) file_get_contents(INDEXNOW_KEY_FILE));
if (!preg_match('/^[a-f0-9]{8,128}$/i', $key)) {
    fwrite(STDERR, "La cle IndexNow doit contenir uniquement des caracteres hexadecimaux.\n");
    exit(1);
}

$parsedFirstUrl = parse_url($urls[0]);
$host = is_array($parsedFirstUrl) ? ($parsedFirstUrl['host'] ?? '') : '';
if (($parsedFirstUrl['scheme'] ?? '') !== 'https' || !is_string($host) || $host === '') {
    fwrite(STDERR, "Chaque URL doit etre une URL HTTPS valide.\n");
    exit(1);
}

foreach ($urls as $url) {
    $parsedUrl = parse_url($url);
    $urlHost = is_array($parsedUrl) ? ($parsedUrl['host'] ?? '') : '';
    if (($parsedUrl['scheme'] ?? '') !== 'https' || $urlHost !== $host || filter_var($url, FILTER_VALIDATE_URL) === false) {
        fwrite(STDERR, "Toutes les URL doivent etre HTTPS, valides et appartenir au meme hote.\n");
        exit(1);
    }
}

$payload = json_encode([
    'host' => $host,
    'key' => $key,
    'keyLocation' => 'https://' . $host . '/' . basename(INDEXNOW_KEY_FILE),
    'urlList' => $urls,
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

$curl = curl_init(INDEXNOW_ENDPOINT);
if ($curl === false) {
    fwrite(STDERR, "Impossible d'initialiser cURL.\n");
    exit(1);
}

curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json; charset=utf-8',
        'Content-Length: ' . strlen($payload),
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($curl);
$curlError = curl_error($curl);
$statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);

if ($response === false) {
    fwrite(STDERR, "Erreur lors de l'envoi IndexNow : {$curlError}\n");
    exit(1);
}

if ($statusCode < 200 || $statusCode >= 300) {
    fwrite(STDERR, "IndexNow a retourne le statut HTTP {$statusCode}.\n");
    fwrite(STDERR, trim($response) . "\n");
    exit(1);
}

printf("Requete IndexNow envoyee pour %d URL (HTTP %d).\n", count($urls), $statusCode);