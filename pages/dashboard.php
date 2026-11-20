<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }
$user = current_user();
if ($user && $user['role'] === 'admin'):
    $showEventBalloons = ($_SESSION['ui_effect'] ?? null) === 'event-balloons';
    if ($showEventBalloons) unset($_SESSION['ui_effect']);
    $studentsTotal = collection('students')->countDocuments(['roll_number' => ['$type' => 'string']]);
    $eventsTotal = count_docs('events');
    $registrationsTotal = count_docs('registrations');
    ensure_event_date_index();
    ensure_registration_student_event_index();
    $upcomingEvents = [];
    $now = new DateTimeImmutable();
    foreach (collection('events')->find(
        ['event_date' => ['$gte' => date('Y-m-d')]],
        ['sort' => ['event_date' => 1, 'event_time' => 1]]
    ) as $event) {
        $start = event_datetime($event);
        if ($start && $start > $now) $upcomingEvents[] = $event;
        if (count($upcomingEvents) === 5) break;
    }
    $recentRegistrations = iterator_to_array(collection('registrations')->find(
        [],
        ['sort' => ['registered_at' => -1], 'limit' => 8]
    ));
?>
<?php if ($showEventBalloons): ?>
<div class="event-balloon-toast" role="status" aria-live="polite">
  <span class="event-balloons" aria-hidden="true"><span>🎈</span><span>🎈</span><span>🎈</span></span>
  <span><strong>Event added!</strong><small>Your new campus event is on the calendar.</small></span>
</div>
<?php endif; ?>
<section class="section-heading"><div><span class="eyebrow">ADMINISTRATION</span><h2>Campus at a glance</h2></div><a class="btn btn-primary" href="index.php?page=events">＋ Manage events</a></section>
<section class="stats-grid">
  <article class="stat-card"><span class="stat-icon purple">♙</span><small>Registered students</small><strong><?= $studentsTotal ?></strong><span class="stat-note">Student accounts</span></article>
  <article class="stat-card"><span class="stat-icon orange">▦</span><small>Total events</small><strong><?= $eventsTotal ?></strong><span class="stat-note">In the event calendar</span></article>
  <article class="stat-card"><span class="stat-icon green">✓</span><small>Event registrations</small><strong><?= $registrationsTotal ?></strong><span class="stat-note">Including pending reviews</span></article>
</section>
<section class="admin-dashboard-grid">
  <div class="panel table-panel">
    <div class="panel-heading split"><div><span class="eyebrow">COMING UP</span><h2>Upcoming events</h2></div><a class="text-link" href="index.php?page=events">Manage →</a></div>
    <div class="event-list">
      <?php foreach ($upcomingEvents as $event): ?>
        <article class="event-row">
          <?php $posterPath = safe_poster_path($event['poster'] ?? null); ?>
          <?php if ($posterPath && is_file($posterPath)): ?><img class="event-poster-thumb" src="<?= h($event['poster']) ?>" alt=""><?php else: ?><div class="event-poster-thumb event-poster-placeholder">✦</div><?php endif; ?>
          <div class="event-info"><h3><?= h($event['event_name'] ?? '') ?></h3><p><?= h(($event['event_date'] ?? '') . ' ' . ($event['event_time'] ?? '')) ?> · <?= h($event['venue'] ?? '') ?></p></div>
        </article>
      <?php endforeach; ?>
      <?php if (!$upcomingEvents): ?><p class="empty">No upcoming events yet. Add one to get started.</p><?php endif; ?>
    </div>
  </div>
  <div class="panel table-panel">
    <div class="panel-heading split"><div><span class="eyebrow">LATEST ACTIVITY</span><h2>Recent registrations</h2></div><a class="text-link" href="index.php?page=registrations">View all →</a></div>
    <div class="recent-list">
      <?php foreach ($recentRegistrations as $registration): ?>
        <div class="recent-item"><div class="avatar"><?= h(strtoupper(substr($registration['student_name'] ?? 'S', 0, 1))) ?></div><div><strong><?= h($registration['student_name'] ?? 'Student') ?></strong><small>Roll <?= h($registration['student_roll_number'] ?? '—') ?> · <?= h($registration['event_name'] ?? 'Event') ?></small></div><span class="status-tag <?= h($registration['status'] ?? 'confirmed') ?>"><?= h(ucfirst($registration['status'] ?? 'confirmed')) ?></span></div>
      <?php endforeach; ?>
      <?php if (!$recentRegistrations): ?><p class="empty">No student registrations yet.</p><?php endif; ?>
    </div>
  </div>
</section>
<?php else: ?>
<?php
$homeEvents = [];
if ($dbReady) {
    ensure_event_date_index();
    $now = new DateTimeImmutable();
    foreach (collection('events')->find(
        ['event_date' => ['$gte' => date('Y-m-d')]],
        ['sort' => ['event_date' => 1, 'event_time' => 1]]
    ) as $event) {
        $start = event_datetime($event);
        if ($start && $start > $now) $homeEvents[] = $event;
        if (count($homeEvents) === 3) break;
    }
}
?>
<section class="hero home-hero">
  <div class="home-hero-copy">
    <span class="pill">✦ COLLEGE EVENT HUB</span>
    <h2>“The best college memories are made <em>together.”</em></h2>
    <p>From music nights to cultural celebrations, find your next reason to get together and enjoy campus life.</p>
    <div class="home-hero-actions"><a class="btn btn-light" href="index.php?page=events">Explore events <span>→</span></a><a class="home-student-link" href="index.php?page=signup">Join the fun</a></div>
  </div>
  <div class="home-photo-collage" aria-label="Scenes from college events">
    <figure class="home-photo-card home-photo-tall">
      <img src="https://images.pexels.com/photos/37882408/pexels-photo-37882408.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=900" alt="Graduates celebrating together at a campus event" fetchpriority="high" referrerpolicy="no-referrer">
      <figcaption>Campus celebrations</figcaption>
    </figure>
    <figure class="home-photo-card">
      <img src="https://images.pexels.com/photos/1190297/pexels-photo-1190297.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=700" alt="A crowd enjoying live music together" loading="lazy" referrerpolicy="no-referrer">
      <figcaption>Music nights</figcaption>
    </figure>
    <figure class="home-photo-card">
      <img src="https://images.pexels.com/photos/40350779/pexels-photo-40350779.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=700" alt="A group of friends posing together at a college event" loading="lazy" referrerpolicy="no-referrer">
      <figcaption>Moments with friends</figcaption>
    </figure>
  </div>
</section>
<?php if ($dbReady): ?>
<section class="home-events">
  <div class="section-heading"><div><span class="eyebrow">MAKE ROOM FOR FUN</span><h2>Coming up on campus</h2></div><a class="text-link" href="index.php?page=events">See all events →</a></div>
  <?php if ($homeEvents): ?>
    <div class="home-event-grid">
      <?php foreach ($homeEvents as $event): $posterPath = safe_poster_path($event['poster'] ?? null); ?>
        <article class="home-event-card">
          <?php if ($posterPath && is_file($posterPath)): ?><img src="<?= h($event['poster']) ?>" alt="<?= h($event['event_name'] ?? 'Event') ?> poster" loading="lazy"><?php else: ?><div class="home-event-art" aria-hidden="true">✦</div><?php endif; ?>
          <div class="home-event-info"><span class="eyebrow"><?= h(($event['event_date'] ?? '') . ' · ' . ($event['event_time'] ?? '')) ?></span><h3><?= h($event['event_name'] ?? '') ?></h3><p><?= h($event['description'] ?? '') ?></p><small>⌖ <?= h($event['venue'] ?? '') ?></small></div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="home-events-empty"><span class="quick-icon purple">✦</span><div><h3>Your next campus memory is on its way.</h3><p>Check out the event calendar and come back soon for something exciting.</p></div><a class="btn btn-primary" href="index.php?page=events">Explore the calendar</a></div>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php endif; ?>
