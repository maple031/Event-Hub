<?php $flashMessage = get_flash(); $user = current_user(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitles[$page] ?? 'EventHub') ?> · College Event Hub</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/portal.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <a class="brand" href="index.php"><span class="brand-mark">✦</span><span>College<span class="brand-light">Event Hub</span></span></a>
    <div class="side-label">WORKSPACE</div>
    <nav>
      <?php if ($user && $user['role'] === 'admin'): ?>
        <a class="<?= $page==='dashboard'?'active':'' ?>" href="index.php?page=dashboard"><span>⌂</span> Overview</a>
        <a class="<?= $page==='students'?'active':'' ?>" href="index.php?page=students"><span>♙</span> Students</a>
        <a class="<?= $page==='events'?'active':'' ?>" href="index.php?page=events"><span>▦</span> Manage events</a>
        <a class="<?= $page==='registrations'?'active':'' ?>" href="index.php?page=registrations"><span>☷</span> Registrations</a>
      <?php elseif ($user && $user['role'] === 'student'): ?>
        <a class="<?= $page==='student_dashboard'?'active':'' ?>" href="index.php?page=student_dashboard"><span>⌂</span> My dashboard</a>
        <a class="<?= $page==='events'?'active':'' ?>" href="index.php?page=events"><span>▦</span> Events</a>
        <a class="<?= $page==='register'?'active':'' ?>" href="index.php?page=register"><span>＋</span> Register</a>
        <a class="<?= $page==='my_registrations'?'active':'' ?>" href="index.php?page=my_registrations"><span>☷</span> My registrations</a>
      <?php else: ?>
        <a class="<?= $page==='dashboard'?'active':'' ?>" href="index.php"><span>⌂</span> Home</a>
        <a class="<?= $page==='events'?'active':'' ?>" href="index.php?page=events"><span>▦</span> Events</a>
        <a href="index.php?page=login"><span>♙</span> Student login</a>
        <a href="index.php?page=admin_login"><span>⚙</span> Admin login</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-bottom">
      <?php if ($user): ?>
        <div class="avatar"><?= h(strtoupper(substr($user['name'], 0, 1))) ?></div>
        <div class="account-details"><strong><?= h($user['name']) ?></strong><small><?= $user['role'] === 'student' ? 'Roll ' . h($user['roll_number']) : 'Administrator' ?></small></div>
        <form method="post" action="index.php?page=logout" class="logout-form"><?= csrf_field() ?><button type="submit" class="logout-button">Sign out</button></form>
      <?php else: ?>
        <div class="avatar">CE</div><div class="account-details"><strong>College Event Hub</strong><small>Choose a portal above</small></div>
      <?php endif; ?>
    </div>
  </aside>
  <main class="main">
    <header class="topbar"><div><span class="eyebrow">CAMPUS MANAGEMENT</span><h1><?= h($pageTitles[$page] ?? 'Dashboard') ?></h1></div><div class="top-actions"><span class="status-dot"></span><span>MongoDB <?= $dbReady ? 'connected' : 'not configured' ?></span></div></header>
    <?php if ($flashMessage): ?><div class="alert <?= h($flashMessage['type']) ?>"><?= h($flashMessage['message']) ?></div><?php endif; ?>
