<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
$investor = require_investor();

$INDUSTRIES = ['SaaS', 'Fintech', 'HealthTech', 'EdTech', 'E-commerce', 'AgriTech', 'CleanTech', 'AI/ML', 'Consumer', 'DeepTech'];
$STAGES = ['Idea', 'Pre-seed', 'Seed', 'Series A', 'Series B+'];
$MODELS = ['Subscription', 'Marketplace', 'D2C', 'B2B', 'Freemium', 'Transactional'];
$STATUSES = ['pending' => 'Pending review', 'in_review' => 'In review', 'shortlisted' => 'Shortlisted', 'rejected' => 'Not moving forward'];

$stmt = db()->prepare('SELECT * FROM investor_preferences WHERE investor_id = ?');
$stmt->execute([$investor['id']]);
$prefs = $stmt->fetch() ?: [];

$matchOnly = isset($_GET['match']) && $_GET['match'] === '1';
$industry = $_GET['industry'] ?? '';
$stage = $_GET['stage'] ?? '';
$model = $_GET['model'] ?? '';
$status = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if ($matchOnly) {
    $prefIndustries = csv_to_array($prefs['industries'] ?? '');
    $prefStages = csv_to_array($prefs['funding_stages'] ?? '');
    $prefModels = csv_to_array($prefs['business_models'] ?? '');
    if ($prefIndustries) { $where[] = 'industry IN (' . implode(',', array_fill(0, count($prefIndustries), '?')) . ')'; array_push($params, ...$prefIndustries); }
    if ($prefStages) { $where[] = 'funding_stage IN (' . implode(',', array_fill(0, count($prefStages), '?')) . ')'; array_push($params, ...$prefStages); }
    if ($prefModels) { $where[] = 'business_model IN (' . implode(',', array_fill(0, count($prefModels), '?')) . ')'; array_push($params, ...$prefModels); }
    if (!empty($prefs['ticket_size_min'])) { $where[] = 'funding_ask >= ?'; $params[] = (int) $prefs['ticket_size_min']; }
    if (!empty($prefs['ticket_size_max'])) { $where[] = 'funding_ask <= ?'; $params[] = (int) $prefs['ticket_size_max']; }
}

if ($industry) { $where[] = 'industry = ?'; $params[] = $industry; }
if ($stage) { $where[] = 'funding_stage = ?'; $params[] = $stage; }
if ($model) { $where[] = 'business_model = ?'; $params[] = $model; }
if ($status) { $where[] = 'status = ?'; $params[] = $status; }
if ($q !== '') { $where[] = '(startup_name LIKE ? OR tagline LIKE ? OR description LIKE ? OR deck_keywords LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%", "%$q%"); }

$sql = 'SELECT p.*, f.full_name AS founder_name, f.email AS founder_email FROM pitches p JOIN founders f ON f.id = p.founder_id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY p.submitted_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$pitches = $stmt->fetchAll();

$totalCount = (int) db()->query('SELECT COUNT(*) FROM pitches')->fetchColumn();

function ring_stage_admin(string $status): int
{
    return match ($status) {
        'pending' => 1, 'in_review' => 2, 'shortlisted', 'rejected' => 3, default => 1,
    };
}

$page_title = 'Startups';
require __DIR__ . '/../includes/header.php';
?>
<div class="container section-tight">
  <div class="dash-header">
    <div>
      <div class="eyebrow" style="color: var(--bloom-deep);">Investor desk</div>
      <h2 class="mb-0">Startups</h2>
    </div>
    <div class="small muted"><?= count($pitches) ?> of <?= $totalCount ?> shown</div>
  </div>

  <form method="get" class="filter-bar" data-autosubmit>
    <div class="field">
      <label for="q">Search</label>
      <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Name, keyword, deck content…" style="min-width:220px;">
    </div>
    <div class="field">
      <label for="industry">Industry</label>
      <select id="industry" name="industry">
        <option value="">Any</option>
        <?php foreach ($INDUSTRIES as $i): ?><option value="<?= e($i) ?>" <?= $industry === $i ? 'selected' : '' ?>><?= e($i) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="stage">Stage</label>
      <select id="stage" name="stage">
        <option value="">Any</option>
        <?php foreach ($STAGES as $s): ?><option value="<?= e($s) ?>" <?= $stage === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="model">Business model</label>
      <select id="model" name="model">
        <option value="">Any</option>
        <?php foreach ($MODELS as $m): ?><option value="<?= e($m) ?>" <?= $model === $m ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="">Any</option>
        <?php foreach ($STATUSES as $val => $lbl): ?><option value="<?= e($val) ?>" <?= $status === $val ? 'selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?>
      </select>
    </div>
    <label class="checkbox-chip" style="margin-bottom:0;">
      <input type="checkbox" name="match" value="1" onchange="this.form.submit()" <?= $matchOnly ? 'checked' : '' ?>>
      Match my portfolio
    </label>
    <button type="submit" class="btn btn-ghost btn-sm">Apply</button>
    <?php if ($industry || $stage || $model || $status || $q || $matchOnly): ?>
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (!$pitches): ?>
    <div class="empty-state">
      <h3>No startups match these filters</h3>
      <p>Try widening the filter, or clear it to see everyone who's pitched.</p>
    </div>
  <?php else: ?>
    <div class="pitch-list">
      <?php foreach ($pitches as $p): $stage_n = ring_stage_admin($p['status']); ?>
        <a href="<?= BASE_URL ?>/admin/pitch_detail.php?id=<?= $p['id'] ?>" class="pitch-row">
          <div class="pitch-row-main">
            <h3><?= e($p['startup_name']) ?> <span class="small muted" style="font-weight:400;">— <?= e($p['founder_name']) ?></span></h3>
            <div class="meta">
              <span><?= e($p['industry']) ?></span>
              <span><?= e($p['funding_stage']) ?></span>
              <span><?= e($p['business_model']) ?></span>
              <span><?= money((int) $p['funding_ask']) ?> ask</span>
              <span>Submitted <?= time_ago($p['submitted_at']) ?></span>
            </div>
          </div>
          <div class="pitch-row-side">
            <span class="rings <?= $p['status'] === 'rejected' ? 'rejected' : '' ?>">
              <?php for ($i = 1; $i <= 3; $i++): ?><span class="<?= $i <= $stage_n ? 'filled' : '' ?>"></span><?php endfor; ?>
            </span>
            <span class="badge badge-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
