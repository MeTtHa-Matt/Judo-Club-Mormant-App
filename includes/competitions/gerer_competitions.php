<?php

if (!isset($_SESSION['admin']) || (int) $_SESSION['admin'] !== 1) {
    header('Location: competitions.php');
    exit;
}

require_once __DIR__ . '/../general/security.php';
jcm_require_admin($pdo);
jcm_require_csrf();

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS competition_cibles ("
        . "competition_id INT NOT NULL,"
        . "cible_id INT NOT NULL,"
        . "PRIMARY KEY (competition_id, cible_id),"
        . "CONSTRAINT fk_competition_cibles_competition FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,"
        . "CONSTRAINT fk_competition_cibles_cible FOREIGN KEY (cible_id) REFERENCES cible(id) ON DELETE CASCADE"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (PDOException $e) {
}

try {
    $pdo->exec("ALTER TABLE competitions MODIFY id_cible INT NULL");
} catch (PDOException $e) {
}

try {
    $pdo->exec("ALTER TABLE competitions ADD COLUMN date_limite_inscription DATE NULL");
} catch (PDOException $e) {
}

try {
    $pdo->exec("UPDATE competitions SET date_limite_inscription = DATE_SUB(date, INTERVAL 7 DAY) WHERE date_limite_inscription IS NULL AND date IS NOT NULL");
} catch (PDOException $e) {
}

try {
    $pdo->exec("ALTER TABLE competitions MODIFY date_limite_inscription DATE NOT NULL");
} catch (PDOException $e) {
}

function getCompetitionUploadDir(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'competitions';
}

function getCompetitionImageUrl(?string $imageName): string
{
    if (empty($imageName)) {
        return '';
    }

    $rootPath = getCompetitionUploadDir() . DIRECTORY_SEPARATOR . $imageName;
    $legacyPath = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'competitions' . DIRECTORY_SEPARATOR . $imageName;

    if (is_file($rootPath)) {
        return 'img/competitions/' . rawurlencode($imageName);
    }

    if (is_file($legacyPath)) {
        return 'includes/competitions/img/competitions/' . rawurlencode($imageName);
    }

    return 'img/competitions/' . rawurlencode($imageName);
}

function isCompetitionRegistrationOpen(?string $competitionDate, ?string $deadlineDate): bool
{
    if (empty($competitionDate)) {
        return false;
    }

    $today = date('Y-m-d');
    $effectiveDeadline = !empty($deadlineDate) ? $deadlineDate : date('Y-m-d', strtotime($competitionDate . ' -7 days'));

    return $today < $effectiveDeadline && $today < $competitionDate;
}

function normalizeCompetitionCibleIds($value): array
{
    $items = is_array($value) ? $value : [$value];
    $ids = [];

    foreach ($items as $item) {
        $id = filter_var($item, FILTER_VALIDATE_INT);
        if ($id !== false && $id > 0) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function getCompetitionCibleIds(PDO $pdo, int $competitionId): array
{
    $stmt = $pdo->prepare("SELECT cible_id FROM competition_cibles WHERE competition_id = ? ORDER BY cible_id ASC");
    $stmt->execute([$competitionId]);
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    if (!empty($ids)) {
        return $ids;
    }

    $stmt = $pdo->prepare("SELECT id_cible FROM competitions WHERE id = ?");
    $stmt->execute([$competitionId]);
    $legacyId = $stmt->fetchColumn();

    return $legacyId ? [(int) $legacyId] : [];
}

function syncCompetitionCibleLinks(PDO $pdo, int $competitionId, array $selectedIds): void
{
    $pdo->beginTransaction();
    try {
        $deleteStmt = $pdo->prepare("DELETE FROM competition_cibles WHERE competition_id = ?");
        $deleteStmt->execute([$competitionId]);

        $primaryCibleId = null;
        foreach ($selectedIds as $cibleId) {
            $primaryCibleId = $primaryCibleId ?? $cibleId;
            $insertStmt = $pdo->prepare("INSERT INTO competition_cibles (competition_id, cible_id) VALUES (?, ?)");
            $insertStmt->execute([$competitionId, $cibleId]);
        }

        $updateStmt = $pdo->prepare("UPDATE competitions SET id_cible = ? WHERE id = ?");
        $updateStmt->execute([$primaryCibleId, $competitionId]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nom = $_POST['nom'] ?? '';
        $lieu = $_POST['lieu'] ?? null;
        $selectedCibleIds = normalizeCompetitionCibleIds($_POST['id_cibles'] ?? []);
        $informations = $_POST['informations'] ?? null;
        $date = $_POST['date'] ?? null;
        $registrationDeadline = $_POST['date_limite_inscription'] ?? null;
        if ($nom === '' || mb_strlen($nom) > 100 || mb_strlen((string) $lieu) > 100 || mb_strlen((string) $informations) > 10000) {
            exit('Données de compétition invalides.');
        }
        if (empty($registrationDeadline) && !empty($date)) {
            $registrationDeadline = date('Y-m-d', strtotime($date . ' -7 days'));
        }
        if (!empty($registrationDeadline) && !empty($date) && $registrationDeadline > $date) {
            $registrationDeadline = $date;
        }
        $image = null;
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['image_file']['tmp_name'];
            $fileSize = $_FILES['image_file']['size'];

            $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($fileTmp);
            $check = @getimagesize($fileTmp);
            if ($check !== false && $check[0] <= 6000 && $check[1] <= 6000 && isset($allowedMimes[$mime]) && $fileSize <= 5 * 1024 * 1024) {
                $ext = $allowedMimes[$mime];
                $uploadDir = getCompetitionUploadDir();
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $newName = 'comp_' . bin2hex(random_bytes(16)) . '.' . $ext;
                $dest = $uploadDir . DIRECTORY_SEPARATOR . $newName;
                if (move_uploaded_file($fileTmp, $dest)) {
                    $image = $newName;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO competitions (nom, lieu, id_cible, informations, date, date_limite_inscription, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $lieu, $selectedCibleIds[0] ?? null, $informations, $date, $registrationDeadline, $image]);
        $competitionId = (int) $pdo->lastInsertId();
        if ($competitionId > 0) {
            syncCompetitionCibleLinks($pdo, $competitionId, $selectedCibleIds);
        }
        header('Location: gerer_competitions.php?success=created');
        exit;
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $nom = $_POST['nom'] ?? '';
        $lieu = $_POST['lieu'] ?? null;
        $selectedCibleIds = normalizeCompetitionCibleIds($_POST['id_cibles'] ?? []);
        $informations = $_POST['informations'] ?? null;
        $date = $_POST['date'] ?? null;
        $registrationDeadline = $_POST['date_limite_inscription'] ?? null;
        if ($nom === '' || mb_strlen($nom) > 100 || mb_strlen((string) $lieu) > 100 || mb_strlen((string) $informations) > 10000) {
            exit('Données de compétition invalides.');
        }
        if (empty($registrationDeadline) && !empty($date)) {
            $registrationDeadline = date('Y-m-d', strtotime($date . ' -7 days'));
        }
        if (!empty($registrationDeadline) && !empty($date) && $registrationDeadline > $date) {
            $registrationDeadline = $date;
        }
        $stmtCur = $pdo->prepare("SELECT image FROM competitions WHERE id = ?");
        $stmtCur->execute([$id]);
        $cur = $stmtCur->fetch(PDO::FETCH_ASSOC);
        $currentImage = $cur['image'] ?? null;

        $image = $currentImage;
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['image_file']['tmp_name'];
            $fileSize = $_FILES['image_file']['size'];
            $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($fileTmp);
            $check = @getimagesize($fileTmp);
            if ($check !== false && $check[0] <= 6000 && $check[1] <= 6000 && isset($allowedMimes[$mime]) && $fileSize <= 5 * 1024 * 1024) {
                $ext = $allowedMimes[$mime];
                $uploadDir = getCompetitionUploadDir();
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $newName = 'comp_' . bin2hex(random_bytes(16)) . '.' . $ext;
                $dest = $uploadDir . DIRECTORY_SEPARATOR . $newName;
                if (move_uploaded_file($fileTmp, $dest)) {
                    if ($currentImage) {
                        $oldPath = $uploadDir . DIRECTORY_SEPARATOR . $currentImage;
                        if (is_file($oldPath))
                            @unlink($oldPath);
                    }
                    $image = $newName;
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE competitions SET nom = ?, lieu = ?, id_cible = ?, informations = ?, date = ?, date_limite_inscription = ?, image = ? WHERE id = ?");
        $stmt->execute([$nom, $lieu, $selectedCibleIds[0] ?? null, $informations, $date, $registrationDeadline, $image, $id]);
        syncCompetitionCibleLinks($pdo, $id, $selectedCibleIds);
        header('Location: gerer_competitions.php?success=updated');
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmtImg = $pdo->prepare("SELECT image FROM competitions WHERE id = ?");
        $stmtImg->execute([$id]);
        $imgR = $stmtImg->fetch(PDO::FETCH_ASSOC);
        $imgName = $imgR['image'] ?? null;
        if ($imgName) {
            $uploadDir = getCompetitionUploadDir();
            $path = $uploadDir . DIRECTORY_SEPARATOR . $imgName;
            if (is_file($path))
                @unlink($path);
        }
        $stmt = $pdo->prepare("DELETE FROM competitions WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: gerer_competitions.php?success=deleted');
        exit;
    }
}

$editing = false;
$editData = null;
$selectedEditCibleIds = [];
if (isset($_GET['edit'])) {
    $idEdit = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM competitions WHERE id = ?");
    $stmt->execute([$idEdit]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editData) {
        $editing = true;
        $selectedEditCibleIds = getCompetitionCibleIds($pdo, (int) $editData['id']);
        $editData['selected_cible_ids'] = $selectedEditCibleIds;
    }
}

$stmt = $pdo->query("SELECT c.id, c.nom, c.lieu, c.informations, c.date, c.image, c.id_cible,
        GROUP_CONCAT(DISTINCT ci.cible ORDER BY ci.cible SEPARATOR ', ') AS cible_nom
    FROM competitions c
    LEFT JOIN (
        SELECT cc.competition_id AS competition_id, ci2.cible AS cible
        FROM competition_cibles cc
        JOIN cible ci2 ON ci2.id = cc.cible_id
        UNION ALL
        SELECT c2.id AS competition_id, ci3.cible AS cible
        FROM competitions c2
        JOIN cible ci3 ON ci3.id = c2.id_cible
        WHERE c2.id_cible IS NOT NULL
    ) ci ON ci.competition_id = c.id
    GROUP BY c.id, c.nom, c.lieu, c.informations, c.date, c.image, c.id_cible
    ORDER BY c.date DESC");
$competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM cible ORDER BY id ASC");
$cibles = $stmt->fetchAll(PDO::FETCH_ASSOC);