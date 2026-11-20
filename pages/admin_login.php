<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(404); exit; }

$canCreateFirstAdmin = false;
$adminSetupError = false;
if ($dbReady) {
    try {
        ensure_admin_username_index();
        $canCreateFirstAdmin = collection('admins')->countDocuments() === 0;
    } catch (Throwable $e) {
        error_log('Admin setup check failed: ' . $e->getMessage());
        $adminSetupError = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        flash('error', 'Your request could not be verified. Please try again.');
        redirect('admin_login');
    }

    $action = post_string('action');
    if ($action === 'create_first_admin') {
        $name = preg_replace('/\s+/u', ' ', post_string('admin_name'));
        $password = $_POST['password'] ?? '';
        if (
            !$dbReady ||
            !is_string($name) ||
            $name === '' ||
            strlen($name) > 100 ||
            preg_match('/[\x00-\x1F\x7F]/', $name) ||
            !is_string($password) ||
            strlen($password) < 12 ||
            strlen($password) > 72
        ) {
            flash('error', 'Enter a valid admin name and a password between 12 and 72 characters.');
            redirect('admin_login');
        }

        try {
            ensure_admin_username_index();
            if (collection('admins')->countDocuments() !== 0) {
                flash('error', 'Admin setup is already complete. Sign in with your admin account.');
                redirect('admin_login');
            }

            $username = strtolower($name);
            $result = collection('admins')->insertOne([
                'name' => $name,
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'public_signup_slot' => 'initial-admin',
                'role' => 'admin',
                'created_at' => new MongoDB\BSON\UTCDateTime(),
            ]);
            sign_in(['_id' => $result->getInsertedId(), 'name' => $name], 'admin');
            flash('success', 'Your admin account has been created.');
            redirect('dashboard');
        } catch (Throwable $e) {
            if ((int)$e->getCode() === 11000) {
                flash('error', 'Initial admin setup was already completed. Sign in with the admin account.');
            } else {
                error_log('Initial admin registration failed: ' . $e->getMessage());
                flash('error', 'The admin account could not be created. Please try again.');
            }
            redirect('admin_login');
        }
    }

    if ($action !== 'admin_login') {
        flash('error', 'Choose a valid admin action.');
        redirect('admin_login');
    }

    $adminName = preg_replace('/\s+/u', ' ', post_string('admin_name'));
    $password = $_POST['password'] ?? '';
    if (!is_string($adminName) || $adminName === '' || strlen($adminName) > 100 || !is_string($password) || $password === '' || strlen($password) > 72) {
        flash('error', 'Enter your admin name and password.');
        redirect('admin_login');
    }

    try {
        ensure_admin_username_index();
        $admin = collection('admins')->findOne(
            ['$or' => [
                ['username' => strtolower($adminName)],
                ['name' => $adminName],
            ]],
            ['collation' => ['locale' => 'en', 'strength' => 2]]
        );
        if (!$admin || !password_verify($password, $admin['password_hash'] ?? '')) {
            flash('error', 'Admin name or password is incorrect.');
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
    <?php if ($canCreateFirstAdmin): ?>
      <div class="panel-heading"><span class="eyebrow">ADMINISTRATION</span><h2>Create the first admin</h2><p>The first admin account is created here. After it exists, this signup form closes.</p></div>
      <form method="post" class="stack-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_first_admin">
        <label>Admin name<input name="admin_name" autocomplete="name" maxlength="100" required></label>
        <label>Password<input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" required><small>Use 12 to 72 characters.</small></label>
        <button class="btn btn-primary" type="submit">Create admin account</button>
      </form>
    <?php elseif ($dbReady && !$adminSetupError): ?>
      <div class="panel-heading"><span class="eyebrow">ADMINISTRATION</span><h2>Admin login</h2><p>Sign in to manage college events and registrations.</p></div>
      <form method="post" class="stack-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="admin_login">
        <label>Admin name<input name="admin_name" autocomplete="username" maxlength="100" required></label>
        <label>Password<input type="password" name="password" autocomplete="current-password" maxlength="72" required></label>
        <button class="btn btn-primary" type="submit">Admin login</button>
      </form>
    <?php elseif ($adminSetupError): ?>
      <div class="panel-heading"><span class="eyebrow">ADMINISTRATION</span><h2>Admin access unavailable</h2><p>The admin account could not be checked. Please try again later.</p></div>
    <?php else: ?>
      <div class="panel-heading"><span class="eyebrow">ADMINISTRATION</span><h2>Admin access</h2><p>Connect MongoDB before creating or signing into an admin account.</p></div>
    <?php endif; ?>
  </div>
  <div class="auth-aside"><span class="big-star">✦</span><h2>Bring your campus<br><em>together.</em></h2><p>Manage events, review registrations, and help students find their next experience.</p></div>
</section>
