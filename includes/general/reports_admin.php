<?php
$reportsFile = __DIR__ . '/../../storage/reports/reports.json';
$reports = [];

if (is_file($reportsFile)) {
    $decodedReports = json_decode((string) file_get_contents($reportsFile), true);
    if (is_array($decodedReports)) {
        $reports = $decodedReports;
    }
}

usort($reports, static fn(array $left, array $right): int => strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? '')));
$reportThreads = [];
foreach ($reports as $report) {
    $email = strtolower(trim((string) ($report['email'] ?? '')));
    if ($email === '') {
        continue;
    }
    $reportThreads[$email][] = $report;
}
uksort($reportThreads, static function (string $left, string $right) use ($reportThreads): int {
    return strcmp((string) ($reportThreads[$right][0]['created_at'] ?? ''), (string) ($reportThreads[$left][0]['created_at'] ?? ''));
});
