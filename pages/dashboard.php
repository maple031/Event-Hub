<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }
$user = current_user();
if ($user && $user['role'] === 'admin'):
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
<section class="hero home-hero">
  <div><span class="pill">✦ COLLEGE EVENT HUB</span><h2>Make every college<br><em>moment count.</em></h2><p>Discover campus events, connect with your community, and keep every registration in one place.</p><a class="btn btn-light" href="index.php?page=events">Explore events <span>→</span></a></div>
  <div class="hero-art"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="hero-card"><span class="hero-icon">✦</span><strong>Ideas become<br>experiences.</strong><small>CREATE · CONNECT · CELEBRATE</small></div><span class="spark spark-a">✳</span><span class="spark spark-b">✦</span></div>
</section>
<section class="portal-grid">
  <article class="portal-card student-portal"><span class="quick-icon purple">♙</span><span class="eyebrow">STUDENT PORTAL</span><h2>Join the campus experience</h2><p>Sign in or create a student account to register for events and track your plans.</p><div class="portal-actions"><a class="btn btn-primary" href="index.php?page=login">Student login</a><a class="text-link" href="index.php?page=signup">Create account →</a></div></article>
  <article class="portal-card admin-portal"><span class="quick-icon orange">⚙</span><span class="eyebrow">ADMIN PORTAL</span><h2>Manage campus events</h2><p>Create events, monitor registrations, and review participant lists.</p><div class="portal-actions"><a class="btn btn-dark" href="index.php?page=admin_login">Admin login</a></div></article>
</section>
<section class="section-heading"><div><span class="eyebrow">FOR EVERYONE</span><h2>One hub for campus life</h2></div></section>
<section class="quick-grid">
  <a class="quick-card" href="index.php?page=events"><span class="quick-icon orange">▦</span><h3>Browse events</h3><p>Explore what's happening across campus.</p><b>View event calendar →</b></a>
  <a class="quick-card" href="index.php?page=signup"><span class="quick-icon green">＋</span><h3>Register as a student</h3><p>Create your account with your college details.</p><b>Get started →</b></a>
  <a class="quick-card" href="index.php?page=admin_login"><span class="quick-icon purple">☷</span><h3>Coordinate events</h3><p>Admins can manage events and participant registrations.</p><b>Admin portal →</b></a>
</section>
<?php endif; ?>
