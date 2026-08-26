<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../../');
$dotenv->load();

function loadEnvOverridesFromDotenv(): void
{
    $envFile = __DIR__ . '/../../.env';
    if (!is_file($envFile)) {
        return;
    }

    $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $separatorPosition = strpos($trimmed, '=');
        if ($separatorPosition === false) {
            continue;
        }

        $key = trim(substr($trimmed, 0, $separatorPosition));
        $value = trim(substr($trimmed, $separatorPosition + 1));

        if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }

        if ($key === '') {
            continue;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

loadEnvOverridesFromDotenv();

function getMailerSettings(): array
{
    $host = trim((string) getEnvValue('MAIL_HOST', ''));
    $port = (int) getEnvValue('MAIL_PORT', '0');
    $username = trim((string) getEnvValue('MAIL_USERNAME', ''));
    $from = trim((string) getEnvValue('MAIL_FROM', ''));
    $fromName = trim((string) getEnvValue('MAIL_FROM_NAME', ''));
    $encryption = strtolower(trim((string) getEnvValue('MAIL_ENCRYPTION', '')));
    $secure = $encryption === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;

    return [
        'host' => $host,
        'port' => $port,
        'username' => $username,
        'from' => $from,
        'fromName' => $fromName,
        'secure' => $secure,
        'encryption' => $encryption,
    ];
}

function testMailerConfiguration(): array
{
    $issues = [];
    $settings = getMailerSettings();

    if (empty(getEnvValue('MAIL_PASSWORD', ''))) {
        $issues[] = 'MAIL_PASSWORD absent ou vide';
    }

    if (!checkdnsrr($settings['host'], 'MX') && !checkdnsrr($settings['host'], 'A')) {
        $issues[] = "Impossible de résoudre le host SMTP ({$settings['host']})";
    }

    $scheme = $settings['secure'] === PHPMailer::ENCRYPTION_SMTPS ? 'ssl' : 'tcp';
    $connection = @stream_socket_client("{$scheme}://{$settings['host']}:{$settings['port']}", $errno, $errstr, 5);
    if ($connection === false) {
        $issues[] = "Impossible de se connecter au SMTP {$settings['host']}:{$settings['port']} - {$errno} {$errstr}";
    } else {
        fclose($connection);
    }

    return $issues;
}

function getApplicationBaseUrl(): string
{
    $envBaseUrl = getenv('APP_BASE_URL');
    if (!empty($envBaseUrl)) {
        return rtrim($envBaseUrl, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

    if (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false || stripos($host, '::1') !== false) {
        return $scheme . '://' . $host . '/JCM-App';
    }

    return $scheme . '://' . $host;
}

function getEnvValue(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value !== false && $value !== null && trim((string) $value) !== '') {
        return trim((string) $value);
    }

    if (isset($_ENV[$key]) && trim((string) $_ENV[$key]) !== '') {
        return trim((string) $_ENV[$key]);
    }

    if (isset($_SERVER[$key]) && trim((string) $_SERVER[$key]) !== '') {
        return trim((string) $_SERVER[$key]);
    }

    $envFile = __DIR__ . '/../../.env';
    if (is_file($envFile)) {
        $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                    continue;
                }

                $separatorPosition = strpos($trimmed, '=');
                if ($separatorPosition === false) {
                    continue;
                }

                $candidateKey = trim(substr($trimmed, 0, $separatorPosition));
                if ($candidateKey !== $key) {
                    continue;
                }

                $candidateValue = trim(substr($trimmed, $separatorPosition + 1));
                if ($candidateValue !== '' && (($candidateValue[0] === '"' && substr($candidateValue, -1) === '"') || ($candidateValue[0] === "'" && substr($candidateValue, -1) === "'"))) {
                    $candidateValue = substr($candidateValue, 1, -1);
                }

                if (trim((string) $candidateValue) !== '') {
                    return trim((string) $candidateValue);
                }
            }
        }
    }

    return $default;
}

function getReportRecipient(): string
{
    $recipient = getEnvValue('MAIL_REPORT_TO');
    if ($recipient !== null && $recipient !== '') {
        return $recipient;
    }

    $envFile = __DIR__ . '/../../.env';
    if (is_file($envFile)) {
        $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                    continue;
                }

                $separatorPosition = strpos($trimmed, '=');
                if ($separatorPosition === false) {
                    continue;
                }

                $key = trim(substr($trimmed, 0, $separatorPosition));
                if ($key !== 'MAIL_REPORT_TO') {
                    continue;
                }

                $value = trim(substr($trimmed, $separatorPosition + 1));
                if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                    $value = substr($value, 1, -1);
                }

                if (trim((string) $value) !== '') {
                    return trim((string) $value);
                }
            }
        }
    }

    return '24mgimenez@gmail.com';
}

function getReportRecipientName(): string
{
    $name = getEnvValue('MAIL_REPORT_NAME');
    if ($name !== null && $name !== '') {
        return $name;
    }

    return 'Signalements JCM';
}

function getMailDomainFromAddress(string $address): string
{
    $parts = explode('@', $address, 2);
    return $parts[1] ?? 'localhost';
}

function sanitizeMailName(string $name): string
{
    return trim(str_replace(["\r", "\n"], '', (string) $name));
}

function configureMailerCharacterEncoding(PHPMailer $mail): void
{
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->setLanguage('fr');
}

function configureSecureSmtpMailer(PHPMailer $mail, array $settings, ?string $replyTo = null, ?string $replyToName = null, bool $isBulk = false): void
{
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true;
    $mail->Host = $settings['host'];
    $mail->Username = $settings['username'];
    $mail->Password = getEnvValue('MAIL_PASSWORD', '');
    $mail->SMTPSecure = $settings['secure'];
    $mail->Port = $settings['port'];
    $mail->SMTPAutoTLS = true;
    $mail->Timeout = 20;
    configureMailerCharacterEncoding($mail);
    $mail->Priority = 3;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ];

    $mail->addCustomHeader('X-Priority', '3');
    $mail->addCustomHeader('X-Content-Type-Options', 'nosniff');
    $mail->addCustomHeader('X-Frame-Options', 'DENY');
    $mail->addCustomHeader('X-XSS-Protection', '1; mode=block');
    $mail->addCustomHeader('Referrer-Policy', 'no-referrer');
    $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
    $mail->addCustomHeader('X-Mailer', 'JCM-App');

    if ($isBulk) {
        $mail->addCustomHeader('Precedence', 'bulk');
        $mail->addCustomHeader('List-Unsubscribe', '<' . getApplicationBaseUrl() . '/contact.php>');
    }

    $dkimDomain = trim((string) getenv('MAIL_DKIM_DOMAIN'));
    $dkimSelector = trim((string) getenv('MAIL_DKIM_SELECTOR'));
    $dkimPrivateKey = trim((string) getenv('MAIL_DKIM_PRIVATE_KEY'));
    $dkimIdentity = trim((string) getenv('MAIL_DKIM_IDENTITY'));
    $dkimPassphrase = trim((string) getenv('MAIL_DKIM_PASSPHRASE'));

    if ($dkimDomain !== '' && $dkimSelector !== '' && $dkimPrivateKey !== '') {
        $mail->DKIM_domain = $dkimDomain;
        $mail->DKIM_private = $dkimPrivateKey;
        $mail->DKIM_selector = $dkimSelector;
        $mail->DKIM_identity = $dkimIdentity !== '' ? $dkimIdentity : $settings['from'];
        $mail->DKIM_passphrase = $dkimPassphrase !== '' ? $dkimPassphrase : '';
        $mail->DKIM_copyHeaderFields = ['From', 'To', 'Cc', 'Subject'];
    }

    $mail->setFrom($settings['from'], $settings['fromName']);
    $mail->Sender = $settings['from'];

    if (!empty($replyTo) && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $mail->addReplyTo($replyTo, sanitizeMailName($replyToName ?: 'Utilisateur'));
    } else {
        $mail->addReplyTo($settings['from'], $settings['fromName']);
    }
}

function sendVerificationEmail($email, $firstname, $token)
{
    $mail = new PHPMailer(true);
    $smtpDebug = [];
    $settings = getMailerSettings();
    $diagnostics = testMailerConfiguration();

    if (!empty($diagnostics)) {
        error_log('Diagnostics mailer : ' . implode(' | ', $diagnostics));
    }

    try {
        $mail->Debugoutput = function ($str, $level) use (&$smtpDebug) {
            $smtpDebug[] = trim($str);
        };

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Adresse email invalide.'];
        }

        configureSecureSmtpMailer($mail, $settings);
        $mail->addAddress($email, sanitizeMailName($firstname));

        $mail->isHTML(true);
        $mail->Subject = 'Confirmez votre adresse email - Judo Club Mormant';

        $link = getApplicationBaseUrl() . '/verify.php?token=' . urlencode($token);
        $safeFirstname = sanitizeMailName($firstname);

        $mail->Body = buildVerificationEmailHtml($safeFirstname, $link);
        $mail->AltBody = "Bonjour $safeFirstname,\n\nConfirmez votre inscription au Judo Club Mormant en ouvrant ce lien :\n$link\n\nCe lien expire dans 24h.";

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        $error = $mail->ErrorInfo ?: $e->getMessage();
        $details = trim(implode(' | ', $smtpDebug));
        if ($details !== '') {
            $error .= ' | debug: ' . $details;
        }

        error_log('Erreur envoi mail vérification : ' . $error);
        return ['success' => false, 'error' => $error];
    }
}

function sendBulkHtmlEmail(array $recipients, string $subject, string $htmlBody, array $embeddedImages = [])
{
    $mail = new PHPMailer(true);
    $smtpDebug = [];
    $settings = getMailerSettings();
    $diagnostics = testMailerConfiguration();

    if (!empty($diagnostics)) {
        error_log('Diagnostics mailer (newsletter) : ' . implode(' | ', $diagnostics));
    }

    if (empty($recipients)) {
        return ['success' => false, 'error' => 'Aucun destinataire disponible.'];
    }

    try {
        $mail->Debugoutput = function ($str, $level) use (&$smtpDebug) {
            $smtpDebug[] = trim($str);
        };

        configureSecureSmtpMailer($mail, $settings);

        foreach ($recipients as $recipient) {
            $email = $recipient['email'] ?? null;
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $name = sanitizeMailName($recipient['name'] ?? '');
            $mail->addAddress($email, $name);
        }

        if (empty($mail->getToAddresses())) {
            return ['success' => false, 'error' => 'Aucune adresse email valide trouvée.'];
        }

        foreach ($embeddedImages as $index => $image) {
            $cid = $image['cid'] ?? 'inline-image-' . $index;
            $mail->addStringEmbeddedImage(
                $image['content'],
                $cid,
                $image['filename'] ?? 'image_' . $index . '.png',
                'base64',
                $image['mime'] ?? 'application/octet-stream'
            );
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody));

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        $error = $mail->ErrorInfo ?: $e->getMessage();
        $details = trim(implode(' | ', $smtpDebug));
        if ($details !== '') {
            $error .= ' | debug: ' . $details;
        }

        error_log('Erreur envoi mail groupe : ' . $error);
        return ['success' => false, 'error' => $error];
    }
}

function sendSiteContactEmail(string $subject, string $htmlBody, ?string $replyTo = null, ?string $replyToName = null): array
{
    $mail = new PHPMailer(true);
    $smtpDebug = [];
    $settings = getMailerSettings();
    $diagnostics = testMailerConfiguration();

    if (!empty($diagnostics)) {
        error_log('Diagnostics mailer (site contact) : ' . implode(' | ', $diagnostics));
    }

    try {
        $mail->Debugoutput = function ($str, $level) use (&$smtpDebug) {
            $smtpDebug[] = trim($str);
        };

        configureSecureSmtpMailer($mail, $settings, $replyTo, $replyToName);

        $reportRecipient = getReportRecipient();
        $reportRecipientName = getReportRecipientName();
        if (!filter_var($reportRecipient, FILTER_VALIDATE_EMAIL)) {
            $reportRecipient = $settings['from'];
            $reportRecipientName = $settings['fromName'];
        }

        $mail->clearAddresses();
        $mail->addAddress($reportRecipient, $reportRecipientName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?/i', "\n", $htmlBody));

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        $error = $mail->ErrorInfo ?: $e->getMessage();
        $details = trim(implode(' | ', $smtpDebug));
        if ($details !== '') {
            $error .= ' | debug: ' . $details;
        }

        error_log('Erreur envoi mail contact site : ' . $error);
        return ['success' => false, 'error' => $error];
    }
}

function sendReportVerificationCodeEmail(string $email, string $code): array
{
    $mail = new PHPMailer(true);
    $settings = getMailerSettings();

    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Adresse email invalide.'];
        }

        configureSecureSmtpMailer($mail, $settings);
        $mail->clearAddresses();
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Votre code de vérification - Judo Club Mormant';
        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $mail->Body = '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#222">'
            . '<h2>Vérification de votre signalement</h2>'
            . '<p>Utilisez ce code pour confirmer votre adresse email :</p>'
            . '<p style="font-size:28px;font-weight:700;letter-spacing:8px;color:#b30000">' . $safeCode . '</p>'
            . '<p>Ce code expire dans 15 minutes. Si vous n’êtes pas à l’origine de cette demande, ignorez cet email.</p>'
            . '</div>';
        $mail->AltBody = "Votre code de vérification pour le signalement est : $code\n\nCe code expire dans 15 minutes.";
        $mail->send();

        return ['success' => true];
    } catch (Exception $e) {
        error_log('Erreur envoi code signalement : ' . ($mail->ErrorInfo ?: $e->getMessage()));
        return ['success' => false, 'error' => 'Impossible d’envoyer le code de vérification.'];
    }
}

function buildVerificationEmailHtml($firstname, $link)
{
    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background-color:#f4f4f5; font-family:'Segoe UI', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5; padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background-color:#000000; padding:28px; text-align:center;">
                            <h1 style="color:#ffffff; margin:0; font-size:22px; letter-spacing:1px;">JUDO CLUB MORMANT</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px;">
                            <h2 style="color:#1a1a1a; font-size:20px; margin-top:0;">Bonjour {$firstname},</h2>
                            <p style="color:#444444; font-size:15px; line-height:1.6;">
                                Merci de vous être inscrit(e) sur l'espace membre du Judo Club Mormant.
                                Pour activer votre compte, confirmez votre adresse email en cliquant sur le bouton ci-dessous.
                            </p>
                            <div style="text-align:center; margin:32px 0;">
                                <a href="{$link}" style="background-color:#b30000; color:#ffffff; text-decoration:none; padding:14px 32px; border-radius:8px; font-weight:bold; font-size:15px; display:inline-block;">
                                    Confirmer mon email
                                </a>
                            </div>
                            <p style="color:#888888; font-size:13px; line-height:1.5;">
                                Ce lien est valable 24 heures. Si vous n'êtes pas à l'origine de cette inscription, ignorez simplement cet email.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f4f4f5; padding:18px; text-align:center;">
                            <p style="color:#999999; font-size:12px; margin:0;">Judo Club Mormant · Ne répondez pas à cet email</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

function sendPasswordResetEmail($email, $firstname, $token)
{
    $mail = new PHPMailer(true);
    $smtpDebug = [];
    $settings = getMailerSettings();
    $diagnostics = testMailerConfiguration();

    if (!empty($diagnostics)) {
        error_log('Diagnostics mailer (reset password) : ' . implode(' | ', $diagnostics));
    }

    try {
        $mail->Debugoutput = function ($str, $level) use (&$smtpDebug) {
            $smtpDebug[] = trim($str);
        };

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Adresse email invalide.'];
        }

        configureSecureSmtpMailer($mail, $settings);
        $mail->addAddress($email, sanitizeMailName($firstname));

        $mail->isHTML(true);
        $mail->Subject = 'Réinitialisation de votre mot de passe - Judo Club Mormant';

        $link = getApplicationBaseUrl() . '/reset_password.php?token=' . urlencode($token);
        $safeFirstname = sanitizeMailName($firstname);

        $mail->Body = buildPasswordResetEmailHtml($safeFirstname, $link);
        $mail->AltBody = "Bonjour $safeFirstname,\n\nVous avez demandé la réinitialisation de votre mot de passe. Ouvrez ce lien pour en choisir un nouveau :\n$link\n\nCe lien expire dans 1 heure. Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.";

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        $error = $mail->ErrorInfo ?: $e->getMessage();
        $details = trim(implode(' | ', $smtpDebug));
        if ($details !== '') {
            $error .= ' | debug: ' . $details;
        }

        error_log('Erreur envoi mail réinitialisation : ' . $error);
        return ['success' => false, 'error' => $error];
    }
}

function buildPasswordResetEmailHtml($firstname, $link)
{
    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background-color:#f4f4f5; font-family:'Segoe UI', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5; padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background-color:#000000; padding:28px; text-align:center;">
                            <h1 style="color:#ffffff; margin:0; font-size:22px; letter-spacing:1px;">JUDO CLUB MORMANT</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px;">
                            <h2 style="color:#1a1a1a; font-size:20px; margin-top:0;">Bonjour {$firstname},</h2>
                            <p style="color:#444444; font-size:15px; line-height:1.6;">
                                Vous avez demandé la réinitialisation de votre mot de passe sur l'espace membre du Judo Club Mormant.
                                Cliquez sur le bouton ci-dessous pour en choisir un nouveau.
                            </p>
                            <div style="text-align:center; margin:32px 0;">
                                <a href="{$link}" style="background-color:#b30000; color:#ffffff; text-decoration:none; padding:14px 32px; border-radius:8px; font-weight:bold; font-size:15px; display:inline-block;">
                                    Réinitialiser mon mot de passe
                                </a>
                            </div>
                            <p style="color:#888888; font-size:13px; line-height:1.5;">
                                Ce lien est valable 1 heure. Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email : votre mot de passe restera inchangé.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f4f4f5; padding:18px; text-align:center;">
                            <p style="color:#999999; font-size:12px; margin:0;">Judo Club Mormant · Ne répondez pas à cet email</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}