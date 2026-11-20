<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        flash('error', 'Your registration request could not be verified. Please try again.');
        redirect('signup');
    }

    $rollNumber = strtoupper(post_string('roll_number'));
    $name = post_string('name');
    $branch = post_string('branch');
    $section = strtoupper(post_string('section'));
    $phoneInput = post_string('phone');
    $phone = preg_replace('/[\s()-]/', '', $phoneInput);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (
        !preg_match('/^[A-Z0-9][A-Z0-9_-]{1,29}$/', $rollNumber) ||
        $name === '' || strlen($name) > 100 ||
        $branch === '' || strlen($branch) > 80 ||
        !preg_match('/^[A-Z0-9-]{1,20}$/', $section) ||
        !is_string($phone) || !preg_match('/^\+?[0-9]{7,15}$/', $phone) ||
        !is_string($password) || strlen($password) < 12 || strlen($password) > 72 ||
        !is_string($confirmPassword) || !hash_equals($password, $confirmPassword)
    ) {
        flash('error', 'Check each field. Passwords must match and be 12–72 characters.');
        redirect('signup');
    }

    try {
        ensure_student_roll_index();
        if (collection('students')->findOne(['roll_number' => $rollNumber])) {
            flash('error', 'That roll number is already registered. Please sign in.');
            redirect('login');
        }

        $result = collection('students')->insertOne([
            'roll_number' => $rollNumber,
            'name' => $name,
            'branch' => $branch,
            'section' => $section,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => new MongoDB\BSON\UTCDateTime(),
        ]);
        sign_in([
            '_id' => $result->getInsertedId(),
            'name' => $name,
            'roll_number' => $rollNumber,
        ], 'student');
        flash('success', 'Your student account has been created.');
        redirect('student_dashboard');
    } catch (Throwable $e) {
        if ((int)$e->getCode() === 11000) {
            flash('error', 'That roll number is already registered. Please sign in.');
        } else {
            error_log('Student registration failed: ' . $e->getMessage());
            flash('error', 'Your account could not be created. Please try again.');
        }
        redirect('signup');
    }
}
?>
<section class="auth-layout">
  <div class="panel form-panel auth-panel">
    <div class="panel-heading"><span class="eyebrow">STUDENT PORTAL</span><h2>Create your account</h2><p>Register once, then sign up for events with your college profile.</p></div>
    <form method="post" class="stack-form">
      <?= csrf_field() ?>
      <label>Roll number<input name="roll_number" maxlength="30" required></label>
      <label>Full name<input name="name" autocomplete="name" maxlength="100" required></label>
      <div class="form-row">
        <label>Branch<input name="branch" maxlength="80" placeholder="e.g. Computer Science" required></label>
        <label>Section<input name="section" maxlength="20" placeholder="e.g. A" required></label>
      </div>
      <label>Phone number<input type="tel" name="phone" autocomplete="tel" maxlength="24" placeholder="+91 9876543210" required></label>
      <label>Password<input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" required><small>Use 12 to 72 characters.</small></label>
      <label>Confirm password<input type="password" name="confirm_password" autocomplete="new-password" minlength="12" maxlength="72" required></label>
      <button class="btn btn-primary" type="submit">Create student account</button>
    </form>
    <p class="auth-switch">Already registered? <a href="index.php?page=login">Student login</a></p>
  </div>
  <div class="auth-aside"><span class="big-star">✦</span><h2>Make campus<br><em>memorable.</em></h2><p>Join events, build connections, and see all your registrations in one place.</p></div>
</section>
