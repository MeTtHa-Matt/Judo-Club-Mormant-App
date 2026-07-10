<?php

global $pdo;

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../../');
$dotenv->load();

$dbHost = trim((string) getenv('DB_HOST'));
$dbName = trim((string) getenv('DB_NAME'));
$dbUser = trim((string) getenv('DB_USER'));
$dbPassword = trim((string) getenv('DB_PASSWORD'));

if ($dbHost === '' || $dbName === '' || $dbUser === '' || $dbPassword === '') {
    die('Configuration de base de données absente dans le fichier .env.');
}

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

function cleanupExpiredUnverifiedAccounts(PDO $pdo): int
{
    $stmt = $pdo->prepare(
        "DELETE FROM account
         WHERE email_verified = 0
           AND verification_token IS NOT NULL
           AND verification_token_expires IS NOT NULL
           AND verification_token_expires < NOW()"
    );
    $stmt->execute();

    return $stmt->rowCount();
}
