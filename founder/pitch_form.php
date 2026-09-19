<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
$founder = require_founder();

$editing = null;
if (isset($_GET['id'])) {
    $stmt = db()->prepare('SELECT * FROM pitches WHERE id = ? AND founder_id = ?');
    $stmt->execute([(int) $_GET['id'], $founder['id']]);
    $editing = $stmt->fetch();
    if (!$editing) { flash('error', 'Pitch not found.'); redirect(BASE_URL . '/founder/dashboard.php'); }
}

$INDUSTRIES = ['SaaS', 'Fintech', 'HealthTech', 'EdTech', 'E-commerce', 'AgriTech', 'CleanTech', 'AI/ML', 'Consumer', 'DeepTech'];
$STAGES = ['Idea', 'Pre-seed', 'Seed', 'Series A', 'Series B+'];
$MODELS = ['Subscription', 'Marketplace', 'D2C', 'B2B', 'Freemium', 'Transactional'];

$errors = [];
$f = $editing ?: [
    'startup_name' => '', 'tagline' => '', 'industry' => '', 'funding_stage' => '',
    'business_model' => '', 'funding_ask' => '', 'team_size' => 1, 'website' => '',
    'description' => '', 'problem_statement' => '', 'traction' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $f['startup_name'] = trim($_POST['startup_name'] ?? '');
    $f['tagline'] = trim($_POST['tagline'] ?? '');
    $f['industry'] = trim($_POST['industry'] ?? '');
    $f['funding_stage'] = trim($_POST['funding_stage'] ?? '');
    $f['business_model'] = trim($_POST['business_model'] ?? '');
    $f['funding_ask'] = (int) ($_POST['funding_ask'] ?? 0);
    $f['team_size'] = (int) ($_POST['team_size'] ?? 1);
    $f['website'] = trim($_POST['website'] ?? '');
    $f['description'] = trim($_POST['description'] ?? '');
    $f['problem_statement'] = trim($_POST['problem_statement'] ?? '');
    $f['traction'] = trim($_POST['traction'] ?? '');

    if (mb_strlen($f['startup_name']) < 2) $errors[] = 'Startup name is required.';
    if (!in_array($f['industry'], $INDUSTRIES, true)) $errors[] = 'Please choose an industry.';
    if (!in_array($f['funding_stage'], $STAGES, true)) $errors[] = 'Please choose a funding stage.';
    if (!in_array($f['business_model'], $MODELS, true)) $errors[] = 'Please choose a business model.';
    if ($f['funding_ask'] <= 0) $errors[] = 'Please enter how much you are raising.';
    if (mb_strlen($f['description']) < 30) $errors[] = 'Please describe your startup in at least a few sentences.';
    if ($f['website'] && !filter_var($f['website'], FILTER_VALIDATE_URL)) $errors[] = 'Website URL looks invalid.';

    // ---- Deck upload (optional on edit, required on first submission) ----
    $deckFilename = $editing['deck_filename'] ?? null;
    $deckExcerpt = $editing['deck_text_excerpt'] ?? null;
    $deckKeywords = $editing['deck_keywords'] ?? null;

    if (!empty($_FILES['deck']['name'])) {
        $file = $_FILES['deck'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Deck upload failed. Please try again.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $sizeMb = $file['size'] / 1024 / 1024;
            if (!in_array($ext, ALLOWED_DECK_TYPES, true)) {
                $errors[] = 'Deck must be a PDF or PPTX file.';
            } elseif ($sizeMb > MAX_DECK_SIZE_MB) {
                $errors[] = 'Deck must be under ' . MAX_DECK_SIZE_MB . 'MB.';
            } else {
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                $safeName = 'deck_' . $founder['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = UPLOAD_DIR . '/' . $safeName;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $deckFilename = $safeName;
                    $analysis = analyze_deck_file($dest, $ext);
                    $deckExcerpt = $analysis['excerpt'];
                    $deckKeywords = $analysis['keywords'];
                } else {
                    $errors[] = 'Could not save the uploaded deck. Check server upload permissions.';
                }
            }
        }
    } elseif (!$editing) {
        $errors[] = 'Please upload your pitch deck (PDF or PPTX).';
    }

    if (!$errors) {
        if ($editing) {
            $stmt = db()->prepare('UPDATE pitches SET startup_name=?, tagline=?, industry=?, funding_stage=?, business_model=?, funding_ask=?, team_size=?, website=?, description=?, problem_statement=?, traction=?, deck_filename=?, deck_text_excerpt=?, deck_keywords=? WHERE id=? AND founder_id=?');
            $stmt->execute([
                $f['startup_name'], $f['tagline'], $f['industry'], $f['funding_stage'], $f['business_model'],
                $f['funding_ask'], $f['team_size'], $f['website'] ?: null, $f['description'], $f['problem_statement'] ?: null,
                $f['traction'] ?: null, $deckFilename, $deckExcerpt, $deckKeywords, $editing['id'], $founder['id'],
            ]);
            flash('success', 'Your pitch has been updated.');
        } else {
            $stmt = db()->prepare('INSERT INTO pitches (founder_id, startup_name, tagline, industry, funding_stage, business_model, funding_ask, team_size, website, description, problem_statement, traction, deck_filename, deck_text_excerpt, deck_keywords) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $founder['id'], $f['startup_name'], $f['tagline'], $f['industry'], $f['funding_stage'], $f['business_model'],
                $f['funding_ask'], $f['team_size'], $f['website'] ?: null, $f['description'], $f['problem_statement'] ?: null,
                $f['traction'] ?: null, $deckFilename, $deckExcerpt, $deckKeywords,
            ]);
            flash('success', 'Your pitch is live. Investors matching your industry and stage can now find it.');
        }
        redirect(BASE_URL . '/founder/dashboard.php');
    }
}

$page_title = $editing ? 'Edit your pitch' : 'Pitch your startup';
require __DIR__ . '/../includes/header.php';
?>
<div class="container container-narrow section-tight">
  <div class="eyebrow">Founder</div>
  <h2><?= $editing ? 'Edit your pitch' : 'Tell us about your startup' ?></h2>
  <p class="lede">This is what investors see first. Be specific — numbers and a clear ask travel further than adjectives.</p>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <fieldset>
      <legend>Basics</legend>
      <div class="field">
        <label for="startup_name">Startup name</label>
        <input type="text" id="startup_name" name="startup_name" value="<?= e($f['startup_name']) ?>" required>
      </div>
      <div class="field">
        <label for="tagline">One-line tagline</label>
        <input type="text" id="tagline" name="tagline" value="<?= e($f['tagline']) ?>" placeholder="What you do, in one sentence">
      </div>
      <div class="field-row">
        <div class="field">
          <label for="website">Website <span class="muted">(optional)</span></label>
          <input type="url" id="website" name="website" value="<?= e($f['website']) ?>" placeholder="https://">
        </div>
        <div class="field">
          <label for="team_size">Team size</label>
          <input type="number" id="team_size" name="team_size" min="1" value="<?= e((string) $f['team_size']) ?>">
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>Classification</legend>
      <div class="field-row">
        <div class="field">
          <label for="industry">Industry</label>
          <select id="industry" name="industry" required>
            <option value="">Select…</option>
            <?php foreach ($INDUSTRIES as $ind): ?>
              <option value="<?= e($ind) ?>" <?= $f['industry'] === $ind ? 'selected' : '' ?>><?= e($ind) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="funding_stage">Funding stage</label>
          <select id="funding_stage" name="funding_stage" required>
            <option value="">Select…</option>
            <?php foreach ($STAGES as $stage): ?>
              <option value="<?= e($stage) ?>" <?= $f['funding_stage'] === $stage ? 'selected' : '' ?>><?= e($stage) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="business_model">Business model</label>
          <select id="business_model" name="business_model" required>
            <option value="">Select…</option>
            <?php foreach ($MODELS as $m): ?>
              <option value="<?= e($m) ?>" <?= $f['business_model'] === $m ? 'selected' : '' ?>><?= e($m) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="funding_ask">Funding ask (₹)</label>
          <input type="number" id="funding_ask" name="funding_ask" min="1" step="10000" value="<?= e((string) $f['funding_ask']) ?>" required>
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>The pitch</legend>
      <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="5" required><?= e($f['description']) ?></textarea>
        <div class="field-hint">What you're building, for whom, and why now.</div>
      </div>
      <div class="field">
        <label for="problem_statement">Problem you're solving <span class="muted">(optional)</span></label>
        <textarea id="problem_statement" name="problem_statement" rows="3"><?= e($f['problem_statement']) ?></textarea>
      </div>
      <div class="field">
        <label for="traction">Traction so far <span class="muted">(optional)</span></label>
        <textarea id="traction" name="traction" rows="3" placeholder="Users, revenue, pilots, waitlist…"><?= e($f['traction']) ?></textarea>
      </div>
    </fieldset>

    <fieldset>
      <legend>Pitch deck</legend>
      <div class="field">
        <label for="deck">Upload PDF or PPTX <?= $editing ? '<span class="muted">(leave empty to keep current file)</span>' : '' ?></label>
        <input type="file" id="deck" name="deck" accept=".pdf,.ppt,.pptx" <?= $editing ? '' : 'required' ?>>
        <div class="field-hint file-name-hint"><?= $editing && $editing['deck_filename'] ? 'Current file: ' . e($editing['deck_filename']) : 'PDF or PPTX, up to ' . MAX_DECK_SIZE_MB . 'MB.' ?></div>
      </div>
    </fieldset>

    <button type="submit" class="btn btn-primary btn-block"><?= $editing ? 'Save changes' : 'Submit pitch' ?></button>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
