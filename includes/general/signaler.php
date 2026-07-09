<?php
if (!isset($_SESSION['id'])) {
    header('Location: login.php?alert=' . urlencode('Veuillez vous connecter pour signaler un problème.'));
    exit;
}

$flashSuccess = null;
$flashError = null;

$accountId = (int) $_SESSION['id'];
$stmt = $pdo->prepare('SELECT firstname, lastname, email FROM account WHERE id = ? LIMIT 1');
$stmt->execute([$accountId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: logout.php');
    exit;
}

$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_report') {
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($subject === '') {
        $flashError = 'Le sujet est obligatoire.';
    } elseif ($message === '') {
        $flashError = 'Le message est obligatoire.';
    } else {
        $stmtCount = $pdo->prepare(
            'SELECT COUNT(*) FROM signalements_jcm WHERE account_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
        );
        $stmtCount->execute([$accountId]);
        $sentThisWeek = (int) $stmtCount->fetchColumn();

        if ($sentThisWeek >= 3) {
            $flashError = 'Vous avez déjà envoyé 3 signalements au cours des 7 derniers jours. Merci de réessayer plus tard.';
        } else {
            $cleanSubject = substr(trim($subject), 0, 150);
            $cleanMessage = trim($message);
            $userName = htmlspecialchars(trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')));
            $replyTo = trim((string) ($user['email'] ?? ''));

            $htmlBody = '<p>Un utilisateur a soumis un signalement via l’espace membre.</p>' .
                '<p><strong>Utilisateur :</strong> ' . $userName . ' (ID ' . $accountId . ')</p>' .
                '<p><strong>Objet :</strong> ' . htmlspecialchars($cleanSubject) . '</p>' .
                '<p><strong>Message :</strong></p>' .
                '<div style="white-space:pre-wrap; font-family:Arial, sans-serif; padding:12px; border:1px solid #ddd; background:#f8f8f8;">' .
                nl2br(htmlspecialchars($cleanMessage)) .
                '</div>' .
                '<p><strong>IP :</strong> ' . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'N/A') . '</p>' .
                '<p><strong>User agent :</strong> ' . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . '</p>';

            $result = sendSiteContactEmail('[Signalement JCM] ' . $cleanSubject, $htmlBody, $replyTo, $userName);
            if ($result['success']) {
                $stmtInsert = $pdo->prepare(
                    'INSERT INTO signalements_jcm (account_id, subject, message, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)'
                );
                $stmtInsert->execute([
                    $accountId,
                    $cleanSubject,
                    $cleanMessage,
                    substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                ]);

                $flashSuccess = 'Votre signalement a bien été envoyé. Nous vous répondrons dès que possible.';
                $subject = '';
                $message = '';
            } else {
                $flashError = 'Échec de l’envoi : ' . ($result['error'] ?? 'Erreur inconnue.');
            }
        }
    }
}