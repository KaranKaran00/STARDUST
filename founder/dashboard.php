<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
$founder = require_founder();

$stmt = db()->prepare('SELECT * FROM pitches WHERE founder_id = ? ORDER BY submitted_at DESC');
$stmt->execute([$founder['id']]);
$pitches = $stmt->fetchAll();

$pendingMeetings = 0;
if ($pitches) {
    $ids = array_column($pitches, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT COUNT(*) FROM meetings WHERE pitch_id IN ($in) AND status = 'pending'");
    $stmt->execute($ids);
    $pendingMeetings = (int) $stmt->fetchColumn();
}

function ring_stage(string $status): int
{
    return match ($status) {
        'pending' => 1,
        'in_review' => 2,
        'shortlisted', 'rejected' => 3,
        default => 1,
    };
}

$page_title = 'Your dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="container section-tight">
  <div class="dash-header">
    <div>
      <div class="eyebrow">Founder dashboard</div>
      <h2 class="mb-0">Hi <?= e($founder['full_name']) ?></h2>
    </div>
    <a href="<?= BASE_URL ?>/founder/pitch_form.php" class="btn btn-primary">+ New pitch</a>
  </div>

  <div class="stat-row">
    <div class="stat-tile"><span class="num"><?= count($pitches) ?></span><span class="lbl">Pitches submitted</span></div>
    <div class="stat-tile"><span class="num"><?= count(array_filter($pitches, fn($p) => $p['status'] === 'shortlisted')) ?></span><span class="lbl">Shortlisted</span></div>
    <div class="stat-tile"><span class="num"><?= $pendingMeetings ?></span><span class="lbl">Pending meeting requests</span></div>
  </div>

  <?php if (!$pitches): ?>
    <div class="empty-state">
      <h3>You haven't submitted a pitch yet</h3>
      <p>Investors can only find startups that have a pitch on file.</p>
      <a href="<?= BASE_URL ?>/founder/pitch_form.php" class="btn btn-primary">Submit your first pitch</a>
    </div>
  <?php else: ?>
    <div class="pitch-list">
      <?php foreach ($pitches as $p): $stage = ring_stage($p['status']); ?>
        <a href="<?= BASE_URL ?>/founder/pitch_detail.php?id=<?= $p['id'] ?>" class="pitch-row">
          <div class="pitch-row-main">
            <h3><?= e($p['startup_name']) ?></h3>
            <div class="meta">
              <span><?= e($p['industry']) ?></span>
              <span><?= e($p['funding_stage']) ?></span>
              <span><?= money((int) $p['funding_ask']) ?> ask</span>
              <span>Submitted <?= time_ago($p['submitted_at']) ?></span>
            </div>
          </div>
          <div class="pitch-row-side">
            <span class="rings <?= $p['status'] === 'rejected' ? 'rejected' : '' ?>" title="<?= e(status_label($p['status'])) ?>">
              <?php for ($i = 1; $i <= 3; $i++): ?><span class="<?= $i <= $stage ? 'filled' : '' ?>"></span><?php endfor; ?>
            </span>
            <span class="badge badge-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
