<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }
$user = current_user();
$studentId = new MongoDB\BSON\ObjectId($user['id']);
$student = collection('students')->findOne(['_id' => $studentId]);
ensure_event_date_index();
ensure_registration_student_event_index();
$upcomingEvents = iterator_to_array(collection('events')->find(
    ['event_date' => ['$gte' => date('Y-m-d')]],
    ['sort' => ['event_date' => 1, 'event_time' => 1]]
));
$upcomingEvents = array_values(array_filter($upcomingEvents, static function ($event): bool {
    $start = event_datetime($event);
    return $start !== null && $start > new DateTimeImmutable();
}));
$myRegistrations = iterator_to_array(collection('registrations')->find(
    ['student_id' => $studentId],
    ['sort' => ['registered_at' => -1]]
));
$registeredEvents = [];
foreach ($myRegistrations as $registration) {
    $registeredEvents[(string)$registration['event_id']] = $registration;
}
?>
<section class="student-welcome">
  <div><span class="eyebrow">STUDENT DASHBOARD</span><h2>Welcome, <?= h($student['name'] ?? $user['name']) ?></h2><p><?= h($student['branch'] ?? 'Student') ?> · Section <?= h($student['section'] ?? '—') ?> · Roll <?= h($student['roll_number'] ?? $user['roll_number']) ?></p></div>
  <div class="welcome-mark">✦</div>
</section>
<section class="section-heading"><div><span class="eyebrow">YOUR NEXT EXPERIENCE</span><h2>Upcoming events <span class="count"><?= count($upcomingEvents) ?></span></h2></div></section>
<section class="student-event-grid">
  <?php foreach ($upcomingEvents as $event): ?>
    <?php
      $eventId = (string)$event['_id'];
      $registration = $registeredEvents[$eventId] ?? null;
      $posterPath = safe_poster_path($event['poster'] ?? null);
      $posterExists = $posterPath && is_file($posterPath);
      $registrationCount = (int)($event['registered_count'] ?? 0);
    ?>
    <article class="student-event-card">
      <?php if ($posterExists): ?><img class="student-event-poster" src="<?= h($event['poster']) ?>" alt="<?= h($event['event_name'] ?? 'Event') ?> poster"><?php else: ?><div class="student-event-placeholder">✦</div><?php endif; ?>
      <div class="student-event-body"><span class="eyebrow"><?= h(($event['event_date'] ?? '') . ' · ' . ($event['event_time'] ?? '')) ?></span><h3><?= h($event['event_name'] ?? '') ?></h3><p><?= h($event['description'] ?? '') ?></p><small>⌖ <?= h($event['venue'] ?? '') ?></small>
        <?php if ($registration): ?>
          <span class="status-tag <?= h($registration['status'] ?? 'confirmed') ?>"><?= h(ucfirst($registration['status'] ?? 'confirmed')) ?></span>
        <?php elseif (!event_registration_is_open($event)): ?>
          <span class="notice-inline">Registration closed</span>
        <?php elseif (isset($event['max_participants']) && (int)$event['max_participants'] > 0 && $registrationCount >= (int)$event['max_participants']): ?>
          <span class="notice-inline">Event full</span>
        <?php else: ?>
          <form method="post" action="index.php?page=register" class="inline-register">
            <?= csrf_field() ?><input type="hidden" name="action" value="register"><input type="hidden" name="event_id" value="<?= h($eventId) ?>">
            <button type="submit" class="btn btn-primary">Register for event</button>
          </form>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if (!$upcomingEvents): ?><div class="panel empty">There are no upcoming events right now. Check back soon.</div><?php endif; ?>
</section>
<section class="section-heading section-heading-spaced"><div><span class="eyebrow">YOUR ACTIVITY</span><h2>My registered events <span class="count"><?= count($myRegistrations) ?></span></h2></div><a class="text-link" href="index.php?page=my_registrations">View history →</a></section>
<section class="panel table-panel">
  <div class="table-wrap"><table><thead><tr><th>Event</th><th>Date & time</th><th>Venue</th><th>Registration status</th></tr></thead><tbody>
    <?php foreach (array_slice($myRegistrations, 0, 5) as $registration): ?>
      <tr><td><strong><?= h($registration['event_name'] ?? '') ?></strong></td><td><?= h(($registration['event_date'] ?? '') . ' ' . ($registration['event_time'] ?? '')) ?></td><td><?= h($registration['venue'] ?? '') ?></td><td><span class="status-tag <?= h($registration['status'] ?? 'confirmed') ?>"><?= h(ucfirst($registration['status'] ?? 'confirmed')) ?></span></td></tr>
    <?php endforeach; ?>
    <?php if (!$myRegistrations): ?><tr><td colspan="4" class="empty">You have not registered for an event yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
