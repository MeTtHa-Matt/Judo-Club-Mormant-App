<?php

function jcm_report_directory(): string
{
    return __DIR__ . '/../../storage/reports';
}

function jcm_report_normalize(array $report): array
{
    $report['id'] = strtolower((string) ($report['id'] ?? ''));
    $report['email'] = strtolower(trim((string) ($report['email'] ?? '')));

    if (!isset($report['messages']) || !is_array($report['messages'])) {
        $report['messages'] = [[
            'sender' => 'user',
            'message' => (string) ($report['message'] ?? ''),
            'created_at' => (string) ($report['created_at'] ?? ''),
        ]];
    }
    unset($report['message']);

    return $report;
}

function jcm_report_parse_timestamp(string $timestamp): ?DateTimeImmutable
{
    if (trim($timestamp) === '') {
        return null;
    }

    try {
        return new DateTimeImmutable($timestamp);
    } catch (Exception $exception) {
        return null;
    }
}

function jcm_report_has_unread_messages(array $report, string $reader): bool
{
    $readAt = jcm_report_parse_timestamp((string) ($report[$reader . '_read_at'] ?? ''))
        ?? new DateTimeImmutable('@0');
    $incomingSender = $reader === 'admin' ? 'user' : 'admin';

    foreach ($report['messages'] ?? [] as $message) {
        if (($message['sender'] ?? '') !== $incomingSender) {
            continue;
        }
        $messageTime = jcm_report_parse_timestamp((string) ($message['created_at'] ?? ''));
        if ($messageTime !== null && $messageTime > $readAt) {
            return true;
        }
    }

    return false;
}

function jcm_report_timestamp(): string
{
    return (new DateTimeImmutable())->format('Y-m-d\TH:i:s.uP');
}

function jcm_count_unread_reports(array $reports, string $reader): int
{
    $unreadCount = 0;
    foreach ($reports as $report) {
        if (jcm_report_has_unread_messages($report, $reader)) {
            $unreadCount++;
        }
    }

    return $unreadCount;
}

function jcm_count_user_unread_reports(array $reports, string $email): int
{
    $email = strtolower(trim($email));
    $unreadCount = 0;
    foreach ($reports as $report) {
        if (strtolower((string) ($report['email'] ?? '')) === $email
            && jcm_report_has_unread_messages($report, 'user')) {
            $unreadCount++;
        }
    }

    return $unreadCount;
}

function jcm_read_report_file(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Impossible de lire un fichier de signalement.');
    }

    try {
        if (!flock($handle, LOCK_SH)) {
            throw new RuntimeException('Impossible de verrouiller un fichier de signalement.');
        }
        $contents = stream_get_contents($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }

    $report = json_decode($contents ?: '', true);
    if (!is_array($report)) {
        throw new RuntimeException('Le fichier de signalement contient un JSON invalide.');
    }

    return jcm_report_normalize($report);
}

function jcm_find_legacy_report(string $reportId): ?array
{
    $legacyFile = jcm_report_directory() . '/reports.json';
    if (!is_file($legacyFile)) {
        return null;
    }

    $legacyReports = json_decode((string) file_get_contents($legacyFile), true);
    if (!is_array($legacyReports)) {
        throw new RuntimeException('Le fichier historique des signalements contient un JSON invalide.');
    }

    foreach ($legacyReports as $report) {
        if (is_array($report) && strtolower((string) ($report['id'] ?? '')) === $reportId) {
            return jcm_report_normalize($report);
        }
    }

    return null;
}

function jcm_load_report(string $reportId): ?array
{
    $reportId = strtolower($reportId);
    if (!preg_match('/^[a-f0-9]{24}$/', $reportId)) {
        return null;
    }

    $report = jcm_read_report_file(jcm_report_directory() . '/' . $reportId . '.json');
    return $report ?? jcm_find_legacy_report($reportId);
}

function jcm_list_reports(bool $migrateLegacy = false): array
{
    $directory = jcm_report_directory();
    if (!is_dir($directory)) {
        return [];
    }

    $reportsById = [];
    foreach (scandir($directory) ?: [] as $filename) {
        if (!preg_match('/^([a-f0-9]{24})\.json$/', $filename, $matches)) {
            continue;
        }

        $report = jcm_read_report_file($directory . '/' . $filename);
        if ($report !== null) {
            $reportsById[$matches[1]] = $report;
        }
    }

    $legacyFile = $directory . '/reports.json';
    if (is_file($legacyFile)) {
        $legacyReports = json_decode((string) file_get_contents($legacyFile), true);
        if (!is_array($legacyReports)) {
            throw new RuntimeException('Le fichier historique des signalements contient un JSON invalide.');
        }

        foreach ($legacyReports as $legacyReport) {
            if (!is_array($legacyReport)) {
                continue;
            }
            $report = jcm_report_normalize($legacyReport);
            if (preg_match('/^[a-f0-9]{24}$/', $report['id']) && !isset($reportsById[$report['id']])) {
                if ($migrateLegacy) {
                    $individualReport = jcm_update_report($report['id'], static fn(array $existingReport): array => $existingReport);
                    if ($individualReport === null) {
                        throw new RuntimeException('Impossible de migrer un signalement historique.');
                    }
                    $reportsById[$report['id']] = $individualReport;
                    continue;
                }
                $reportsById[$report['id']] = $report;
            }
        }
    }

    $reports = array_values($reportsById);
    usort($reports, static fn(array $left, array $right): int =>
        strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''))
    );

    return $reports;
}

function jcm_create_report(array $report): bool
{
    $directory = jcm_report_directory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Impossible de créer le dossier des signalements.');
    }

    $report = jcm_report_normalize($report);
    if (!preg_match('/^[a-f0-9]{24}$/', $report['id'])) {
        throw new InvalidArgumentException('Identifiant de signalement invalide.');
    }
    $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Impossible d’encoder le signalement en JSON.');
    }

    $handle = fopen($directory . '/' . $report['id'] . '.json', 'x');
    if ($handle === false) {
        return false;
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Impossible de verrouiller le nouveau signalement.');
        }
        $written = fwrite($handle, $json);
        if ($written !== strlen($json) || !fflush($handle)) {
            throw new RuntimeException('Impossible d’enregistrer le nouveau signalement.');
        }
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }

    return true;
}

function jcm_update_report(string $reportId, callable $update): ?array
{
    $reportId = strtolower($reportId);
    if (!preg_match('/^[a-f0-9]{24}$/', $reportId)) {
        return null;
    }

    $directory = jcm_report_directory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Impossible de créer le dossier des signalements.');
    }

    $handle = fopen($directory . '/' . $reportId . '.json', 'c+');
    if ($handle === false) {
        throw new RuntimeException('Impossible d’ouvrir le signalement.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Impossible de verrouiller le signalement.');
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        if ($contents === '') {
            $report = jcm_find_legacy_report($reportId);
            if ($report === null) {
                flock($handle, LOCK_UN);
                return null;
            }
        } else {
            $report = json_decode($contents, true);
            if (!is_array($report)) {
                throw new RuntimeException('Le fichier de signalement contient un JSON invalide.');
            }
            $report = jcm_report_normalize($report);
        }

        $report = $update($report);
        if (!is_array($report)) {
            throw new RuntimeException('La mise à jour du signalement a échoué.');
        }

        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Impossible d’encoder le signalement en JSON.');
        }

        rewind($handle);
        if (!ftruncate($handle, 0) || fwrite($handle, $json) !== strlen($json) || !fflush($handle)) {
            throw new RuntimeException('Impossible d’enregistrer la conversation du signalement.');
        }
        flock($handle, LOCK_UN);

        return $report;
    } finally {
        fclose($handle);
    }
}

function jcm_delete_report(string $reportId): bool
{
    $reportId = strtolower($reportId);
    if (!preg_match('/^[a-f0-9]{24}$/', $reportId)) {
        return false;
    }

    $directory = jcm_report_directory();
    $reportPath = $directory . '/' . $reportId . '.json';
    if (!is_file($reportPath) && !jcm_find_legacy_report($reportId)) {
        return false;
    }
    $handle = fopen($reportPath, 'c+');
    if ($handle === false) {
        return false;
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Impossible de verrouiller le signalement avant sa suppression.');
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        if ($contents === '') {
            $report = jcm_find_legacy_report($reportId);
            if ($report === null) {
                return false;
            }
        }

        $attachmentDirectory = $directory . '/' . $reportId;
        $imageDirectory = $attachmentDirectory . '/images';
        if (is_dir($imageDirectory)) {
            foreach (scandir($imageDirectory) ?: [] as $filename) {
                if (preg_match('/^[a-f0-9]{32}\.(?:jpg|png|gif|webp)$/', $filename)
                    && is_file($imageDirectory . '/' . $filename)
                    && !unlink($imageDirectory . '/' . $filename)) {
                    throw new RuntimeException('Impossible de supprimer une image jointe au signalement.');
                }
            }
            if (!rmdir($imageDirectory)) {
                throw new RuntimeException('Impossible de supprimer le dossier des images du signalement.');
            }
        }
        if (is_dir($attachmentDirectory) && !rmdir($attachmentDirectory)) {
            throw new RuntimeException('Impossible de supprimer le dossier du signalement.');
        }

        $legacyPath = $directory . '/reports.json';
        if (is_file($legacyPath)) {
            $legacyHandle = fopen($legacyPath, 'c+');
            if ($legacyHandle === false || !flock($legacyHandle, LOCK_EX)) {
                if (is_resource($legacyHandle)) {
                    fclose($legacyHandle);
                }
                throw new RuntimeException('Impossible de verrouiller les signalements historiques.');
            }

            try {
                rewind($legacyHandle);
                $legacyReports = json_decode(stream_get_contents($legacyHandle) ?: '[]', true);
                if (!is_array($legacyReports)) {
                    throw new RuntimeException('Le fichier historique des signalements contient un JSON invalide.');
                }
                $legacyReports = array_values(array_filter(
                    $legacyReports,
                    static fn($report): bool => !is_array($report)
                        || strtolower((string) ($report['id'] ?? '')) !== $reportId
                ));
                $legacyJson = json_encode($legacyReports, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($legacyJson === false || !ftruncate($legacyHandle, 0)) {
                    throw new RuntimeException('Impossible de supprimer le signalement historique.');
                }
                rewind($legacyHandle);
                if (fwrite($legacyHandle, $legacyJson) !== strlen($legacyJson) || !fflush($legacyHandle)) {
                    throw new RuntimeException('Impossible d’enregistrer la suppression historique.');
                }
            } finally {
                flock($legacyHandle, LOCK_UN);
                fclose($legacyHandle);
            }
        }

        if (is_file($reportPath) && !unlink($reportPath)) {
            throw new RuntimeException('Impossible de supprimer le fichier du signalement.');
        }

        return true;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
