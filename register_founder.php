<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (current_founder()) redirect(BASE_URL . '/founder/dashboard.php');

$errors = [];
$full_name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (mb_strlen($full_name) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (mb_strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM founders WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'An account with that email already exists.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('INSERT INTO founders (full_name, email, password_hash, phone) VALUES (?, ?, ?, ?)');
        $stmt->execute([$full_name, $email, $hash, $phone ?: null]);
        $row = ['id' => db()->lastInsertId(), 'full_name' => $full_name, 'email' => $email];
        login_founder($row);
        flash('success', 'Welcome to Stardust — let\'s get your pitch in front of investors.');
        redirect(BASE_URL . '/founder/pitch_form.php');
    }
}

$page_title = 'Create a founder account';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-shell">
  <div class="auth-card">
    <div class="eyebrow">Founder</div>
    <h2>Create your account</h2>
    <p class="small muted">Takes two minutes. You'll fill in your pitch details right after.</p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" value="<?= e($full_name) ?>" required>
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
      </div>
      <div class="field">
        <label for="phone">Phone <span class="muted">(optional)</span></label>
        <input type="tel" id="phone" name="phone" value="<?= e($phone) ?>">
      </div>
      <div class="field-row">
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" minlength="8" required>
        </div>
        <div class="field">
          <label for="confirm_password">Confirm password</label>
          <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create account</button>
    </form>
    <p class="auth-foot">Already have an account? <a href="<?= BASE_URL ?>/login_founder.php">Log in</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
