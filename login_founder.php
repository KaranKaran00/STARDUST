<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (current_founder()) redirect(BASE_URL . '/founder/dashboard.php');

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM founders WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
        login_founder($row);
        redirect(BASE_URL . '/founder/dashboard.php');
    } else {
        $error = 'Incorrect email or password.';
    }
}

$page_title = 'Founder login';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-shell">
  <div class="auth-card">
    <div class="eyebrow">Founder</div>
    <h2>Welcome back</h2>

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
      <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>
    <p class="auth-foot">New to Stardust? <a href="<?= BASE_URL ?>/register_founder.php">Create a founder account</a></p>
    <p class="auth-foot">Investor? <a href="<?= BASE_URL ?>/login_investor.php">Log in here</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
