<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
$founder = require_founder();

$stmt = db()->prepare('SELECT * FROM pitches WHERE id = ? AND founder_id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0), $founder['id']]);
$pitch = $stmt->fetch();
if (!$pitch) { flash('error', 'Pitch not found.'); redirect(BASE_URL . '/founder/dashboard.php'); }

// Respond to a meeting request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meeting_id'])) {
    verify_csrf();
    $action = $_POST['action'] === 'confirm' ? 'confirmed' : 'declined';
    $stmt = db()->prepare('UPDATE meetings SET status = ? WHERE id = ? AND pitch_id = ?');
    $stmt->execute([$action, (int) $_POST['meeting_id'], $pitch['id']]);
    flash('success', 'Meeting ' . $action . '.');
    redirect(BASE_URL . '/founder/pitch_detail.php?id=' . $pitch['id']);
}

$stmt = db()->prepare('
  SELECT m.*, i.full_name AS investor_name, i.firm_name FROM meetings m
  JOIN investors i ON i.id = m.investor_id
  WHERE m.pitch_id = ? ORDER BY m.proposed_datetime DESC');
$stmt->execute([$pitch['id']]);
$meetings = $stmt->fetchAll();

$page_title = $pitch['startup_name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container section-tight">
  <div class="eyebrow">Founder · your pitch</div>
  <div class="dash-header">
    <div>
      <h2 class="mb-0"><?= e($pitch['startup_name']) ?></h2>
      <p class="small muted mt-0"><?= e($pitch['tagline']) ?></p>
    </div>
    <div class="flex gap-8">
      <span class="badge badge-<?= e($pitch['status']) ?>"><?= e(status_label($pitch['status'])) ?></span>
      <a href="<?= BASE_URL ?>/founder/pitch_form.php?id=<?= $pitch['id'] ?>" class="btn btn-ghost btn-sm">Edit pitch</a>
    </div>
  </div>

  <div class="two-col">
    <div>
      <div class="card" style="background:#fff;">
        <div class="tag-list" style="margin-bottom:18px;">
          <span class="tag"><?= e($pitch['industry']) ?></span>
          <span class="tag"><?= e($pitch['funding_stage']) ?></span>
          <span class="tag"><?= e($pitch['business_model']) ?></span>
          <span class="tag tag-bloom"><?= money((int) $pitch['funding_ask']) ?> ask</span>
        </div>
        <h3>Description</h3>
        <p><?= nl2br(e($pitch['description'])) ?></p>
        <?php if ($pitch['problem_statement']): ?>
          <h3>Problem</h3><p><?= nl2br(e($pitch['problem_statement'])) ?></p>
        <?php endif; ?>
        <?php if ($pitch['traction']): ?>
          <h3>Traction</h3><p><?= nl2br(e($pitch['traction'])) ?></p>
        <?php endif; ?>
        <?php if ($pitch['deck_filename']): ?>
          <h3>Deck</h3>
          <a href="<?= BASE_URL . UPLOAD_URL . '/' . e($pitch['deck_filename']) ?>" class="btn btn-ghost btn-sm" target="_blank" rel="noopener">Download deck</a>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <h3>Meeting requests</h3>
      <?php if (!$meetings): ?>
        <p class="small muted">No investor has requested a meeting yet.</p>
      <?php else: ?>
        <?php foreach ($meetings as $m): ?>
          <div class="note">
            <strong><?= e($m['firm_name'] ?: $m['investor_name']) ?></strong>
            <span class="badge badge-<?= $m['status'] === 'pending' ? 'pending-mt' : e($m['status']) ?>"><?= e(ucfirst($m['status'])) ?></span>
            <div class="meta"><?= date('d M Y, g:i A', strtotime($m['proposed_datetime'])) ?></div>
            <?php if ($m['message']): ?><p class="small" style="margin:8px 0 0;"><?= nl2br(e($m['message'])) ?></p><?php endif; ?>
            <?php if ($m['status'] === 'pending'): ?>
              <form method="post" class="flex gap-8" style="margin-top:10px;">
                <?= csrf_field() ?>
                <input type="hidden" name="meeting_id" value="<?= $m['id'] ?>">
                <button type="submit" name="action" value="confirm" class="btn btn-primary btn-sm">Confirm</button>
                <button type="submit" name="action" value="decline" class="btn btn-danger btn-sm">Decline</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
