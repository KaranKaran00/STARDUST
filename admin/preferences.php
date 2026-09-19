<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
$investor = require_investor();

$INDUSTRIES = ['SaaS', 'Fintech', 'HealthTech', 'EdTech', 'E-commerce', 'AgriTech', 'CleanTech', 'AI/ML', 'Consumer', 'DeepTech'];
$STAGES = ['Idea', 'Pre-seed', 'Seed', 'Series A', 'Series B+'];
$MODELS = ['Subscription', 'Marketplace', 'D2C', 'B2B', 'Freemium', 'Transactional'];

$stmt = db()->prepare('SELECT * FROM investor_preferences WHERE investor_id = ?');
$stmt->execute([$investor['id']]);
$prefs = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $industries = array_to_csv($_POST['industries'] ?? []);
    $stages = array_to_csv($_POST['stages'] ?? []);
    $models = array_to_csv($_POST['models'] ?? []);
    $ticketMin = (int) ($_POST['ticket_min'] ?? 0);
    $ticketMax = (int) ($_POST['ticket_max'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if ($prefs) {
        $stmt = db()->prepare('UPDATE investor_preferences SET industries=?, funding_stages=?, business_models=?, ticket_size_min=?, ticket_size_max=?, notes=? WHERE investor_id=?');
        $stmt->execute([$industries, $stages, $models, $ticketMin, $ticketMax, $notes ?: null, $investor['id']]);
    } else {
        $stmt = db()->prepare('INSERT INTO investor_preferences (investor_id, industries, funding_stages, business_models, ticket_size_min, ticket_size_max, notes) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$investor['id'], $industries, $stages, $models, $ticketMin, $ticketMax, $notes ?: null]);
    }
    flash('success', 'Portfolio preferences saved.');
    redirect(BASE_URL . '/admin/preferences.php');
}

$selIndustries = csv_to_array($prefs['industries'] ?? '');
$selStages = csv_to_array($prefs['funding_stages'] ?? '');
$selModels = csv_to_array($prefs['business_models'] ?? '');

$page_title = 'Portfolio preferences';
require __DIR__ . '/../includes/header.php';
?>
<div class="container container-narrow section-tight">
  <div class="eyebrow" style="color: var(--bloom-deep);">Investor desk</div>
  <h2>Portfolio preferences</h2>
  <p class="lede">This is what "Match my portfolio" filters against on the startups list.</p>

  <form method="post">
    <?= csrf_field() ?>
    <fieldset>
      <legend>Industries</legend>
      <div class="checkbox-grid">
        <?php foreach ($INDUSTRIES as $ind): ?>
          <label class="checkbox-chip"><input type="checkbox" name="industries[]" value="<?= e($ind) ?>" <?= in_array($ind, $selIndustries, true) ? 'checked' : '' ?>><?= e($ind) ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <fieldset>
      <legend>Funding stages</legend>
      <div class="checkbox-grid">
        <?php foreach ($STAGES as $stage): ?>
          <label class="checkbox-chip"><input type="checkbox" name="stages[]" value="<?= e($stage) ?>" <?= in_array($stage, $selStages, true) ? 'checked' : '' ?>><?= e($stage) ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <fieldset>
      <legend>Business models</legend>
      <div class="checkbox-grid">
        <?php foreach ($MODELS as $m): ?>
          <label class="checkbox-chip"><input type="checkbox" name="models[]" value="<?= e($m) ?>" <?= in_array($m, $selModels, true) ? 'checked' : '' ?>><?= e($m) ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <fieldset>
      <legend>Typical ticket size (₹)</legend>
      <div class="field-row">
        <div class="field">
          <label for="ticket_min">Minimum</label>
          <input type="number" id="ticket_min" name="ticket_min" min="0" step="10000" value="<?= e((string) ($prefs['ticket_size_min'] ?? 0)) ?>">
        </div>
        <div class="field">
          <label for="ticket_max">Maximum</label>
          <input type="number" id="ticket_max" name="ticket_max" min="0" step="10000" value="<?= e((string) ($prefs['ticket_size_max'] ?? 0)) ?>">
        </div>
      </div>
    </fieldset>
    <div class="field">
      <label for="notes">Notes to self <span class="muted">(optional)</span></label>
      <textarea id="notes" name="notes" rows="3"><?= e($prefs['notes'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-bloom btn-block">Save preferences</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
