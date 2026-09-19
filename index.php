<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Light stats for the hero — quietly fails to zero if DB isn't set up yet.
try {
    $pdo = db();
    $founderCount = (int) $pdo->query("SELECT COUNT(*) FROM founders")->fetchColumn();
    $pitchCount = (int) $pdo->query("SELECT COUNT(*) FROM pitches")->fetchColumn();
    $investorCount = (int) $pdo->query("SELECT COUNT(*) FROM investors")->fetchColumn();
} catch (Throwable $e) {
    $founderCount = $pitchCount = $investorCount = 0;
}

$page_title = 'Home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container hero-grid">

    <div>
      <div class="eyebrow">Startup Pitchdeck Reviewer</div>

      <h1>Where a good deck finds the right room.</h1>

      <p class="lede">
        Stardust connects founders raising a round with investors who already
        want what they're building — filtered by industry, stage and business
        model, not cold emails.
      </p>

      <div class="flex gap-12" style="margin-top: 28px;">
        <a href="<?= BASE_URL ?>/register_founder.php" class="btn btn-primary">
          Pitch your startup
        </a>

        <a href="<?= BASE_URL ?>/login_investor.php" class="btn btn-ghost">
          I'm an investor
        </a>
      </div>

      <div class="hero-stats">
        <div>
          <span class="hero-stat-num"><?= $founderCount ?></span><br>
          <span class="hero-stat-label">Founders</span>
        </div>

        <div>
          <span class="hero-stat-num"><?= $pitchCount ?></span><br>
          <span class="hero-stat-label">Pitches submitted</span>
        </div>

        <div>
          <span class="hero-stat-num"><?= $investorCount ?></span><br>
          <span class="hero-stat-label">Investors</span>
        </div>
      </div>
    </div>

    <!-- Hero Image -->
    <div class="hero-blob">
      <div class="hero-blob-inner">
        <img
          src="<?= BASE_URL ?>/webimage.png"
          alt="Founder and investor handshake"
          style="width: 100%; max-width: 520px; height: auto; display: block;"
        >
      </div>
    </div>

  </div>
</section>

<section id="how-it-works" class="section-tight">
  <div class="container">

    <div class="eyebrow">How it works</div>

    <h2 class="mb-0">Two sides, one review loop</h2>

    <p class="lede">
      Founders submit once. Investors filter, read, and reach out —
      Stardust keeps the whole thread in one place.
    </p>

    <div class="grid grid-3" style="margin-top: 36px;">

      <div class="card">
        <h3>1. Create your pitch</h3>
        <p>
          Founders register, fill out a structured pitch form — industry,
          stage, ask, traction — and upload their deck as a PDF or PPTX.
        </p>
      </div>

      <div class="card">
        <h3>2. Deck gets read automatically</h3>
        <p>
          A Python script pulls a text excerpt and keywords straight out of
          the uploaded deck, so reviewers get a preview before opening the file.
        </p>
      </div>

      <div class="card">
        <h3>3. Investors filter &amp; reach out</h3>
        <p>
          Investors filter startups against their own portfolio preferences,
          leave internal notes, and propose a meeting time directly.
        </p>
      </div>

    </div>
  </div>
</section>

<section class="section-tight">
  <div class="container two-col">

    <div class="card" style="background:#fff;">
      <div class="eyebrow">For founders</div>

      <h2>One form. Every serious investor.</h2>

      <p>
        Tell us who you are, what you're building, and how much you're raising.
        Your pitch stays visible to investors until you choose otherwise, and
        you'll see every note and meeting request land in your dashboard.
      </p>

      <a href="<?= BASE_URL ?>/register_founder.php" class="btn btn-primary">
        Create a founder account
      </a>
    </div>

    <div class="card"
         style="background: var(--bloom-pale); border-color: #E3BFCB;">

      <div class="eyebrow" style="color: var(--bloom-deep);">
        For investors
      </div>

      <h2>Set your thesis once.</h2>

      <p>
        Save your industries, funding stages and ticket size — then filter
        every incoming pitch against them in one view, with meeting
        scheduling built in.
      </p>

      <a href="<?= BASE_URL ?>/login_investor.php" class="btn btn-bloom">
        Investor login
      </a>
    </div>

  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
