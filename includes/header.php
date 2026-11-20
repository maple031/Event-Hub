<?php $flashMessage = get_flash(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitles[$page] ?? 'EventHub') ?> · Campus EventHub</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <a class="brand" href="index.php"><span class="brand-mark">✦</span><span>Campus<span class="brand-light">EventHub</span></span></a>
    <div class="side-label">WORKSPACE</div>
    <nav>
      <a class="<?= $page==='dashboard'?'active':'' ?>" href="index.php?page=dashboard"><span>⌂</span> Overview</a>
      <a class="<?= $page==='students'?'active':'' ?>" href="index.php?page=students"><span>♙</span> Students</a>
      <a class="<?= $page==='events'?'active':'' ?>" href="index.php?page=events"><span>▦</span> Events</a>
      <a class="<?= $page==='register'?'active':'' ?>" href="index.php?page=register"><span>＋</span> Register</a>
      <a class="<?= $page==='registrations'?'active':'' ?>" href="index.php?page=registrations"><span>☷</span> Registrations</a>
    </nav>
    <div class="sidebar-bottom"><div class="avatar">CH</div><div><strong>Campus Admin</strong><small>Event coordinator</small></div></div>
  </aside>
  <main class="main">
    <header class="topbar"><div><span class="eyebrow">CAMPUS MANAGEMENT</span><h1><?= h($pageTitles[$page] ?? 'Dashboard') ?></h1></div><div class="top-actions"><span class="status-dot"></span><span>MongoDB <?= $dbReady ? 'connected' : 'not configured' ?></span></div></header>
    <?php if ($flashMessage): ?><div class="alert <?= h($flashMessage['type']) ?>"><?= h($flashMessage['message']) ?></div><?php endif; ?>
