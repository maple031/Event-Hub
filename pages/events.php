<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }
$user = current_user();
$isAdmin = $user && $user['role'] === 'admin';
$action = post_string('action');
$redirectToEditor = static function (?string $id = null): void {
    header('Location: index.php?page=events' . ($id !== null ? '&edit=' . rawurlencode($id) : ''));
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['save_event', 'delete_event'], true)) {
    require_role('admin');
    if (!csrf_is_valid()) {
        flash('error', 'Your event request could not be verified. Please try again.');
        redirect('events');
    }

    $eventId = post_string('event_id');
    $eventObjectId = $eventId === '' ? null : object_id_from_string($eventId);
    if ($action === 'delete_event') {
        if ($eventObjectId === null) {
            flash('error', 'Choose a valid event to delete.');
            redirect('events');
        }

        try {
            $event = collection('events')->findOne(['_id' => $eventObjectId]);
            if (!$event) {
                flash('error', 'That event no longer exists.');
                redirect('events');
            }
            $deleted = collection('events')->deleteOne(['_id' => $eventObjectId]);
            if ($deleted->getDeletedCount() !== 1) {
                flash('error', 'That event could not be deleted.');
                redirect('events');
            }
            try {
                collection('registrations')->deleteMany(['event_id' => $eventObjectId]);
            } catch (Throwable $cleanupError) {
                error_log('Could not remove registrations for deleted event: ' . $cleanupError->getMessage());
                flash('error', 'The event was deleted, but registration history cleanup failed. Contact the database administrator.');
                redirect('events');
            }
            $oldPoster = safe_poster_path($event['poster'] ?? null);
            if ($oldPoster && is_file($oldPoster) && !unlink($oldPoster)) {
                error_log('Could not remove deleted event poster: ' . $oldPoster);
            }
            flash('success', 'Event and its registrations were deleted.');
        } catch (Throwable $e) {
            error_log('Event deletion failed: ' . $e->getMessage());
            flash('error', 'The event could not be deleted. Please try again.');
        }
        redirect('events');
    }

    $name = post_string('event_name');
    $description = post_string('description');
    $date = post_string('event_date');
    $time = post_string('event_time');
    $venue = post_string('venue');
    $deadlineInput = post_string('registration_deadline');
    $maxInput = post_string('max_participants');
    $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $parsedTime = DateTimeImmutable::createFromFormat('!H:i', $time);
    $eventStart = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
    $deadline = $deadlineInput === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $deadlineInput);
    $maxParticipants = $maxInput === '' ? null : filter_var($maxInput, FILTER_VALIDATE_INT);

    if (
        $name === '' || strlen($name) > 120 ||
        strlen($description) > 3000 ||
        !$parsedDate || $parsedDate->format('Y-m-d') !== $date ||
        !$parsedTime || $parsedTime->format('H:i') !== $time ||
        !$eventStart || $eventStart->format('Y-m-d H:i') !== $date . ' ' . $time ||
        $venue === '' || strlen($venue) > 160 ||
        ($deadlineInput !== '' && (!$deadline || $deadline->format('Y-m-d\TH:i') !== $deadlineInput || $deadline > $eventStart)) ||
        ($maxInput !== '' && ($maxParticipants === false || $maxParticipants < 1 || $maxParticipants > 100000))
    ) {
        flash('error', 'Check the event details, date, time, deadline, and participant limit.');
        $redirectToEditor($eventObjectId ? $eventId : null);
    }
    if ($eventId !== '' && $eventObjectId === null) {
        flash('error', 'Choose a valid event to edit.');
        redirect('events');
    }

    $existingEvent = $eventObjectId ? collection('events')->findOne(['_id' => $eventObjectId]) : null;
    if ($eventObjectId && !$existingEvent) {
        flash('error', 'That event no longer exists.');
        redirect('events');
    }
    if ($existingEvent && $maxParticipants !== null) {
        $activeCount = collection('registrations')->countDocuments([
            'event_id' => $eventObjectId,
            'status' => ['$ne' => 'rejected'],
        ]);
        if ($maxParticipants < $activeCount) {
            flash('error', 'The participant limit cannot be lower than the current non-rejected registrations.');
            $redirectToEditor($eventId);
        }
    }

    $newPoster = null;
    $upload = $_FILES['poster'] ?? null;
    if (is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($upload['tmp_name'] ?? null) || (int)($upload['size'] ?? 0) > 5 * 1024 * 1024) {
            flash('error', 'Poster upload failed or is larger than 5 MB.');
            $redirectToEditor($eventObjectId ? $eventId : null);
        }
        $image = @getimagesize($upload['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = is_array($image) ? ($image['mime'] ?? '') : '';
        if (!isset($extensions[$mime]) || ($image[0] * $image[1]) > 30000000) {
            flash('error', 'Choose a valid JPG, PNG, or WebP poster image (maximum 5 MB).');
            $redirectToEditor($eventObjectId ? $eventId : null);
        }
        $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'events';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            flash('error', 'The poster upload directory could not be created.');
            $redirectToEditor($eventObjectId ? $eventId : null);
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($upload['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
            flash('error', 'The poster could not be saved.');
            $redirectToEditor($eventObjectId ? $eventId : null);
        }
        $newPoster = 'uploads/events/' . $filename;
    }

    $eventData = [
        'event_name' => $name,
        'description' => $description,
        'event_date' => $date,
        'event_time' => $time,
        'venue' => $venue,
        'registration_deadline' => $deadlineInput === '' ? null : $deadlineInput,
        'max_participants' => $maxParticipants,
        'updated_at' => new MongoDB\BSON\UTCDateTime(),
    ];
    if ($newPoster !== null) $eventData['poster'] = $newPoster;

    try {
        if ($existingEvent) {
            collection('events')->updateOne(['_id' => $eventObjectId], ['$set' => $eventData]);
            $oldPoster = safe_poster_path($existingEvent['poster'] ?? null);
            if ($newPoster !== null && $oldPoster && is_file($oldPoster) && !unlink($oldPoster)) {
                error_log('Could not remove replaced event poster: ' . $oldPoster);
            }
            flash('success', 'Event updated successfully.');
        } else {
            $eventData['registered_count'] = 0;
            $eventData['created_at'] = new MongoDB\BSON\UTCDateTime();
            collection('events')->insertOne($eventData);
            flash('success', 'Event created successfully.');
        }
    } catch (Throwable $e) {
        if ($newPoster !== null) {
            $newPosterPath = safe_poster_path($newPoster);
            if ($newPosterPath && is_file($newPosterPath) && !unlink($newPosterPath)) {
                error_log('Could not remove unused event poster: ' . $newPosterPath);
            }
        }
        error_log('Event save failed: ' . $e->getMessage());
        flash('error', 'The event could not be saved. Please try again.');
    }
    redirect('events');
}

$editId = is_string($_GET['edit'] ?? null) ? $_GET['edit'] : '';
$editObjectId = $isAdmin && $editId !== '' ? object_id_from_string($editId) : null;
$editingEvent = $editObjectId ? collection('events')->findOne(['_id' => $editObjectId]) : null;
if ($isAdmin && $editId !== '' && (!$editObjectId || !$editingEvent)) {
    flash('error', 'That event could not be found.');
    redirect('events');
}
$eventQuery = $isAdmin ? [] : ['event_date' => ['$gte' => date('Y-m-d')]];
ensure_event_date_index();
$events = iterator_to_array(collection('events')->find($eventQuery, ['sort' => ['event_date' => 1, 'event_time' => 1]]));
if (!$isAdmin) {
    $events = array_values(array_filter($events, static function ($event): bool {
        $start = event_datetime($event);
        return $start !== null && $start > new DateTimeImmutable();
    }));
}
$registrationCounts = [];
foreach (collection('registrations')->aggregate([
    ['$match' => ['status' => ['$ne' => 'rejected']]],
    ['$group' => ['_id' => '$event_id', 'count' => ['$sum' => 1]]],
]) as $countRow) {
    $registrationCounts[(string)$countRow['_id']] = (int)$countRow['count'];
}
?>
<?php if ($isAdmin): ?>
<section class="content-grid event-management">
  <div class="panel form-panel">
    <div class="panel-heading"><span class="eyebrow"><?= $editingEvent ? 'UPDATE CAMPUS EVENT' : 'PLAN SOMETHING GREAT' ?></span><h2><?= $editingEvent ? 'Edit event' : 'Add an event' ?></h2><p><?= $editingEvent ? 'Update the event details and poster.' : 'Publish a new campus experience.' ?></p></div>
    <form method="post" enctype="multipart/form-data" class="stack-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_event">
      <?php if ($editingEvent): ?><input type="hidden" name="event_id" value="<?= h($editId) ?>"><?php endif; ?>
      <label>Event name<input name="event_name" maxlength="120" required value="<?= h($editingEvent['event_name'] ?? '') ?>"></label>
      <div class="form-row"><label>Event date<input type="date" name="event_date" required value="<?= h($editingEvent['event_date'] ?? '') ?>"></label><label>Event time<input type="time" name="event_time" required value="<?= h($editingEvent['event_time'] ?? '18:00') ?>"></label></div>
      <label>Venue<input name="venue" maxlength="160" required value="<?= h($editingEvent['venue'] ?? '') ?>"></label>
      <label>Description<textarea name="description" rows="3" maxlength="3000"><?= h($editingEvent['description'] ?? '') ?></textarea></label>
      <label>Registration deadline<input type="datetime-local" name="registration_deadline" value="<?= h($editingEvent['registration_deadline'] ?? '') ?>"><small>Leave blank to close registrations when the event begins.</small></label>
      <label>Maximum participants<input type="number" name="max_participants" min="1" max="100000" value="<?= h($editingEvent['max_participants'] ?? '') ?>"><small>Leave blank for no limit.</small></label>
      <label>Event poster<input type="file" name="poster" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, or WebP; up to 5 MB.</small></label>
      <?php if ($editingEvent && ($posterPath = safe_poster_path($editingEvent['poster'] ?? null)) && is_file($posterPath)): ?><img class="event-edit-poster" src="<?= h($editingEvent['poster']) ?>" alt="Current event poster"><?php endif; ?>
      <div class="form-actions"><button class="btn btn-primary" type="submit"><?= $editingEvent ? 'Save changes' : '＋ Create event' ?></button><?php if ($editingEvent): ?><a class="btn btn-secondary" href="index.php?page=events">Cancel edit</a><?php endif; ?></div>
    </form>
  </div>
  <div class="panel table-panel">
  <div class="panel-heading"><span class="eyebrow">EVENT MANAGEMENT</span><h2>All events <span class="count"><?= count($events) ?></span></h2></div>
    <div class="event-list">
      <?php foreach ($events as $event): $eventId = (string)$event['_id']; ?>
        <article class="event-row admin-event-row">
          <?php $posterPath = safe_poster_path($event['poster'] ?? null); ?>
          <?php if ($posterPath && is_file($posterPath)): ?><img class="event-poster-thumb" src="<?= h($event['poster']) ?>" alt=""><?php else: ?><div class="event-poster-thumb event-poster-placeholder">✦</div><?php endif; ?>
          <div class="event-info"><h3><?= h($event['event_name'] ?? '') ?></h3><p><?= h(($event['event_date'] ?? '') . ' ' . ($event['event_time'] ?? '')) ?> · <?= h($event['venue'] ?? '') ?></p><small><?= h($registrationCounts[$eventId] ?? 0) ?> registered<?= isset($event['max_participants']) && (int)$event['max_participants'] > 0 ? ' / ' . h($event['max_participants']) : '' ?></small></div>
          <div class="event-admin-actions"><a class="text-link" href="index.php?page=events&amp;edit=<?= h($eventId) ?>">Edit</a><a class="text-link" href="index.php?page=registrations&amp;event_id=<?= h($eventId) ?>">Participants</a><form method="post" onsubmit="return confirm('Delete this event and its registrations?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_event"><input type="hidden" name="event_id" value="<?= h($eventId) ?>"><button class="link-button danger-text" type="submit">Delete</button></form></div>
        </article>
      <?php endforeach; ?>
      <?php if (!$events): ?><p class="empty">No events yet. Add the first campus event.</p><?php endif; ?>
    </div>
  </div>
</section>
<?php else: ?>
<section class="section-heading"><div><span class="eyebrow">CAMPUS CALENDAR</span><h2>Upcoming events</h2></div></section>
<section class="student-event-grid">
  <?php foreach ($events as $event): ?>
    <?php $eventId = (string)$event['_id']; $posterPath = safe_poster_path($event['poster'] ?? null); ?>
    <article class="student-event-card">
      <?php if ($posterPath && is_file($posterPath)): ?><img class="student-event-poster" src="<?= h($event['poster']) ?>" alt="<?= h($event['event_name'] ?? 'Event') ?> poster"><?php else: ?><div class="student-event-placeholder">✦</div><?php endif; ?>
      <div class="student-event-body"><span class="eyebrow"><?= h(($event['event_date'] ?? '') . ' · ' . ($event['event_time'] ?? '')) ?></span><h3><?= h($event['event_name'] ?? '') ?></h3><p><?= h($event['description'] ?? '') ?></p><small>⌖ <?= h($event['venue'] ?? '') ?></small>
        <?php if ($user && $user['role'] === 'student'): ?><a class="btn btn-primary" href="index.php?page=register&amp;event=<?= h($eventId) ?>">Register</a><?php elseif (!$user): ?><a class="text-link" href="index.php?page=login">Student login to register →</a><?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if (!$events): ?><div class="panel empty">No upcoming events are listed yet.</div><?php endif; ?>
</section>
<?php endif; ?>
