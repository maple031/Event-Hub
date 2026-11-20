<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }
$user = current_user();
$registrations = iterator_to_array(collection('registrations')->find(
    ['student_id' => new MongoDB\BSON\ObjectId($user['id'])],
    ['sort' => ['registered_at' => -1], 'limit' => 100]
));
?>
<section class="panel table-panel full-panel">
  <div class="panel-heading"><span class="eyebrow">YOUR EVENT PLANS</span><h2>My registrations <span class="count"><?= count($registrations) ?></span></h2><p>Events you have signed up for.</p></div>
  <div class="table-wrap"><table><thead><tr><th>Event</th><th>Date & time</th><th>Venue</th><th>Registered</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($registrations as $registration): ?>
      <?php $status = $registration['status'] ?? 'confirmed'; ?>
      <tr><td><strong><?= h($registration['event_name'] ?? '') ?></strong></td><td><?= h(($registration['event_date'] ?? '') . ' ' . ($registration['event_time'] ?? '')) ?></td><td><?= h($registration['venue'] ?? '') ?></td><td><?= isset($registration['registered_at']) ? h($registration['registered_at']->toDateTime()->format('d M Y, H:i')) : '—' ?></td><td><span class="status-tag <?= h($status) ?>"><?= h(ucfirst($status)) ?></span></td></tr>
    <?php endforeach; ?>
    <?php if (!$registrations): ?><tr><td colspan="5" class="empty">You have not registered for an event yet. <a href="index.php?page=events">Explore events</a>.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
