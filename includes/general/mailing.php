<?php

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['admin']) || (int) $_SESSION['admin'] !== 1) {
    header('Location: index.php');
    exit;
}

$flashSuccess = null;
$flashError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_mail') {
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $messageHtml = trim((string) ($_POST['message_html'] ?? ''));

    if ($subject === '') {
        $flashError = 'Le sujet du mail est obligatoire.';
    } elseif ($messageHtml === '') {
        $flashError = 'Le contenu du mail est obligatoire.';
    } else {
        $messageHtml = preg_replace('/\s+/', ' ', $messageHtml);
        $messageHtml = str_replace(['&nbsp;', ' '], ' ', $messageHtml);

        $recipients = [];
        $stmt = $pdo->query("SELECT firstname, lastname, email FROM account WHERE email IS NOT NULL AND TRIM(email) <> '' AND accept_email = 1 ORDER BY lastname, firstname");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $user) {
            $email = trim((string) ($user['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $name = trim((string) ($user['firstname'] ?? '') . ' ' . (string) ($user['lastname'] ?? ''));
            $recipients[] = ['email' => $email, 'name' => $name];
        }

        if (empty($recipients)) {
            $flashError = 'Aucun destinataire n’a été trouvé.';
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

            $result = sendBulkHtmlEmail($recipients, $subject, $messageHtml, $embeddedImages);
            if ($result['success']) {
                $flashSuccess = 'Le mail a été envoyé à ' . count($recipients) . ' destinataire(s).';
            } else {
                $flashError = 'Échec de l’envoi : ' . ($result['error'] ?? 'Erreur inconnue');
            }
        }
    }
}