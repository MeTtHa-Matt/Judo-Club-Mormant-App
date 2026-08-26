<?php

function jcm_csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function jcm_require_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(jcm_csrf_token(), $token)) {
        http_response_code(403);
        exit('Requête invalide.');
    }
}

function jcm_valid_person_name(string $value, int $maxLength = 100): bool
{
    return $value !== '' && mb_strlen($value) <= $maxLength && preg_match("/^[\\p{L} '-]+$/u", $value) === 1;
}

function jcm_valid_https_url(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false
        && (bool) preg_match('#^https://#i', $url)
        && !preg_match('/[\x00-\x20]/', $url);
}

function jcm_rate_limit(string $key, int $limit, int $window): bool
{
    $file = sys_get_temp_dir() . '/jcm-rate-' . hash('sha256', $key) . '.json';
    $handle = fopen($file, 'c+');
    if ($handle === false) {
        return false;
    }

    flock($handle, LOCK_EX);
    $contents = stream_get_contents($handle);
    $data = json_decode($contents ?: '{}', true);
    $now = time();
    $attempts = array_values(array_filter(is_array($data) ? $data : [], static fn($timestamp) => (int) $timestamp > $now - $window));
    $allowed = count($attempts) < $limit;
    if ($allowed) {
        $attempts[] = $now;
    }
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($attempts));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return $allowed;
}

function jcm_require_admin(PDO $pdo): int
{
    $userId = (int) ($_SESSION['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT admin FROM account WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);

    if ($userId <= 0 || (int) $stmt->fetchColumn() !== 1) {
        http_response_code(403);
        exit('Accès refusé.');
    }

    return $userId;
}
