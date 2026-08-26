<?php
require_once __DIR__ . '/session_start_pwa.php';
require_once __DIR__ . '/mailer.php';

$flashSuccess = null;
$flashError = null;
$subject = trim((string) ($_SESSION['report_draft']['subject'] ?? ''));
$message = trim((string) ($_SESSION['report_draft']['message'] ?? ''));
$step = isset($_SESSION['report_verification']) ? 'verify' : 'report';
$reportsFile = __DIR__ . '/../../storage/reports/reports.json';
$deviceKey = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));

function readReportData(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function writeReportData(string $file, array $data): bool
{
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $csrf = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['report_csrf'] ?? '', $csrf)) {
        $flashError = 'Votre session a expiré. Rechargez la page puis réessayez.';
    } elseif (!empty($_POST['website'])) {
        $flashError = 'La demande n’a pas pu être traitée.';
    } elseif ($action === 'request_code') {
        $subject = substr(trim((string) ($_POST['subject'] ?? '')), 0, 150);
        $message = trim((string) ($_POST['message'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $rateFile = __DIR__ . '/../../storage/reports/rate_limits.json';
        $rateData = readReportData($rateFile);
        $now = time();
        $recent = array_values(array_filter($rateData, static fn($entry) => ($entry['expires'] ?? 0) > $now));
        $deviceRequests = count(array_filter($recent, static fn($entry) => ($entry['device'] ?? '') === $deviceKey));

        if ($message === '' || strlen($message) < 10) {
            $flashError = 'Décrivez le problème en au moins 10 caractères.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $flashError = 'Renseignez une adresse email valide.';
        } elseif ($deviceRequests >= 3) {
            $flashError = 'Trop de demandes depuis cet appareil. Réessayez dans une heure.';
        } else {
            $code = (string) random_int(100000, 999999);
            $_SESSION['report_draft'] = ['subject' => $subject ?: 'Signalement', 'message' => $message, 'email' => strtolower($email)];
            $_SESSION['report_verification'] = [
                'email' => strtolower($email),
                'code_hash' => password_hash($code, PASSWORD_DEFAULT),
                'expires' => $now + 900,
                'attempts' => 0,
                'device' => $deviceKey,
            ];
            $recent[] = ['device' => $deviceKey, 'expires' => $now + 3600];
            writeReportData($rateFile, $recent);
            $mailResult = sendReportVerificationCodeEmail($email, $code);
            if ($mailResult['success']) {
                $step = 'verify';
                $flashSuccess = 'Un code de vérification vient de vous être envoyé.';
            } else {
                unset($_SESSION['report_verification'], $_SESSION['report_draft']);
                $flashError = $mailResult['error'];
            }
        }
    } elseif ($action === 'verify_code') {
        $verification = $_SESSION['report_verification'] ?? null;
        $draft = $_SESSION['report_draft'] ?? null;
        $code = trim((string) ($_POST['code'] ?? ''));
        if (!is_array($verification) || !is_array($draft) || ($verification['expires'] ?? 0) < time()) {
            $flashError = 'Ce code est expiré. Demandez-en un nouveau.';
            unset($_SESSION['report_verification']);
        } elseif (($verification['attempts'] ?? 0) >= 5) {
            $flashError = 'Trop de tentatives. Demandez un nouveau code.';
            unset($_SESSION['report_verification']);
        } elseif (!preg_match('/^\d{6}$/', $code) || !password_verify($code, $verification['code_hash'])) {
            $_SESSION['report_verification']['attempts'] = (int) $verification['attempts'] + 1;
            $step = 'verify';
            $flashError = 'Code incorrect.';
        } else {
            $reports = readReportData($reportsFile);
            $reports[] = [
                'id' => bin2hex(random_bytes(12)),
                'email' => $draft['email'],
                'subject' => $draft['subject'],
                'message' => $draft['message'],
                'created_at' => date(DATE_ATOM),
                'ip_address' => substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ];
            if (writeReportData($reportsFile, $reports)) {
                unset($_SESSION['report_verification'], $_SESSION['report_draft']);
                $subject = $message = '';
                $step = 'report';
                $flashSuccess = 'Votre signalement a bien été transmis. Merci.';
            } else {
                $flashError = 'Le signalement n’a pas pu être enregistré. Réessayez.';
            }
        }
    }
}

if (!isset($_SESSION['report_csrf'])) {
    $_SESSION['report_csrf'] = bin2hex(random_bytes(32));
}