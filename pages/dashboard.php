<section class="hero">
  <div><span class="pill">✦ YOUR CAMPUS, IN ONE PLACE</span><h2>Make every campus<br><em>moment count.</em></h2><p>Plan events, bring students together, and keep every registration organised.</p><a class="btn btn-light" href="index.php?page=events">Explore events <span>→</span></a></div>
  <div class="hero-art"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="hero-card"><span class="hero-icon">✦</span><strong>Ideas become<br>experiences.</strong><small>CREATE · CONNECT · CELEBRATE</small></div><span class="spark spark-a">✳</span><span class="spark spark-b">✦</span></div>
</section>
<section class="stats-grid">
  <article class="stat-card"><span class="stat-icon purple">♙</span><small>Total students</small><strong><?= $dbReady ? count_docs('students') : '—' ?></strong><span class="stat-note">Registered in the system</span></article>
  <article class="stat-card"><span class="stat-icon orange">▦</span><small>Campus events</small><strong><?= $dbReady ? count_docs('events') : '—' ?></strong><span class="stat-note">Events listed</span></article>
  <article class="stat-card"><span class="stat-icon green">✓</span><small>Registrations</small><strong><?= $dbReady ? count_docs('registrations') : '—' ?></strong><span class="stat-note">Student sign-ups</span></article>
</section>
<section class="section-heading"><div><span class="eyebrow">GET STARTED</span><h2>Everything you need</h2></div></section>
<section class="quick-grid">
  <a class="quick-card" href="index.php?page=students"><span class="quick-icon purple">♙</span><h3>Manage students</h3><p>Add student details and keep your campus directory up to date.</p><b>Open students →</b></a>
  <a class="quick-card" href="index.php?page=events"><span class="quick-icon orange">▦</span><h3>Organise events</h3><p>Create workshops, fests, competitions and campus activities.</p><b>Manage events →</b></a>
  <a class="quick-card" href="index.php?page=register"><span class="quick-icon green">＋</span><h3>Register students</h3><p>Connect a student to an event with a simple registration.</p><b>New registration →</b></a>
</section>
