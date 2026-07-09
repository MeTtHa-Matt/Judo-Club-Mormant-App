<?php
function get_secure_domain() {
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    
    // Supprime le port s'il y en a un
    $host = explode(':', $host)[0];
    
    // Force le domaine principal (ex: judo-club.com au lieu de www.judo-club.com)
    // Cela garantit que le cookie fonctionne sur ALL les sous-domaines
    if (strpos($host, 'www.') === 0) {
        $host = substr($host, 4); // Enlève "www."
    }
    
    return $host;
}

$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            || $_SERVER['SERVER_PORT'] == 443
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

function get_persistent_login_cookie_name(): string {
    return 'persistent_login_jcm';
}

function get_cookie_domain(): string {
    $secure_host = get_secure_domain();
    if ($secure_host === 'localhost' || strpos($secure_host, '.') === false) {
        return '';
    }

    return '.' . $secure_host;
}

function get_persistent_login_cookie_options(): array {
    global $is_https;

    return [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => '/',
        'domain' => get_cookie_domain(),
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function set_persistent_login_cookie(string $token): void {
    setcookie(get_persistent_login_cookie_name(), $token, get_persistent_login_cookie_options());
}

function clear_persistent_login_cookie(): void {
    $options = get_persistent_login_cookie_options();
    setcookie(get_persistent_login_cookie_name(), '', array_merge($options, ['expires' => time() - 3600]));
}

function require_database_connection(): void {
    global $pdo;

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        require_once __DIR__ . '/db.php';
    }

    if (isset($pdo) && $pdo instanceof PDO) {
        $GLOBALS['pdo'] = $pdo;
    }
}

function ensure_persistent_tokens_table_exists(PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS persistent_tokens_jcm (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            account_id INT NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            user_agent VARCHAR(255),
            ip_address VARCHAR(45),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            last_used DATETIME DEFAULT NULL,
            FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE CASCADE,
            INDEX (token),
            INDEX (account_id)
        )'
    );
}

function create_persistent_login_token(PDO $pdo, int $accountId): void {
    ensure_persistent_tokens_table_exists($pdo);

    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $ipAddress = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);

    $stmt = $pdo->prepare(
        'INSERT INTO persistent_tokens_jcm (account_id, token, user_agent, ip_address, expires_at) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$accountId, $token, $userAgent, $ipAddress, $expiresAt]);
    set_persistent_login_cookie($token);
}

function clear_persistent_login_token(PDO $pdo, ?string $token): void {
    if (empty($token) || !is_string($token)) {
        return;
    }

    $stmt = $pdo->prepare('DELETE FROM persistent_tokens_jcm WHERE token = ?');
    $stmt->execute([$token]);
    clear_persistent_login_cookie();
}

function restore_session_from_persistent_login(): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    if (!empty($_SESSION['id'])) {
        return false;
    }

    $token = $_COOKIE[get_persistent_login_cookie_name()] ?? null;
    if (empty($token) || !is_string($token)) {
        return false;
    }

    require_database_connection();
    ensure_persistent_tokens_table_exists($GLOBALS['pdo']);

    $stmt = $GLOBALS['pdo']->prepare(
        'SELECT t.account_id, a.firstname, a.lastname, a.pdp, a.admin
         FROM persistent_tokens_jcm AS t
         JOIN account AS a ON a.id = t.account_id
         WHERE t.token = ? AND t.expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        clear_persistent_login_token($GLOBALS['pdo'], $token);
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['id'] = (int) $row['account_id'];
    $_SESSION['firstname'] = $row['firstname'];
    $_SESSION['lastname'] = $row['lastname'];
    $_SESSION['pdp'] = !empty($row['pdp']) ? basename($row['pdp']) : 'pdp_base.png';
    $_SESSION['admin'] = (int) ($row['admin'] ?? 0);

    $newExpiresAt = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);
    $stmt = $GLOBALS['pdo']->prepare(
        'UPDATE persistent_tokens_jcm SET last_used = NOW(), expires_at = ? WHERE token = ?'
    );
    $stmt->execute([$newExpiresAt, $token]);
    set_persistent_login_cookie($token);

    return true;
}

if (session_status() === PHP_SESSION_NONE) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    session_cache_limiter('nocache');

    session_set_cookie_params([
        'lifetime'   => 60 * 60 * 24 * 30, 
        'path'       => '/',
        'domain'     => get_cookie_domain(),
        'secure'     => $is_https,
        'httponly'   => true,
        'samesite'   => 'Lax',
    ]);

    session_start();
    restore_session_from_persistent_login();
}
?>
