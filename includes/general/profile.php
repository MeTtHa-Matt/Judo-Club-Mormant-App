<?php

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['id'];

$flashSuccess = null;
$flashError = null;

$stmt = $pdo->prepare("SELECT id, firstname, lastname, email, pdp, admin, accept_email FROM account WHERE id = ?");
$stmt->execute([$userId]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once __DIR__ . '/security.php';
    jcm_require_csrf();

    if ($_POST['action'] === 'update_info') {
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');

        if (!jcm_valid_person_name($firstname) || !jcm_valid_person_name($lastname)) {
            $flashError = "Tous les champs sont obligatoires.";
        } else {
            $update = $pdo->prepare("UPDATE account SET firstname = ?, lastname = ? WHERE id = ?");
            $update->execute([$firstname, $lastname, $userId]);
            $account['firstname'] = $firstname;
            $account['lastname'] = $lastname;
            $flashSuccess = "Vos informations ont été mises à jour.";
        }
    }

    if ($_POST['action'] === 'update_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM account WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $flashError = "Le mot de passe actuel est incorrect.";
        } elseif (strlen($new) < 8) {
            $flashError = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        } elseif ($new !== $confirm) {
            $flashError = "La confirmation ne correspond pas au nouveau mot de passe.";
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE account SET password = ? WHERE id = ?");
            $update->execute([$newHash, $userId]);
            $pdo->prepare('DELETE FROM persistent_tokens_jcm WHERE account_id = ?')->execute([$userId]);
            $flashSuccess = "Votre mot de passe a été changé.";
        }
    }

    if ($_POST['action'] === 'update_avatar' && isset($_FILES['pdp']) && $_FILES['pdp']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['pdp']['tmp_name']);

        $imageInfo = @getimagesize($_FILES['pdp']['tmp_name']);
        if (!isset($allowed[$mime]) || $_FILES['pdp']['size'] > 3 * 1024 * 1024
            || $imageInfo === false || $imageInfo[0] > 4000 || $imageInfo[1] > 4000) {
            $flashError = "Image invalide (formats acceptés : jpg, png, webp — 3 Mo maximum).";
        } else {
            $destDir = "img/pdps/";
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $filename = "avatar_" . $userId . "_" . bin2hex(random_bytes(12)) . "." . $allowed[$mime];
            if (move_uploaded_file($_FILES['pdp']['tmp_name'], $destDir . $filename)) {
                // tenter de supprimer l'ancienne photo uniquement si elle existe
                $oldPdp = $account['pdp'] ?? '';
                if (!empty($oldPdp) && $oldPdp !== 'pdp_base.png') {
                    $oldPath = $destDir . basename($oldPdp);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $update = $pdo->prepare("UPDATE account SET pdp = ? WHERE id = ?");
                $update->execute([$filename, $userId]);
                $account['pdp'] = $filename;
                $_SESSION['pdp'] = $filename;
                $flashSuccess = "Votre photo de profil a été mise à jour.";
            } else {
                $flashError = "Le téléversement de l'image a échoué.";
            }
        }
    }
}

$stmt = $pdo->prepare("
    SELECT c.nom, c.lieu, c.date,
           GROUP_CONCAT(DISTINCT ci.cible ORDER BY ci.cible SEPARATOR ', ') AS cible,
           ceint.ceinture, i.Poids
    FROM inscrits i
    JOIN competitions c ON i.id_competition = c.id
    LEFT JOIN competition_cibles cc ON cc.competition_id = c.id
    LEFT JOIN cible ci ON ci.id = cc.cible_id OR ci.id = c.id_cible
    JOIN ceintures ceint ON i.id_ceinture = ceint.id
    WHERE i.id_account = ?
    GROUP BY c.id, c.nom, c.lieu, c.date, ceint.ceinture, i.Poids
    ORDER BY c.date DESC
");
$stmt->execute([$userId]);
$inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$avatarPath = !empty($account['pdp']) ? "img/pdps/" . basename($account['pdp']) : "img/pdps/pdp_base.png";