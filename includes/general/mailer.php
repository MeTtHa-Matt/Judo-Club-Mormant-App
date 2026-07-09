<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../../');
$dotenv->load();

function getMailerSettings(): array
{
    $host = trim((string) getenv('MAIL_HOST'));
    $port = (int) getenv('MAIL_PORT');
    $username = trim((string) getenv('MAIL_USERNAME'));
    $from = trim((string) getenv('MAIL_FROM'));
    $fromName = trim((string) getenv('MAIL_FROM_NAME'));
    $encryption = strtolower(trim((string) getenv('MAIL_ENCRYPTION')));
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

    if (empty(getenv('MAIL_PASSWORD'))) {
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
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = function ($str, $level) use (&$smtpDebug) {
            $smtpDebug[] = trim($str);
        };
        $mail->Host = $settings['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['username'];
        $mail->Password = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = $settings['secure'];
        $mail->Port = $settings['port'];

        $mail->setFrom($settings['from'], $settings['fromName']);
        $mail->addAddress($email, $firstname);
        $mail->addReplyTo($settings['from'], $settings['fromName']);

        $mail->isHTML(true);
        $mail->Subject = 'Confirmez votre adresse email - Judo Club Mormant';

        $link = getApplicationBaseUrl() . '/verify.php?token=' . urlencode($token);

        $mail->Body = buildVerificationEmailHtml($firstname, $link);
        $mail->AltBody = "Bonjour $firstname,\n\nConfirmez votre inscription au Judo Club Mormant en ouvrant ce lien :\n$link\n\nCe lien expire dans 24h.";

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
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = function ($str, $level) use (&$smtpDebug) {
            $smtpDebug[] = trim($str);
        };
        $mail->Host = $settings['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['username'];
        $mail->Password = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = $settings['secure'];
        $mail->Port = $settings['port'];

        $mail->setFrom($settings['from'], $settings['fromName']);
        $mail->addReplyTo($settings['from'], $settings['fromName']);

        foreach ($recipients as $recipient) {
            $email = $recipient['email'] ?? null;
            if (empty($email)) {
                continue;
            }
            $name = $recipient['name'] ?? '';
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
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = function ($str, $level) use (&$smtpDebug) {
            $smtpDebug[] = trim($str);
        };
        $mail->Host = $settings['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['username'];
        $mail->Password = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = $settings['secure'];
        $mail->Port = $settings['port'];

        $mail->setFrom($settings['from'], $settings['fromName']);
        $mail->addAddress($settings['from'], $settings['fromName']);

        if (!empty($replyTo) && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyTo, $replyToName ?: 'Utilisateur');
        }

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
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = function ($str, $level) use (&$smtpDebug) {
            $smtpDebug[] = trim($str);
        };
        $mail->Host = $settings['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['username'];
        $mail->Password = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = $settings['secure'];
        $mail->Port = $settings['port'];

        $mail->setFrom($settings['from'], $settings['fromName']);
        $mail->addAddress($email, $firstname);
        $mail->addReplyTo($settings['from'], $settings['fromName']);

        $mail->isHTML(true);
        $mail->Subject = 'Réinitialisation de votre mot de passe - Judo Club Mormant';

        $link = getApplicationBaseUrl() . '/reset_password.php?token=' . urlencode($token);

        $mail->Body = buildPasswordResetEmailHtml($firstname, $link);
        $mail->AltBody = "Bonjour $firstname,\n\nVous avez demandé la réinitialisation de votre mot de passe. Ouvrez ce lien pour en choisir un nouveau :\n$link\n\nCe lien expire dans 1 heure. Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.";

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