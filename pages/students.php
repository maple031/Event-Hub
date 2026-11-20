<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }
$students = iterator_to_array(collection('students')->find([], ['sort' => ['created_at' => -1]]));
?>
<section class="panel table-panel full-panel">
  <div class="panel-heading"><span class="eyebrow">STUDENT DIRECTORY</span><h2>Registered students <span class="count"><?= count($students) ?></span></h2><p>Student profiles are created through the student registration page.</p></div>
  <div class="table-wrap"><table><thead><tr><th>Roll number</th><th>Student</th><th>Branch</th><th>Section</th><th>Phone</th><th>Joined</th></tr></thead><tbody>
    <?php foreach ($students as $student): ?>
      <tr><td><strong><?= h($student['roll_number'] ?? 'Legacy profile') ?></strong></td><td><?= h($student['name'] ?? '') ?></td><td><?= h($student['branch'] ?? $student['department'] ?? '—') ?></td><td><?= h($student['section'] ?? '—') ?></td><td><?= h($student['phone'] ?? '—') ?></td><td><?= isset($student['created_at']) ? h($student['created_at']->toDateTime()->format('d M Y')) : '—' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$students): ?><tr><td colspan="6" class="empty">No student accounts have been created yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
