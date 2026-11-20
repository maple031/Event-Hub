<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }
$user = current_user();
$studentId = new MongoDB\BSON\ObjectId($user['id']);
$student = collection('students')->findOne(['_id' => $studentId]);
if (!$student) {
    sign_out();
    flash('error', 'Your student account could not be found. Please sign in again.');
    redirect('login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post_string('action') === 'register') {
    if (!csrf_is_valid()) {
        flash('error', 'Your registration request could not be verified. Please try again.');
        redirect('student_dashboard');
    }

    $eventId = object_id_from_string(post_string('event_id'));
    if (!$eventId) {
        flash('error', 'Choose a valid event.');
        redirect('student_dashboard');
    }

    $reservedSeat = false;
    try {
        ensure_registration_student_event_index();
        $event = collection('events')->findOne(['_id' => $eventId]);
        if (!$event || !event_registration_is_open($event)) {
            flash('error', 'Registration for that event is closed.');
            redirect('student_dashboard');
        }

        if (collection('registrations')->findOne(['student_id' => $studentId, 'event_id' => $eventId])) {
            flash('error', 'You are already registered for this event.');
            redirect('student_dashboard');
        }

        if (!is_int($event['registered_count'] ?? null) || $event['registered_count'] < 0) {
            $activeCount = collection('registrations')->countDocuments([
                'event_id' => $eventId,
                'status' => ['$ne' => 'rejected'],
            ]);
            collection('events')->updateOne(
                ['_id' => $eventId, 'registered_count' => $event['registered_count'] ?? null],
                ['$set' => ['registered_count' => $activeCount]]
            );
            $event = collection('events')->findOne(['_id' => $eventId]);
        }
        if (!$event) {
            flash('error', 'That event is no longer available.');
            redirect('student_dashboard');
        }

        $limit = isset($event['max_participants']) ? (int)$event['max_participants'] : null;
        $eventFilter = ['_id' => $eventId];
        if ($limit !== null && $limit > 0) $eventFilter['registered_count'] = ['$lt' => $limit];
        $reservation = collection('events')->updateOne($eventFilter, ['$inc' => ['registered_count' => 1]]);
        if ($reservation->getMatchedCount() !== 1) {
            flash('error', 'This event has reached its participant limit.');
            redirect('student_dashboard');
        }
        $reservedSeat = true;

        collection('registrations')->insertOne([
            'registration_key' => (string)$studentId . ':' . (string)$eventId,
            'student_id' => $studentId,
            'event_id' => $eventId,
            'student_roll_number' => $student['roll_number'],
            'student_name' => $student['name'],
            'student_branch' => $student['branch'],
            'student_section' => $student['section'],
            'student_year' => $student['year'] ?? '',
            'student_phone' => $student['phone'],
            'event_name' => $event['event_name'],
            'event_date' => $event['event_date'],
            'event_time' => $event['event_time'] ?? '',
            'venue' => $event['venue'],
            'status' => 'pending',
            'registered_at' => new MongoDB\BSON\UTCDateTime(),
        ]);
        $reservedSeat = false;
        $_SESSION['ui_effect'] = 'paper-rocket';
        flash('success', 'Your registration was sent to the admin for confirmation.');
    } catch (Throwable $e) {
        if ($reservedSeat) {
            try {
                collection('events')->updateOne(['_id' => $eventId], ['$inc' => ['registered_count' => -1]]);
            } catch (Throwable $cleanupError) {
                error_log('Could not release event capacity after failed registration: ' . $cleanupError->getMessage());
            }
        }
        if ((int)$e->getCode() === 11000) {
            flash('error', 'You are already registered for this event.');
        } else {
            error_log('Event registration failed: ' . $e->getMessage());
            flash('error', 'Registration could not be completed. Please try again.');
        }
    }
    redirect('student_dashboard');
}

$selectedEventId = is_string($_GET['event'] ?? null) ? $_GET['event'] : '';
$events = iterator_to_array(collection('events')->find(
    ['event_date' => ['$gte' => date('Y-m-d')]],
    ['sort' => ['event_date' => 1, 'event_time' => 1]]
));
$events = array_values(array_filter($events, static function ($event): bool {
    $start = event_datetime($event);
    return $start !== null && $start > new DateTimeImmutable();
}));
?>
<section class="panel form-panel register-panel">
  <div class="panel-heading"><span class="eyebrow">SAVE YOUR SEAT</span><h2>Register for an event</h2><p>Choose an upcoming event. Your request will be sent to an admin for confirmation.</p></div>
  <?php if (!$events): ?><div class="notice">There are no upcoming events to register for.</div><?php endif; ?>
  <form method="post" class="stack-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="register">
    <label>Select event<select name="event_id" required><option value="">Choose an event</option>
      <?php foreach ($events as $event): $eventId = (string)$event['_id']; ?>
        <?php $full = isset($event['max_participants']) && (int)$event['max_participants'] > 0 && (int)($event['registered_count'] ?? 0) >= (int)$event['max_participants']; ?>
        <option value="<?= h($eventId) ?>" <?= $selectedEventId === $eventId ? 'selected' : '' ?> <?= $full || !event_registration_is_open($event) ? 'disabled' : '' ?>><?= h($event['event_name']) ?> · <?= h($event['event_date']) ?> <?= h($event['event_time'] ?? '') ?><?= $full ? ' · Full' : '' ?></option>
      <?php endforeach; ?>
    </select></label>
    <button class="btn btn-primary" type="submit" <?= !$events ? 'disabled' : '' ?>>Send registration request</button>
  </form>
</section>
