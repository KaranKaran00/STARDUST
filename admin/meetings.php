<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
$investor = require_investor();

$stmt = db()->prepare('
  SELECT m.*, p.startup_name, f.full_name AS founder_name, f.email AS founder_email FROM meetings m
  JOIN pitches p ON p.id = m.pitch_id
  JOIN founders f ON f.id = m.founder_id
  WHERE m.investor_id = ? ORDER BY m.proposed_datetime DESC');
$stmt->execute([$investor['id']]);
$meetings = $stmt->fetchAll();

$page_title = 'Meetings';
require __DIR__ . '/../includes/header.php';
?>
<div class="container section-tight">
  <div class="eyebrow" style="color: var(--bloom-deep);">Investor desk</div>
  <h2>Your meetings</h2>

  <?php if (!$meetings): ?>
    <div class="empty-state"><h3>No meetings scheduled yet</h3><p>Open a startup's pitch to send a meeting request.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Startup</th><th>Founder</th><th>Proposed time</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($meetings as $m): ?>
          <tr>
            <td><?= e($m['startup_name']) ?></td>
            <td><?= e($m['founder_name']) ?> <span class="muted small">(<?= e($m['founder_email']) ?>)</span></td>
            <td><?= date('d M Y, g:i A', strtotime($m['proposed_datetime'])) ?></td>
            <td><span class="badge badge-<?= $m['status'] === 'pending' ? 'pending-mt' : e($m['status']) ?>"><?= e(ucfirst($m['status'])) ?></span></td>
            <td><a href="<?= BASE_URL ?>/admin/pitch_detail.php?id=<?= $m['pitch_id'] ?>" class="btn btn-ghost btn-sm">View pitch</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
