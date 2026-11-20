<?php
require_once __DIR__ . '/includes/bootstrap.php';
ob_start();
$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard', 'student_dashboard', 'students', 'events', 'register', 'registrations', 'my_registrations', 'login', 'signup', 'admin_login', 'logout'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';
$pageTitles = [
    'dashboard'=>'Home',
    'student_dashboard'=>'Student dashboard',
    'students'=>'Students',
    'events'=>'Events',
    'register'=>'Register',
    'registrations'=>'Registrations',
    'my_registrations'=>'My registrations',
    'login'=>'Student login',
    'signup'=>'Student sign up',
    'admin_login'=>'Admin login',
];
$user = current_user();
if ($user && $user['role'] === 'admin') $pageTitles['dashboard'] = 'Admin dashboard';

if ($dbReady && $user !== null && in_array($page, ['login', 'signup', 'admin_login'], true)) {
    require_role($user['role']);
    $user = current_user();
}
if ($page === 'dashboard' && $user !== null && $user['role'] === 'student') redirect('student_dashboard');
if (($page === 'login' || $page === 'signup') && $user !== null) {
    redirect($user['role'] === 'admin' ? 'dashboard' : 'student_dashboard');
}
if ($page === 'admin_login' && $user !== null) {
    redirect($user['role'] === 'admin' ? 'dashboard' : 'student_dashboard');
}
if ($dbReady) {
    if ($page === 'dashboard' || $page === 'students' || $page === 'registrations') require_role('admin');
    if ($page === 'student_dashboard' || $page === 'register' || $page === 'my_registrations') require_role('student');
}

if ($page === 'logout') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid()) {
        flash('error', 'Your sign-out request could not be verified. Please try again.');
    } else {
        sign_out();
        flash('success', 'You have been signed out.');
    }
    redirect('dashboard');
}

if ($dbReady && $page === 'registrations' && ($_GET['export'] ?? '') === 'csv') {
    require __DIR__ . '/pages/export_registrations.php';
    exit;
}

require __DIR__ . '/includes/header.php';
if (!$dbReady) {
    if ($page !== 'dashboard' || $user !== null) {
        echo '<section class="notice notice-error"><h2>MongoDB is unavailable</h2><p>Set <code>MONGODB_URI</code> in the same terminal where you start PHP. Check your Atlas credentials, Network Access rules, and the PHP MongoDB extension.</p></section>';
    }
    if (($page === 'dashboard' && $user === null) || in_array($page, ['login', 'signup', 'admin_login'], true)) {
        require __DIR__ . '/pages/' . $page . '.php';
    }
} else {
    require __DIR__ . '/pages/' . $page . '.php';
}
require __DIR__ . '/includes/footer.php';
