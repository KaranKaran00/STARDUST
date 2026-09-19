<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (current_investor()) redirect(BASE_URL . '/admin/dashboard.php');

$errors = [];
$full_name = $email = $firm_name = '';

$INDUSTRIES = ['SaaS', 'Fintech', 'HealthTech', 'EdTech', 'E-commerce', 'AgriTech', 'CleanTech', 'AI/ML', 'Consumer', 'DeepTech'];
$STAGES = ['Idea', 'Pre-seed', 'Seed', 'Series A', 'Series B+'];
$MODELS = ['Subscription', 'Marketplace', 'D2C', 'B2B', 'Freemium', 'Transactional'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $firm_name = trim($_POST['firm_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $industries = $_POST['industries'] ?? [];
    $stages = $_POST['stages'] ?? [];
    $models = $_POST['models'] ?? [];
    $ticket_min = (int) ($_POST['ticket_min'] ?? 0);
    $ticket_max = (int) ($_POST['ticket_max'] ?? 0);

    if (mb_strlen($full_name) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (mb_strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM investors WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'An account with that email already exists.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO investors (full_name, email, password_hash, firm_name) VALUES (?, ?, ?, ?)');
        $stmt->execute([$full_name, $email, $hash, $firm_name ?: null]);
        $investorId = $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO investor_preferences (investor_id, industries, funding_stages, business_models, ticket_size_min, ticket_size_max) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$investorId, array_to_csv($industries), array_to_csv($stages), array_to_csv($models), $ticket_min, $ticket_max]);
        $pdo->commit();

        $row = ['id' => $investorId, 'full_name' => $full_name, 'email' => $email, 'firm_name' => $firm_name];
        login_investor($row);
        flash('success', 'Your investor desk is ready.');
        redirect(BASE_URL . '/admin/dashboard.php');
    }
}

$page_title = 'Create an investor account';
require __DIR__ . '/includes/header.php';
?>
<div class="container container-narrow section-tight">
  <div class="eyebrow" style="color: var(--bloom-deep);">Investor</div>
  <h2>Set up your desk</h2>
  <p class="lede">Tell us what you invest in — this becomes your default filter on the startups list, and you can change it any time from Portfolio preferences.</p>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="post" novalidate>
    <?= csrf_field() ?>
    <fieldset>
      <legend>Account</legend>
      <div class="field-row">
        <div class="field">
          <label for="full_name">Full name</label>
          <input type="text" id="full_name" name="full_name" value="<?= e($full_name) ?>" required>
        </div>
        <div class="field">
          <label for="firm_name">Firm <span class="muted">(optional)</span></label>
          <input type="text" id="firm_name" name="firm_name" value="<?= e($firm_name) ?>">
        </div>
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
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
    </fieldset>

    <fieldset>
      <legend>Industries you back</legend>
      <div class="checkbox-grid">
        <?php foreach ($INDUSTRIES as $ind): ?>
          <label class="checkbox-chip"><input type="checkbox" name="industries[]" value="<?= e($ind) ?>"><?= e($ind) ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>Funding stages</legend>
      <div class="checkbox-grid">
        <?php foreach ($STAGES as $stage): ?>
          <label class="checkbox-chip"><input type="checkbox" name="stages[]" value="<?= e($stage) ?>"><?= e($stage) ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>Business models</legend>
      <div class="checkbox-grid">
        <?php foreach ($MODELS as $m): ?>
          <label class="checkbox-chip"><input type="checkbox" name="models[]" value="<?= e($m) ?>"><?= e($m) ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>Typical ticket size (₹)</legend>
      <div class="field-row">
        <div class="field">
          <label for="ticket_min">Minimum</label>
          <input type="number" id="ticket_min" name="ticket_min" min="0" step="10000" placeholder="500000">
        </div>
        <div class="field">
          <label for="ticket_max">Maximum</label>
          <input type="number" id="ticket_max" name="ticket_max" min="0" step="10000" placeholder="5000000">
        </div>
      </div>
    </fieldset>

    <button type="submit" class="btn btn-bloom btn-block">Create investor account</button>
  </form>
  <p class="auth-foot">Already have a desk? <a href="<?= BASE_URL ?>/login_investor.php">Log in</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
