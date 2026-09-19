<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (current_investor()) redirect(BASE_URL . '/admin/dashboard.php');

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM investors WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
        login_investor($row);
        redirect(BASE_URL . '/admin/dashboard.php');
    } else {
        $error = 'Incorrect email or password.';
    }
}

$page_title = 'Investor login';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-shell">
  <div class="auth-card">
    <div class="eyebrow" style="color: var(--bloom-deep);">Investor</div>
    <h2>Log in to your desk</h2>
    <p class="small muted">Demo account — <strong>investor@stardust.demo</strong> / <strong>investor123</strong></p>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-bloom btn-block">Log in</button>
    </form>
    <p class="auth-foot">New investor? <a href="<?= BASE_URL ?>/register_investor.php">Create an investor account</a></p>
    <p class="auth-foot">Founder? <a href="<?= BASE_URL ?>/login_founder.php">Log in here</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
