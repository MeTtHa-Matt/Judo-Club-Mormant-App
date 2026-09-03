<?php

declare(strict_types=1);

const INDEXNOW_WATCH_STATE = __DIR__ . '/storage/indexnow-state.json';
const INDEXNOW_SENDER = __DIR__ . '/indexnow.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Ce script doit etre execute en ligne de commande.\n");
}

$siteUrl = rtrim((string) getenv('INDEXNOW_SITE_URL'), '/');
$force = in_array('--force', $argv, true);
$dryRun = in_array('--dry-run', $argv, true);

if ($siteUrl === '' || filter_var($siteUrl, FILTER_VALIDATE_URL) === false || !str_starts_with($siteUrl, 'https://')) {
    fwrite(STDERR, "Definissez INDEXNOW_SITE_URL avec l'URL HTTPS du site.\n");
    exit(1);
}

$lockHandle = fopen(INDEXNOW_WATCH_STATE . '.lock', 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Une autre verification IndexNow est deja en cours.\n");
    exit(0);
}

$publicPages = [
    'index.php',
    'competitions.php',
    'ceintures.php',
    'reglement.php',
    'login.php',
    'register.php',
    'recup_mdp.php',
];

$ignoredDirectories = ['.git', 'storage', 'vendor'];
$watchedFiles = [];
$directoryIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS)
);

foreach ($directoryIterator as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }

    $relativePath = str_replace('\\', '/', substr($fileInfo->getPathname(), strlen(__DIR__) + 1));
    $pathParts = explode('/', $relativePath);
    if (array_intersect($ignoredDirectories, $pathParts) !== []) {
        continue;
    }

    $extension = strtolower((string) pathinfo($relativePath, PATHINFO_EXTENSION));
    if (in_array($extension, ['php', 'css', 'js', 'json', 'webmanifest'], true)) {
        $watchedFiles[$relativePath] = $fileInfo->getMTime() . ':' . $fileInfo->getSize();
    }
}

$previousState = [];
if (is_readable(INDEXNOW_WATCH_STATE)) {
    $decodedState = json_decode((string) file_get_contents(INDEXNOW_WATCH_STATE), true);
    if (is_array($decodedState)) {
        $previousState = $decodedState;
    }
}

$changedFiles = array_keys(array_diff_assoc($watchedFiles, $previousState));
$deletedFiles = array_keys(array_diff_assoc($previousState, $watchedFiles));
$hasChanges = $force || $previousState === [] || $changedFiles !== [] || $deletedFiles !== [];

if (!$hasChanges) {
    echo "Aucune modification detectee.\n";
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    exit(0);
}

$changedPublicPages = array_values(array_intersect($changedFiles, $publicPages));
$hasSharedChange = $force
    || $previousState === []
    || $deletedFiles !== []
    || count($changedPublicPages) !== count($changedFiles);
$pagesToSubmit = $hasSharedChange ? $publicPages : $changedPublicPages;
$urls = array_map(static fn(string $page): string => $siteUrl . '/' . $page, $pagesToSubmit);

if ($dryRun) {
    printf("%d URL(s) seraient envoyee(s).\n", count($urls));
    foreach ($changedFiles as $changedFile) {
        echo "- {$changedFile}\n";
    }
} else {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(INDEXNOW_SENDER);
    foreach ($urls as $url) {
        $command .= ' ' . escapeshellarg($url);
    }

    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        exit($exitCode);
    }
}

$stateDirectory = dirname(INDEXNOW_WATCH_STATE);
if (!is_dir($stateDirectory) && !mkdir($stateDirectory, 0750, true) && !is_dir($stateDirectory)) {
    fwrite(STDERR, "Impossible de creer le dossier d'etat IndexNow.\n");
    exit(1);
}

$temporaryState = INDEXNOW_WATCH_STATE . '.tmp';
file_put_contents($temporaryState, json_encode($watchedFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX);
rename($temporaryState, INDEXNOW_WATCH_STATE);

flock($lockHandle, LOCK_UN);
fclose($lockHandle);