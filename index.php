<?php
require_once __DIR__ . '/includes/bootstrap.php';
$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard', 'students', 'events', 'register', 'registrations'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';
$pageTitles = ['dashboard'=>'Dashboard', 'students'=>'Students', 'events'=>'Events', 'register'=>'Register', 'registrations'=>'Registrations'];
require __DIR__ . '/includes/header.php';
if (!$dbReady) {
    echo '<main class="container"><section class="notice notice-error"><h2>MongoDB is unavailable</h2><p>Set <code>MONGODB_URI</code> in the same terminal where you start PHP. Check that the URI contains your database user credentials, your IP is allowed in Atlas Network Access, and the PHP MongoDB extension is enabled.</p></section></main>';
} else {
    require __DIR__ . '/pages/' . $page . '.php';
}
require __DIR__ . '/includes/footer.php';
