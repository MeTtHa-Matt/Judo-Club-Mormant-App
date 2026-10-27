<?php

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['admin']) || (int) $_SESSION['admin'] !== 1) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/security.php';
$mailingAdminId = jcm_require_admin($pdo);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS mailing_history (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        sender_account_id INT NULL,
        subject VARCHAR(255) NOT NULL,
        message_html MEDIUMTEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT "sending",
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_account_id) REFERENCES account(id) ON DELETE SET NULL,
        INDEX idx_mailing_history_created_at (created_at)
    )'
);
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS mailing_history_recipients (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        mailing_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL DEFAULT "",
        recipient_type VARCHAR(2) NOT NULL,
        FOREIGN KEY (mailing_id) REFERENCES mailing_history(id) ON DELETE CASCADE,
        INDEX idx_mailing_recipients_mailing (mailing_id),
        INDEX idx_mailing_recipients_email (email)
    )'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    jcm_require_csrf();
}

$flashSuccess = null;
$flashError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_mail') {
    $rawSubject = $_POST['subject'] ?? null;
    $rawMessageHtml = $_POST['message_html'] ?? null;
    $subject = is_string($rawSubject) ? trim($rawSubject) : '';
    $messageHtml = is_string($rawMessageHtml) ? trim($rawMessageHtml) : '';
    $toEmails = $_POST['to'] ?? [];

    if (!is_string($rawSubject) || !is_string($rawMessageHtml)) {
        $flashError = 'Le sujet et le contenu du mail sont invalides.';
    } elseif ($subject === '') {
        $flashError = 'Le sujet du mail est obligatoire.';
    } elseif (mb_strlen($subject) > 255) {
        $flashError = 'Le sujet ne peut pas dépasser 255 caractères.';
    } elseif (trim(strip_tags($messageHtml)) === '' && !str_contains($messageHtml, '<img')) {
        $flashError = 'Le contenu du mail est obligatoire.';
    } elseif (!is_array($toEmails)) {
        $flashError = 'La liste des destinataires est invalide.';
    } else {
        $messageHtml = preg_replace('/\s+/', ' ', $messageHtml);
        $messageHtml = str_replace(['&nbsp;', ' '], ' ', $messageHtml);
        $eligibleAccounts = $pdo->query(
            "SELECT firstname, lastname, email, admin FROM account
             WHERE email IS NOT NULL AND TRIM(email) <> ''
             ORDER BY lastname, firstname"
        )->fetchAll(PDO::FETCH_ASSOC);
        $accountNames = [];
        $commandRecipients = ['all' => [], 'admin' => [], 'nadmin' => []];
        foreach ($eligibleAccounts as $user) {
            $email = strtolower(trim((string) $user['email']));
            if ($email !== '') {
                $accountNames[$email] = trim((string) $user['firstname'] . ' ' . (string) $user['lastname']);
                $commandRecipients[(int) $user['admin'] === 1 ? 'admin' : 'nadmin'][] = $email;
                $commandRecipients['all'][] = $email;
            }
        }

        $expandedEmails = [];
        foreach ($toEmails as $rawEmail) {
            if (!is_string($rawEmail)) {
                continue;
            }
            $token = trim($rawEmail);
            $command = strtolower($token);
            if (isset($commandRecipients[$command])) {
                array_push($expandedEmails, ...$commandRecipients[$command]);
            } else {
                $expandedEmails[] = $token;
            }
        }

        $invalidRecipient = false;
        foreach ($expandedEmails as $email) {
            if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidRecipient = true;
                break;
            }
        }

        $buildRecipients = static function (array $emails) use ($accountNames): array {
            $recipients = [];
            $seen = [];
            foreach ($emails as $rawEmail) {
                if (!is_string($rawEmail)) {
                    continue;
                }
                $email = strtolower(trim($rawEmail));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email])) {
                    continue;
                }
                $seen[$email] = true;
                $recipients[] = [
                    'email' => $email,
                    'name' => $accountNames[$email] ?? '',
                ];
            }
            return $recipients;
        };

        $toRecipients = $buildRecipients($expandedEmails);

        if ($invalidRecipient) {
            $flashError = 'Une ou plusieurs adresses e-mail ne sont pas valides.';
        } elseif (empty($toRecipients)) {
            $flashError = 'Ajoutez au moins un destinataire dans le champ « À ».';
        } else {
            $embeddedImages = [];
            $pattern = '/<img[^>]+src=["\'](data:(image\/[a-zA-Z0-9.+-]+);base64,([A-Za-z0-9\/+=]+))[^>]*>/i';
            $messageHtml = preg_replace_callback($pattern, function ($matches) use (&$embeddedImages): string {
                $mime = $matches[2] ?? 'image/png';
                $data = $matches[3] ?? '';
                $content = base64_decode($data, true);
                if ($content === false || $content === '') {
                    return $matches[0];
                }

                $ext = match ($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    default => 'png'
                };
                $cid = 'inline-image-' . count($embeddedImages);
                $embeddedImages[] = [
                    'cid' => $cid,
                    'content' => $content,
                    'filename' => 'image_' . count($embeddedImages) . '.' . $ext,
                    'mime' => $mime,
                ];

                return str_replace($matches[1], 'cid:' . $cid, $matches[0]);
            }, $messageHtml);

            $historyId = null;
            try {
                $pdo->beginTransaction();
                $insertHistory = $pdo->prepare(
                    'INSERT INTO mailing_history (sender_account_id, subject, message_html, status)
                     VALUES (?, ?, ?, "sending")'
                );
                $insertHistory->execute([$mailingAdminId, $subject, $messageHtml]);
                $historyId = (int) $pdo->lastInsertId();

                $insertRecipient = $pdo->prepare(
                    'INSERT INTO mailing_history_recipients (mailing_id, email, name, recipient_type)
                     VALUES (?, ?, ?, ?)'
                );
                foreach ($toRecipients as $recipient) {
                    $insertRecipient->execute([
                        $historyId,
                        $recipient['email'],
                        $recipient['name'],
                        'to',
                    ]);
                }
                $pdo->commit();
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $error;
            }

            $result = sendBulkHtmlEmail($toRecipients, $subject, $messageHtml, $embeddedImages);
            $status = $result['success'] ? 'sent' : 'failed';
            $updateHistory = $pdo->prepare('UPDATE mailing_history SET status = ? WHERE id = ?');
            $updateHistory->execute([$status, $historyId]);

            if ($result['success']) {
                $flashSuccess = 'Le mail a été envoyé à ' . count($toRecipients) . ' destinataire(s).';
            } else {
                $flashError = 'Échec de l’envoi : ' . ($result['error'] ?? 'Erreur inconnue');
            }
        }
    }
}

$eligibleAccounts = $pdo->query(
    "SELECT firstname, lastname, email, admin FROM account
     WHERE email IS NOT NULL AND TRIM(email) <> ''
     ORDER BY lastname, firstname"
)->fetchAll(PDO::FETCH_ASSOC);
$mailingContacts = [];
foreach ($eligibleAccounts as $user) {
    $email = trim((string) $user['email']);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mailingContacts[] = [
            'email' => $email,
            'name' => trim((string) $user['firstname'] . ' ' . (string) $user['lastname']),
            'admin' => (int) $user['admin'] === 1,
        ];
    }
}

$mailingHistory = $pdo->query(
    "SELECT h.id, h.subject, h.message_html, h.status, h.created_at,
            a.firstname AS sender_firstname, a.lastname AS sender_lastname
     FROM mailing_history h
     LEFT JOIN account a ON a.id = h.sender_account_id
     ORDER BY h.created_at DESC, h.id DESC
     LIMIT 30"
)->fetchAll(PDO::FETCH_ASSOC);
$mailingHistoryRecipients = [];
if (!empty($mailingHistory)) {
    $historyIds = array_map('intval', array_column($mailingHistory, 'id'));
    $placeholders = implode(',', array_fill(0, count($historyIds), '?'));
    $recipientStmt = $pdo->prepare(
        "SELECT mailing_id, email, name, recipient_type
         FROM mailing_history_recipients
         WHERE mailing_id IN ($placeholders)
         ORDER BY email"
    );
    $recipientStmt->execute($historyIds);
    foreach ($recipientStmt->fetchAll(PDO::FETCH_ASSOC) as $recipient) {
        $mailId = (int) $recipient['mailing_id'];
        $type = $recipient['recipient_type'] === 'cc' ? 'cc' : 'to';
        $name = trim((string) $recipient['name']);
        $mailingHistoryRecipients[$mailId][$type][] = $name !== ''
            ? $name . ' <' . $recipient['email'] . '>'
            : $recipient['email'];
    }

    foreach ($mailingHistory as &$history) {
        $recipients = $mailingHistoryRecipients[(int) $history['id']] ?? [];
        $history['to_emails'] = $recipients['to'] ?? [];
        $history['cc_emails'] = $recipients['cc'] ?? [];
    }
    unset($history);
}
