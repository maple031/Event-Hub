<?php
$studentList=iterator_to_array(collection('students')->find([],['sort'=>['name'=>1]]));
$eventList=iterator_to_array(collection('events')->find([],['sort'=>['event_date'=>1]]));
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='register') {
 try {
  $sid=trim($_POST['student_id']??''); $eid=trim($_POST['event_id']??'');
  $student=collection('students')->findOne(['_id'=>new MongoDB\BSON\ObjectId($sid)]);
  $event=collection('events')->findOne(['_id'=>new MongoDB\BSON\ObjectId($eid)]);
  if(!$student||!$event) flash('error','Choose a valid student and event.');
  else {
   $existing=collection('registrations')->findOne(['student_id'=>$student['_id'],'event_id'=>$event['_id']]);
   if($existing) flash('error','This student is already registered for this event.');
   else { collection('registrations')->insertOne(['student_id'=>$student['_id'],'event_id'=>$event['_id'],'student_name'=>$student['name'],'student_email'=>$student['email'],'event_name'=>$event['event_name'],'event_date'=>$event['event_date'],'registered_at'=>new MongoDB\BSON\UTCDateTime()]); flash('success','Registration completed! The student is on the event list.'); }
  }
 } catch(Throwable $e){flash('error','Registration could not be completed. Please check your selection.');}
 redirect('register');
}
?>
<section class="register-layout"><div class="panel form-panel register-panel"><div class="panel-heading"><span class="eyebrow">SAVE A SEAT</span><h2>Event registration</h2><p>Select a student and the event they want to attend.</p></div>
<?php if(!$studentList||!$eventList): ?><div class="notice">Add at least one student and one event before registering.</div><?php endif; ?>
<form method="post" class="stack-form"><input type="hidden" name="action" value="register"><label>Select student<select name="student_id" required><option value="">Choose a student</option><?php foreach($studentList as $s): ?><option value="<?= h((string)$s['_id']) ?>"><?= h($s['name']) ?> · <?= h($s['department']) ?></option><?php endforeach; ?></select></label><label>Select event<select name="event_id" required><option value="">Choose an event</option><?php foreach($eventList as $e): ?><option value="<?= h((string)$e['_id']) ?>"><?= h($e['event_name']) ?> · <?= h($e['event_date']) ?></option><?php endforeach; ?></select></label><button class="btn btn-primary" <?= (!$studentList||!$eventList)?'disabled':'' ?>>Confirm registration →</button></form></div><div class="register-aside"><span class="big-star">✦</span><h2>Good things happen<br>when we <em>come together.</em></h2><p>One registration brings a student closer to a new idea, a new skill, or a new connection.</p><div class="aside-line"></div><small>CREATE MEMORIES. BUILD COMMUNITY.</small></div></section>
