<?php
/**
 * Shared <head> + nav. Include after config/auth are loaded.
 * Optional $page_title variable may be set before including.
 */
$founder = current_founder();
$investor = current_investor();
$title = isset($page_title) ? $page_title . ' · ' . APP_NAME : APP_NAME . ' — Where startups meet the right investors';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 32 32%22><text y=%2224%22 font-size=%2226%22>%E2%9C%A6</text></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,440..620&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?= BASE_URL ?>/index.php" class="brand">
      STARDUST
    </a>

    <?php if ($investor): ?>
      <div class="nav-links">
        <a href="<?= BASE_URL ?>/admin/dashboard.php">Startups</a>
        <a href="<?= BASE_URL ?>/admin/meetings.php">Meetings</a>
        <a href="<?= BASE_URL ?>/admin/preferences.php">Portfolio preferences</a>
      </div>
      <div class="nav-actions">
        <span class="small muted"><?= e($investor['firm_name'] ?: $investor['full_name']) ?></span>
        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-ghost btn-sm">Log out</a>
      </div>
    <?php elseif ($founder): ?>
      <div class="nav-links">
        <a href="<?= BASE_URL ?>/founder/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/founder/meetings.php">Meetings</a>
      </div>
      <div class="nav-actions">
        <span class="small muted"><?= e($founder['full_name']) ?></span>
        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-ghost btn-sm">Log out</a>
      </div>
    <?php else: ?>
      <div class="nav-links">
        <a href="<?= BASE_URL ?>/index.php#how-it-works">How it works</a>
        <a href="<?= BASE_URL ?>/login_investor.php">For investors</a>
      </div>
      <div class="nav-actions">
        <a href="<?= BASE_URL ?>/login_founder.php" class="btn btn-ghost btn-sm">Log in</a>
        <a href="<?= BASE_URL ?>/register_founder.php" class="btn btn-primary btn-sm">Pitch your startup</a>
      </div>
    <?php endif; ?>
    <button type="button" class="nav-toggle btn btn-ghost btn-sm" id="navToggle" aria-label="Toggle menu" aria-expanded="false">☰</button>
  </div>
  <div class="nav-links-mobile" id="navLinksMobile" hidden></div>
</nav>
<?php
$err = flash('error');
$ok = flash('success');
?>
<?php if ($err || $ok): ?>
<div class="container section-tight" style="padding-bottom:0;">
  <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
</div>
<?php endif; ?>
