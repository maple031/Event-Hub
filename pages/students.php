<?php
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='add_student') {
    $name=trim($_POST['name']??''); $email=strtolower(trim($_POST['email']??'')); $dept=trim($_POST['department']??''); $year=(int)($_POST['year']??0);
    if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || $dept==='' || $year<1 || $year>6) flash('error','Please enter valid details in every field.');
    else {
        try { collection('students')->insertOne(['name'=>$name,'email'=>$email,'department'=>$dept,'year'=>$year,'created_at'=>new MongoDB\BSON\UTCDateTime()]); flash('success','Student added successfully.'); }
        catch (Throwable $e) { flash('error','Could not add student. Check whether this email already exists.'); }
    }
    redirect('students');
}
$students = iterator_to_array(collection('students')->find([], ['sort'=>['created_at'=>-1], 'limit'=>200]));
?>
<section class="content-grid">
 <div class="panel form-panel"><div class="panel-heading"><span class="eyebrow">NEW RECORD</span><h2>Add a student</h2><p>Enter the student's campus details.</p></div>
 <form method="post" class="stack-form"><input type="hidden" name="action" value="add_student"><label>Full name<input name="name" required placeholder="e.g. Aanya Sharma"></label><label>Email address<input type="email" name="email" required placeholder="student@college.edu"></label><div class="form-row"><label>Department<input name="department" required placeholder="e.g. CSE"></label><label>Year<select name="year" required><option value="">Select</option><?php for($i=1;$i<=6;$i++): ?><option value="<?= $i ?>"><?= $i ?><?= $i===1?'st':($i===2?'nd':($i===3?'rd':'th')) ?> year</option><?php endfor; ?></select></label></div><button class="btn btn-primary">＋ Add student</button></form></div>
 <div class="panel table-panel"><div class="panel-heading split"><div><span class="eyebrow">DIRECTORY</span><h2>Students <span class="count"><?= count($students) ?></span></h2></div></div>
 <div class="table-wrap"><table><thead><tr><th>Student</th><th>Department</th><th>Year</th></tr></thead><tbody><?php foreach($students as $s): ?><tr><td><strong><?= h($s['name']??'') ?></strong><small><?= h($s['email']??'') ?></small></td><td><?= h($s['department']??'') ?></td><td><span class="tag"><?= h($s['year']??'') ?></span></td></tr><?php endforeach; ?><?php if(!$students): ?><tr><td colspan="3" class="empty">No students yet. Add your first student.</td></tr><?php endif; ?></tbody></table></div></div>
</section>
