<?php
require_once __DIR__ . '/report_storage.php';

try {
    $reports = jcm_list_reports(true);
} catch (Throwable $exception) {
    error_log('Admin report list error: ' . $exception->getMessage());
    $reports = [];
    $reportLoadError = true;
}

$reportConversation = null;
$requestedReportId = strtolower(trim((string) ($_GET['report'] ?? '')));
if ($requestedReportId !== '') {
    try {
        $reportConversation = jcm_load_report($requestedReportId);
        if ($reportConversation !== null) {
            $reportConversation = jcm_update_report($requestedReportId, static function (array $report): array {
                $report['admin_read_at'] = jcm_report_timestamp();
                return $report;
            });
            foreach ($reports as &$report) {
                if (($report['id'] ?? '') === $requestedReportId && $reportConversation !== null) {
                    $report = $reportConversation;
                    break;
                }
            }
            unset($report);
        }
    } catch (Throwable $exception) {
        error_log('Admin report conversation error: ' . $exception->getMessage());
        $reportLoadError = true;
    }
}

$reportUnreadCount = jcm_count_unread_reports($reports, 'admin');
$reportIsAdmin = true;
$reportBackUrl = 'reports.php';
