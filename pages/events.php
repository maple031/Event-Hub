<?php
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add_event') {
 $name=trim($_POST['event_name']??''); $date=$_POST['event_date']??''; $venue=trim($_POST['venue']??''); $description=trim($_POST['description']??'');
 if($name===''||$date===''||$venue==='') flash('error','Event name, date and venue are required.');
 else { try { $d=DateTime::createFromFormat('Y-m-d',$date); if(!$d) throw new Exception(); collection('events')->insertOne(['event_name'=>$name,'event_date'=>$date,'venue'=>$venue,'description'=>$description,'created_at'=>new MongoDB\BSON\UTCDateTime()]); flash('success','Event created successfully.'); } catch(Throwable $e){flash('error','Please check the event date and try again.');} }
 redirect('events');
}
$events=iterator_to_array(collection('events')->find([],['sort'=>['event_date'=>1],'limit'=>200]));
?>
<section class="content-grid">
 <div class="panel form-panel"><div class="panel-heading"><span class="eyebrow">PLAN SOMETHING GREAT</span><h2>Create an event</h2><p>Publish a new campus experience.</p></div><form method="post" class="stack-form"><input type="hidden" name="action" value="add_event"><label>Event name<input name="event_name" required placeholder="e.g. Innovation Fest 2026"></label><label>Event date<input type="date" name="event_date" required></label><label>Venue<input name="venue" required placeholder="e.g. Main Auditorium"></label><label>Description<textarea name="description" rows="3" placeholder="A short description of the event..."></textarea></label><button class="btn btn-primary">＋ Create event</button></form></div>
 <div class="panel table-panel"><div class="panel-heading"><span class="eyebrow">CAMPUS CALENDAR</span><h2>Upcoming events <span class="count"><?= count($events) ?></span></h2></div><div class="event-list"><?php foreach($events as $e): ?><article class="event-row"><div class="date-tile"><strong><?= h(date('d',strtotime($e['event_date']??'now'))) ?></strong><small><?= h(date('M',strtotime($e['event_date']??'now'))) ?></small></div><div class="event-info"><h3><?= h($e['event_name']??'') ?></h3><p>⌖ <?= h($e['venue']??'') ?></p><small><?= h($e['description']??'') ?></small></div></article><?php endforeach; ?><?php if(!$events): ?><p class="empty">No events created yet. Your next big idea starts here.</p><?php endif; ?></div></div>
</section>
