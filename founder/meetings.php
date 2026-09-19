<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
$founder = require_founder();

$stmt = db()->prepare('
  SELECT m.*, p.startup_name, i.full_name AS investor_name, i.firm_name FROM meetings m
  JOIN pitches p ON p.id = m.pitch_id
  JOIN investors i ON i.id = m.investor_id
  WHERE p.founder_id = ? ORDER BY m.proposed_datetime DESC');
$stmt->execute([$founder['id']]);
$meetings = $stmt->fetchAll();

$page_title = 'Meetings';
require __DIR__ . '/../includes/header.php';
?>
<div class="container section-tight">
  <div class="eyebrow">Founder</div>
  <h2>Meeting requests</h2>

  <?php if (!$meetings): ?>
    <div class="empty-state"><h3>No meeting requests yet</h3><p>When an investor wants to talk, it'll show up here.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Startup</th><th>Investor</th><th>Proposed time</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($meetings as $m): ?>
          <tr>
            <td><?= e($m['startup_name']) ?></td>
            <td><?= e($m['firm_name'] ?: $m['investor_name']) ?></td>
            <td><?= date('d M Y, g:i A', strtotime($m['proposed_datetime'])) ?></td>
            <td><span class="badge badge-<?= $m['status'] === 'pending' ? 'pending-mt' : e($m['status']) ?>"><?= e(ucfirst($m['status'])) ?></span></td>
            <td><a href="<?= BASE_URL ?>/founder/pitch_detail.php?id=<?= $m['pitch_id'] ?>" class="btn btn-ghost btn-sm">View pitch</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
