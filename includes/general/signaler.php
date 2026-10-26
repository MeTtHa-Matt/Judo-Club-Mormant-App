<?php
require_once __DIR__ . '/session_start_pwa.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/report_storage.php';

$reportAccountEmail = null;
$isReportUserAuthenticated = !empty($_SESSION['id']);
if ($isReportUserAuthenticated) {
    $accountEmailStmt = $pdo->prepare('SELECT email FROM account WHERE id = ? LIMIT 1');
    $accountEmailStmt->execute([(int) $_SESSION['id']]);
    $accountEmail = $accountEmailStmt->fetchColumn();
    if (is_string($accountEmail) && filter_var($accountEmail, FILTER_VALIDATE_EMAIL)) {
        $reportAccountEmail = strtolower(trim($accountEmail));
        $_SESSION['report_verified_email'] = $reportAccountEmail;
    }
    unset($_SESSION['report_verification'], $_SESSION['report_draft']);
}

$flashSuccess = null;
$flashError = null;
$subject = trim((string) ($_SESSION['report_draft']['subject'] ?? ''));
$message = trim((string) ($_SESSION['report_draft']['message'] ?? ''));
$step = isset($_SESSION['report_verification']) ? 'verify' : 'report';
$deviceKey = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));

function jcm_save_report_submission(string $email, string $subject, string $message): bool
{
    return jcm_create_report([
        'id' => bin2hex(random_bytes(12)),
        'email' => strtolower($email),
        'subject' => $subject ?: 'Signalement',
        'created_at' => jcm_report_timestamp(),
        'ip_address' => substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        'messages' => [[
            'sender' => 'user',
            'message' => $message,
            'created_at' => jcm_report_timestamp(),
        ]],
    ]);
}

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
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $json !== false && file_put_contents($file, $json, LOCK_EX) !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $csrf = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['report_csrf'] ?? '', $csrf)) {
        $flashError = 'Votre session a expiré. Rechargez la page puis réessayez.';
    } elseif (!empty($_POST['website'])) {
        $flashError = 'La demande n’a pas pu être traitée.';
    } elseif ($action === 'change_verified_email') {
        unset($_SESSION['report_verified_email']);
        $flashSuccess = 'Vous pouvez vérifier une autre adresse email.';
    } elseif ($action === 'request_code' || $action === 'request_access_code') {
        $subject = substr(trim((string) ($_POST['subject'] ?? '')), 0, 150);
        $message = trim((string) ($_POST['message'] ?? ''));
        $email = $isReportUserAuthenticated
            ? (string) ($reportAccountEmail ?? '')
            : trim((string) ($_POST['email'] ?? ''));
        $isAccessRequest = $action === 'request_access_code';
        $rateFile = __DIR__ . '/../../storage/reports/rate_limits.json';
        $rateData = readReportData($rateFile);
        $now = time();
        $recent = array_values(array_filter($rateData, static fn($entry) => ($entry['expires'] ?? 0) > $now));
        $deviceRequests = count(array_filter($recent, static fn($entry) => ($entry['device'] ?? '') === $deviceKey));

        if (!$isAccessRequest && ($message === '' || strlen($message) < 10)) {
            $flashError = 'Décrivez le problème en au moins 10 caractères.';
        } elseif (!$isReportUserAuthenticated && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254)) {
            $flashError = 'Renseignez une adresse email valide.';
        } elseif ($isReportUserAuthenticated && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flashError = 'Aucune adresse email valide n’est associée à votre compte.';
        } elseif ($deviceRequests >= 3) {
            $flashError = 'Trop de demandes depuis cet appareil. Réessayez dans une heure.';
        } elseif ($isReportUserAuthenticated && !$isAccessRequest) {
            $recent[] = ['device' => $deviceKey, 'expires' => $now + 3600];
            if (!writeReportData($rateFile, $recent)) {
                $flashError = 'Impossible de démarrer le signalement. Réessayez.';
            } else {
                try {
                    $saved = jcm_save_report_submission($email, $subject, $message);
                } catch (Throwable $exception) {
                    error_log('Authenticated report creation error: ' . $exception->getMessage());
                    $saved = false;
                }

                if ($saved) {
                    $_SESSION['report_verified_email'] = $email;
                    $subject = $message = '';
                    $flashSuccess = 'Votre signalement a bien été transmis. Merci.';
                } else {
                    $flashError = 'Le signalement n’a pas pu être enregistré. Réessayez.';
                }
            }
        } else {
            $code = (string) random_int(100000, 999999);
            $_SESSION['report_draft'] = $isAccessRequest
                ? null
                : ['subject' => $subject ?: 'Signalement', 'message' => $message, 'email' => strtolower($email)];
            $_SESSION['report_verification'] = [
                'email' => strtolower($email),
                'purpose' => $isAccessRequest ? 'access' : 'new_report',
                'code_hash' => password_hash($code, PASSWORD_DEFAULT),
                'expires' => $now + 900,
                'attempts' => 0,
                'device' => $deviceKey,
            ];
            $recent[] = ['device' => $deviceKey, 'expires' => $now + 3600];
            if (!writeReportData($rateFile, $recent)) {
                unset($_SESSION['report_verification'], $_SESSION['report_draft']);
                $flashError = 'Impossible de démarrer la vérification. Réessayez.';
            } else {
                $mailResult = sendReportVerificationCodeEmail($email, $code);
                if ($mailResult['success']) {
                    $step = 'verify';
                    $flashSuccess = 'Un code de vérification vient de vous être envoyé.';
                } else {
                    unset($_SESSION['report_verification'], $_SESSION['report_draft']);
                    $flashError = $mailResult['error'];
                }
            }
        }
    } elseif ($action === 'verify_code') {
        $verification = $_SESSION['report_verification'] ?? null;
        $draft = $_SESSION['report_draft'] ?? null;
        $code = trim((string) ($_POST['code'] ?? ''));
        if (!is_array($verification) || ($verification['expires'] ?? 0) < time()) {
            $flashError = 'Ce code est expiré. Demandez-en un nouveau.';
            unset($_SESSION['report_verification']);
        } elseif (($verification['attempts'] ?? 0) >= 5) {
            $flashError = 'Trop de tentatives. Demandez un nouveau code.';
            unset($_SESSION['report_verification']);
        } elseif (!preg_match('/^\d{6}$/', $code) || !password_verify($code, (string) ($verification['code_hash'] ?? ''))) {
            $_SESSION['report_verification']['attempts'] = (int) $verification['attempts'] + 1;
            $step = 'verify';
            $flashError = 'Code incorrect.';
        } else {
            $purpose = $verification['purpose'] ?? 'new_report';
            if ($purpose === 'access') {
                $_SESSION['report_verified_email'] = strtolower((string) $verification['email']);
                unset($_SESSION['report_verification'], $_SESSION['report_draft']);
                $step = 'report';
                $flashSuccess = 'Votre adresse est vérifiée. Vos signalements sont maintenant accessibles.';
            } else {
                $draft = $_SESSION['report_draft'] ?? null;
                if (!is_array($draft)) {
                    unset($_SESSION['report_verification'], $_SESSION['report_draft']);
                    $step = 'report';
                    $flashError = 'Votre demande a expiré. Recommencez le signalement.';
                } else {
                    try {
                        $saved = jcm_save_report_submission(
                            (string) $draft['email'],
                            (string) $draft['subject'],
                            (string) $draft['message']
                        );
                    } catch (Throwable $exception) {
                        error_log('Report creation error: ' . $exception->getMessage());
                        $saved = false;
                    }
                    if ($saved) {
                        $_SESSION['report_verified_email'] = strtolower((string) $draft['email']);
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
    }
}

if (!isset($_SESSION['report_csrf'])) {
    $_SESSION['report_csrf'] = bin2hex(random_bytes(32));
}

$reportVerifiedEmail = strtolower(trim((string) ($_SESSION['report_verified_email'] ?? '')));
$userReports = [];
$activeReport = null;
if ($reportVerifiedEmail !== '') {
    try {
        $userReports = array_values(array_filter(
            jcm_list_reports(),
            static fn(array $report): bool => strtolower((string) ($report['email'] ?? '')) === $reportVerifiedEmail
        ));
        $requestedReportId = strtolower(trim((string) ($_GET['report'] ?? '')));
        if ($requestedReportId !== '') {
            foreach ($userReports as $index => $report) {
                if (($report['id'] ?? '') === $requestedReportId) {
                    $activeReport = jcm_update_report($requestedReportId, static function (array $storedReport): array {
                        $storedReport['user_read_at'] = jcm_report_timestamp();
                        return $storedReport;
                    });
                    if ($activeReport !== null) {
                        $userReports[$index] = $activeReport;
                    }
                    break;
                }
            }
            if ($activeReport === null) {
                $flashError = 'Ce signalement est introuvable ou ne vous appartient pas.';
            }
        }
    } catch (Throwable $exception) {
        error_log('Report list error: ' . $exception->getMessage());
        $flashError = 'Impossible de charger vos signalements pour le moment.';
    }
}