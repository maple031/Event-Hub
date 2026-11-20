<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }
if (ob_get_level() > 0) ob_end_clean();
require_role('admin');
ensure_registration_student_event_index();
$eventId = is_string($_GET['event_id'] ?? null) ? $_GET['event_id'] : '';
$eventObjectId = $eventId === '' ? null : object_id_from_string($eventId);
$eventInvalid = $eventId !== '' && $eventObjectId === null;
if ($eventInvalid) {
    http_response_code(400);
    exit('Invalid event filter.');
}
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$search = substr($search, 0, 100);
$branch = is_string($_GET['branch'] ?? null) ? trim($_GET['branch']) : '';
$section = is_string($_GET['section'] ?? null) ? trim($_GET['section']) : '';
$query = [];
if ($eventObjectId) $query['event_id'] = $eventObjectId;
if ($search !== '') {
    $pattern = preg_quote($search, '/');
    $query['$or'] = [
        ['student_roll_number' => ['$regex' => $pattern, '$options' => 'i']],
        ['student_name' => ['$regex' => $pattern, '$options' => 'i']],
    ];
}
if ($branch !== '') $query['student_branch'] = $branch;
if ($section !== '') $query['student_section'] = $section;

$safeCsv = static function ($value): string {
    $value = (string)$value;
    return preg_match('/^\s*[=+\-@]/u', $value) ? "'" . $value : $value;
};
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="event-registrations-' . date('Ymd-His') . '.csv"');
header('Cache-Control: no-store');
$output = fopen('php://output', 'wb');
fputcsv($output, ['Roll number', 'Student name', 'Branch', 'Section', 'Phone', 'Event', 'Event date', 'Event time', 'Venue', 'Status', 'Registered at']);
foreach (collection('registrations')->find($query, ['sort' => ['registered_at' => -1]]) as $registration) {
    fputcsv($output, array_map($safeCsv, [
        $registration['student_roll_number'] ?? '',
        $registration['student_name'] ?? '',
        $registration['student_branch'] ?? '',
        $registration['student_section'] ?? '',
        $registration['student_phone'] ?? '',
        $registration['event_name'] ?? '',
        $registration['event_date'] ?? '',
        $registration['event_time'] ?? '',
        $registration['venue'] ?? '',
        $registration['status'] ?? 'confirmed',
        isset($registration['registered_at']) ? $registration['registered_at']->toDateTime()->format('Y-m-d H:i:s') : '',
    ]), ',', '"', '\\');
}
fclose($output);
