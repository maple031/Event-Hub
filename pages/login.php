<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        flash('error', 'Your sign-in request could not be verified. Please try again.');
        redirect('login');
    }

    $rollNumber = strtoupper(post_string('roll_number'));
    $password = $_POST['password'] ?? '';
    if ($rollNumber === '' || !is_string($password) || $password === '' || strlen($password) > 72) {
        flash('error', 'Enter your roll number and password.');
        redirect('login');
    }

    try {
        ensure_student_roll_index();
        $student = collection('students')->findOne(['roll_number' => $rollNumber]);
        if (!$student || !password_verify($password, $student['password_hash'] ?? '')) {
            flash('error', 'Roll number or password is incorrect.');
            redirect('login');
        }

        sign_in($student, 'student');
        flash('success', 'Welcome back, ' . $student['name'] . '.');
        redirect('student_dashboard');
    } catch (Throwable $e) {
        error_log('Student sign-in failed: ' . $e->getMessage());
        flash('error', 'Student sign-in is temporarily unavailable. Please try again.');
        redirect('login');
    }
}
?>
<section class="auth-layout">
  <div class="panel form-panel auth-panel">
    <div class="panel-heading"><span class="eyebrow">STUDENT PORTAL</span><h2>Student login</h2><p>Sign in with your college roll number to find events and manage your registrations.</p></div>
    <form method="post" class="stack-form">
      <?= csrf_field() ?>
      <label>Roll number<input name="roll_number" maxlength="30" autocomplete="username" required></label>
      <label>Password<input type="password" name="password" maxlength="72" autocomplete="current-password" required></label>
      <button class="btn btn-primary" type="submit">Student login</button>
    </form>
    <p class="auth-switch">New student? <a href="index.php?page=signup">Create an account</a></p>
  </div>
  <div class="auth-aside"><span class="big-star">✦</span><h2>Find your next<br><em>great experience.</em></h2><p>Discover college events, save your seat, and keep your registration history together.</p></div>
</section>
