<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();

$pageTitle = 'Terms of Service';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5" style="max-width: 800px;">
  <h1 class="fw-bold mb-4">Terms of Service</h1>
  <p class="text-muted">Last updated: <?= date('F Y') ?></p>

  <h5 class="fw-bold mt-4">1. Acceptance of Terms</h5>
  <p>By registering an account and using ClassSwap, you agree to these Terms of Service.</p>

  <h5 class="fw-bold mt-4">2. Eligibility</h5>
  <p>ClassSwap is intended for students. You must provide accurate information when registering and are responsible for maintaining the confidentiality of your account.</p>

  <h5 class="fw-bold mt-4">3. Listings</h5>
  <p>You may only list items you legally own and are entitled to sell. Prohibited, illegal, or dangerous items may not be listed. ClassSwap reserves the right to remove any listing that violates these terms, without prior notice.</p>

  <h5 class="fw-bold mt-4">4. Transactions</h5>
  <p>All transactions arranged through ClassSwap are strictly <strong>Cash on Meetup</strong> between buyer and seller. ClassSwap is not a party to any transaction, does not process payments, does not guarantee the condition, authenticity, or quality of any item listed, and is not responsible for disputes, losses, or damages arising from a transaction between users.</p>

  <h5 class="fw-bold mt-4">5. User Conduct</h5>
  <p>Users must not harass, defraud, or misrepresent themselves or their items to other users. Reported listings and accounts will be reviewed by our moderation team, and may result in suspension or removal.</p>

  <h5 class="fw-bold mt-4">6. Limitation of Liability</h5>
  <p>ClassSwap is provided "as is" without warranties of any kind. We are not liable for any loss, injury, or damage resulting from meetups, transactions, or interactions between users arranged through the platform.</p>

  <h5 class="fw-bold mt-4">7. Changes to These Terms</h5>
  <p>We may update these terms from time to time. Continued use of ClassSwap after changes constitutes acceptance of the updated terms.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>