<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        flash('error', 'Your sign-in request could not be verified. Please try again.');
        redirect('admin_login');
    }

    $username = strtolower(post_string('username'));
    $password = $_POST['password'] ?? '';
    if ($username === '' || !is_string($password) || $password === '' || strlen($password) > 72) {
        flash('error', 'Enter your admin username and password.');
        redirect('admin_login');
    }

    try {
        ensure_admin_username_index();
        $admin = collection('admins')->findOne(['username' => $username]);
        if (!$admin || !password_verify($password, $admin['password_hash'] ?? '')) {
            flash('error', 'Username or password is incorrect.');
            redirect('admin_login');
        }

        sign_in($admin, 'admin');
        flash('success', 'Welcome, ' . $admin['name'] . '.');
        redirect('dashboard');
    } catch (Throwable $e) {
        error_log('Admin sign-in failed: ' . $e->getMessage());
        flash('error', 'Admin sign-in is temporarily unavailable. Please try again.');
        redirect('admin_login');
    }
}
?>
<section class="auth-layout">
  <div class="panel form-panel auth-panel">
    <div class="panel-heading"><span class="eyebrow">ADMINISTRATION</span><h2>Admin login</h2><p>Sign in to manage college events and registrations.</p></div>
    <form method="post" class="stack-form">
      <?= csrf_field() ?>
      <label>Admin username<input name="username" autocomplete="username" maxlength="60" required></label>
      <label>Password<input type="password" name="password" autocomplete="current-password" maxlength="72" required></label>
      <button class="btn btn-primary" type="submit">Admin login</button>
    </form>
  </div>
  <div class="auth-aside"><span class="big-star">✦</span><h2>Bring your campus<br><em>together.</em></h2><p>Manage events, review registrations, and help students find their next experience.</p></div>
</section>
