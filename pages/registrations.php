<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }
ensure_registration_student_event_index();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post_string('action') === 'set_registration_status') {
    if (!csrf_is_valid()) {
        flash('error', 'Your request could not be verified. Please try again.');
        redirect('registrations');
    }

    $registrationId = object_id_from_string(post_string('registration_id'));
    $nextStatus = post_string('status');
    if (!$registrationId || !in_array($nextStatus, ['confirmed', 'rejected'], true)) {
        flash('error', 'Choose a valid registration action.');
        redirect('registrations');
    }

    try {
        $registration = collection('registrations')->findOne(['_id' => $registrationId]);
        if (!$registration || ($registration['status'] ?? 'confirmed') !== 'pending') {
            flash('error', 'Only pending registrations can be reviewed.');
            redirect('registrations');
        }
        $updated = collection('registrations')->updateOne(
            ['_id' => $registrationId, 'status' => 'pending'],
            ['$set' => ['status' => $nextStatus, 'reviewed_at' => new MongoDB\BSON\UTCDateTime()]]
        );
        if ($updated->getMatchedCount() !== 1) {
            flash('error', 'This registration was already reviewed.');
            redirect('registrations');
        }
        if ($nextStatus === 'rejected') {
            collection('events')->updateOne(
                ['_id' => $registration['event_id'], 'registered_count' => ['$gt' => 0]],
                ['$inc' => ['registered_count' => -1]]
            );
        }
        flash('success', $nextStatus === 'confirmed' ? 'Registration confirmed.' : 'Registration rejected.');
    } catch (Throwable $e) {
        error_log('Registration review failed: ' . $e->getMessage());
        flash('error', 'The registration could not be updated. Please try again.');
    }
    redirect('registrations');
}

$eventId = is_string($_GET['event_id'] ?? null) ? $_GET['event_id'] : '';
$eventObjectId = $eventId === '' ? null : object_id_from_string($eventId);
$eventInvalid = $eventId !== '' && $eventObjectId === null;
if ($eventInvalid) {
    flash('error', 'Choose a valid event filter.');
    redirect('registrations');
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
$registrationCount = collection('registrations')->countDocuments($query);
$registrations = collection('registrations')->find($query, ['sort' => ['registered_at' => -1]]);
$events = iterator_to_array(collection('events')->find([], ['sort' => ['event_date' => -1], 'limit' => 500]));
$branches = collection('students')->distinct('branch');
$sections = collection('students')->distinct('section');
$selectedEvent = $eventObjectId ? collection('events')->findOne(['_id' => $eventObjectId]) : null;
$exportQuery = http_build_query(array_filter([
    'page' => 'registrations',
    'export' => 'csv',
    'event_id' => $eventId,
    'q' => $search,
    'branch' => $branch,
    'section' => $section,
], static function ($value): bool { return $value !== ''; }));
?>
<section class="panel table-panel full-panel">
  <div class="panel-heading split">
    <div><span class="eyebrow">EVENT REGISTRATION MANAGEMENT</span><h2><?= $selectedEvent ? h($selectedEvent['event_name']) : 'All registrations' ?> <span class="count"><?= $registrationCount ?></span></h2><p>Review requests and view participant details.</p></div>
    <a class="btn btn-primary" href="index.php?<?= h($exportQuery) ?>">Export CSV</a>
  </div>
  <form method="get" class="filter-form">
    <input type="hidden" name="page" value="registrations">
    <label>Event<select name="event_id"><option value="">All events</option><?php foreach ($events as $event): ?><option value="<?= h((string)$event['_id']) ?>" <?= $eventId === (string)$event['_id'] ? 'selected' : '' ?>><?= h($event['event_name'] ?? '') ?></option><?php endforeach; ?></select></label>
    <label>Search roll/name<input name="q" value="<?= h($search) ?>" placeholder="Search students"></label>
    <label>Branch<select name="branch"><option value="">All branches</option><?php foreach ($branches as $value): if (!is_string($value) || $value === '') continue; ?><option value="<?= h($value) ?>" <?= $branch === $value ? 'selected' : '' ?>><?= h($value) ?></option><?php endforeach; ?></select></label>
    <label>Section<select name="section"><option value="">All sections</option><?php foreach ($sections as $value): if (!is_string($value) || $value === '') continue; ?><option value="<?= h($value) ?>" <?= $section === $value ? 'selected' : '' ?>><?= h($value) ?></option><?php endforeach; ?></select></label>
    <button class="btn btn-primary" type="submit">Filter</button>
  </form>
  <div class="table-wrap"><table><thead><tr><th>Roll number</th><th>Student</th><th>Branch / section</th><th>Phone</th><th>Event</th><th>Registered</th><th>Status</th><th>Review</th></tr></thead><tbody>
    <?php foreach ($registrations as $registration): $status = $registration['status'] ?? 'confirmed'; ?>
      <tr>
        <td><strong><?= h($registration['student_roll_number'] ?? '—') ?></strong></td>
        <td><?= h($registration['student_name'] ?? '') ?></td>
        <td><?= h($registration['student_branch'] ?? '—') ?> / <?= h($registration['student_section'] ?? '—') ?></td>
        <td><?= h($registration['student_phone'] ?? '—') ?></td>
        <td><?= h($registration['event_name'] ?? '') ?><small><?= h(($registration['event_date'] ?? '') . ' ' . ($registration['event_time'] ?? '')) ?></small></td>
        <td><?= isset($registration['registered_at']) ? h($registration['registered_at']->toDateTime()->format('d M Y, H:i')) : '—' ?></td>
        <td><span class="status-tag <?= h($status) ?>"><?= h(ucfirst($status)) ?></span></td>
        <td>
          <?php if ($status === 'pending'): ?>
            <div class="review-actions">
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="set_registration_status"><input type="hidden" name="registration_id" value="<?= h((string)$registration['_id']) ?>"><input type="hidden" name="status" value="confirmed"><button class="link-button" type="submit">Confirm</button></form>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="set_registration_status"><input type="hidden" name="registration_id" value="<?= h((string)$registration['_id']) ?>"><input type="hidden" name="status" value="rejected"><button class="link-button danger-text" type="submit">Reject</button></form>
            </div>
          <?php else: ?>—<?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$registrationCount): ?><tr><td colspan="8" class="empty">No registrations match your filters.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
