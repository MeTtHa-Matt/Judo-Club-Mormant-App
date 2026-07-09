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
