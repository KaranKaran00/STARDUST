<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
$investor = require_investor();

$stmt = db()->prepare('SELECT p.*, f.full_name AS founder_name, f.email AS founder_email, f.phone AS founder_phone FROM pitches p JOIN founders f ON f.id = p.founder_id WHERE p.id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$pitch = $stmt->fetch();
if (!$pitch) { flash('error', 'Pitch not found.'); redirect(BASE_URL . '/admin/dashboard.php'); }

// ---- Handle actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['update_status'])) {
        $newStatus = $_POST['status'];
        if (in_array($newStatus, ['pending', 'in_review', 'shortlisted', 'rejected'], true)) {
            $stmt = db()->prepare('UPDATE pitches SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $pitch['id']]);
            flash('success', 'Status updated.');
        }
        redirect(BASE_URL . '/admin/pitch_detail.php?id=' . $pitch['id']);
    }

    if (isset($_POST['add_note'])) {
        $note = trim($_POST['note'] ?? '');
        if ($note !== '') {
            $stmt = db()->prepare('INSERT INTO investor_notes (pitch_id, investor_id, note) VALUES (?, ?, ?)');
            $stmt->execute([$pitch['id'], $investor['id'], $note]);
            flash('success', 'Note added.');
        }
        redirect(BASE_URL . '/admin/pitch_detail.php?id=' . $pitch['id']);
    }

    if (isset($_POST['schedule_meeting'])) {
        $when = $_POST['proposed_datetime'] ?? '';
        $msg = trim($_POST['message'] ?? '');
        if ($when) {
            $stmt = db()->prepare('INSERT INTO meetings (pitch_id, investor_id, founder_id, proposed_datetime, message) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$pitch['id'], $investor['id'], $pitch['founder_id'], str_replace('T', ' ', $when), $msg ?: null]);
            flash('success', 'Meeting request sent to the founder.');
        } else {
            flash('error', 'Pick a date and time first.');
        }
        redirect(BASE_URL . '/admin/pitch_detail.php?id=' . $pitch['id']);
    }
}

$stmt = db()->prepare('SELECT n.*, i.full_name AS investor_name FROM investor_notes n JOIN investors i ON i.id = n.investor_id WHERE n.pitch_id = ? ORDER BY n.created_at DESC');
$stmt->execute([$pitch['id']]);
$notes = $stmt->fetchAll();

$stmt = db()->prepare("SELECT m.*, i.full_name AS investor_name, i.firm_name FROM meetings m JOIN investors i ON i.id = m.investor_id WHERE m.pitch_id = ? ORDER BY m.proposed_datetime DESC");
$stmt->execute([$pitch['id']]);
$meetings = $stmt->fetchAll();

$page_title = $pitch['startup_name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container section-tight">
  <div class="eyebrow" style="color: var(--bloom-deep);">Investor desk</div>
  <div class="dash-header">
    <div>
      <h2 class="mb-0"><?= e($pitch['startup_name']) ?></h2>
      <p class="small muted mt-0"><?= e($pitch['tagline']) ?></p>
    </div>
    <form method="post" class="flex gap-8">
      <?= csrf_field() ?>
      <select name="status" onchange="this.form.submit()">
        <?php foreach (['pending' => 'Pending review', 'in_review' => 'In review', 'shortlisted' => 'Shortlisted', 'rejected' => 'Not moving forward'] as $val => $lbl): ?>
          <option value="<?= $val ?>" <?= $pitch['status'] === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="hidden" name="update_status" value="1">
      <noscript><button type="submit" class="btn btn-ghost btn-sm">Update</button></noscript>
    </form>
  </div>

  <div class="two-col">
    <div>
      <div class="card" style="background:#fff;">
        <div class="tag-list" style="margin-bottom:18px;">
          <span class="tag"><?= e($pitch['industry']) ?></span>
          <span class="tag"><?= e($pitch['funding_stage']) ?></span>
          <span class="tag"><?= e($pitch['business_model']) ?></span>
          <span class="tag tag-bloom"><?= money((int) $pitch['funding_ask']) ?> ask</span>
          <span class="tag">Team of <?= (int) $pitch['team_size'] ?></span>
        </div>
        <h3>Description</h3><p><?= nl2br(e($pitch['description'])) ?></p>
        <?php if ($pitch['problem_statement']): ?><h3>Problem</h3><p><?= nl2br(e($pitch['problem_statement'])) ?></p><?php endif; ?>
        <?php if ($pitch['traction']): ?><h3>Traction</h3><p><?= nl2br(e($pitch['traction'])) ?></p><?php endif; ?>
        <?php if ($pitch['website']): ?><h3>Website</h3><p><a href="<?= e($pitch['website']) ?>" target="_blank" rel="noopener"><?= e($pitch['website']) ?></a></p><?php endif; ?>

        <?php if ($pitch['deck_filename']): ?>
          <hr class="divider">
          <h3>Pitch deck</h3>
          <a href="<?= BASE_URL . UPLOAD_URL . '/' . e($pitch['deck_filename']) ?>" class="btn btn-ghost btn-sm" target="_blank" rel="noopener">Download deck</a>
          <?php if ($pitch['deck_text_excerpt']): ?>
            <p class="small muted" style="margin-top:14px;"><strong>Auto-extracted excerpt</strong></p>
            <p class="small"><?= e(mb_substr($pitch['deck_text_excerpt'], 0, 500)) ?>…</p>
          <?php endif; ?>
          <?php if ($pitch['deck_keywords']): ?>
            <div class="tag-list">
              <?php foreach (csv_to_array($pitch['deck_keywords']) as $kw): ?><span class="tag" style="background:var(--paper-deep);color:var(--ink-soft);"><?= e($kw) ?></span><?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <hr class="divider">
        <h3>Founder contact</h3>
        <p class="small"><?= e($pitch['founder_name']) ?> — <?= e($pitch['founder_email']) ?><?= $pitch['founder_phone'] ? ' — ' . e($pitch['founder_phone']) : '' ?></p>
      </div>

      <h3 style="margin-top:28px;">Schedule a meeting</h3>
      <div class="card" style="background:#fff;">
        <form method="post">
          <?= csrf_field() ?>
          <div class="field">
            <label for="proposed_datetime">Proposed date &amp; time</label>
            <input type="datetime-local" id="proposed_datetime" name="proposed_datetime" required>
          </div>
          <div class="field">
            <label for="message">Message to founder <span class="muted">(optional)</span></label>
            <textarea id="message" name="message" rows="3" placeholder="What you'd like to cover…"></textarea>
          </div>
          <button type="submit" name="schedule_meeting" value="1" class="btn btn-bloom">Send meeting request</button>
        </form>
      </div>

      <?php if ($meetings): ?>
        <h3 style="margin-top:28px;">Meetings on this pitch</h3>
        <?php foreach ($meetings as $m): ?>
          <div class="note">
            <?= date('d M Y, g:i A', strtotime($m['proposed_datetime'])) ?>
            <span class="badge badge-<?= $m['status'] === 'pending' ? 'pending-mt' : e($m['status']) ?>"><?= e(ucfirst($m['status'])) ?></span>
            <?php if ($m['message']): ?><p class="small" style="margin:6px 0 0;"><?= nl2br(e($m['message'])) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div>
      <h3>Internal notes <span class="small muted">(investors only)</span></h3>
      <form method="post" style="margin-bottom:18px;">
        <?= csrf_field() ?>
        <div class="field">
          <textarea name="note" rows="3" placeholder="Diligence notes, thoughts, follow-ups…" required></textarea>
        </div>
        <button type="submit" name="add_note" value="1" class="btn btn-ghost btn-sm btn-block">Add note</button>
      </form>
      <?php if (!$notes): ?>
        <p class="small muted">No notes yet.</p>
      <?php else: ?>
        <?php foreach ($notes as $n): ?>
          <div class="note">
            <?= nl2br(e($n['note'])) ?>
            <div class="meta"><?= e($n['investor_name']) ?> · <?= time_ago($n['created_at']) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
