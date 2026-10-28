<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();

$pageTitle = 'Contact Us';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5" style="max-width: 700px;">
  <h1 class="fw-bold mb-4">Contact Us</h1>
  <p class="lead">Have a question, found a bug, or need help with your account? Reach out to us.</p>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h6 class="fw-bold"><i class="bi bi-envelope-fill text-dark"></i> Email Support</h6>
      <p class="mb-0"><a href="mailto:support@classswap.test">support@classswap.test</a></p>
    </div>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h6 class="fw-bold"><i class="bi bi-flag-fill text-dark"></i> Report a Listing or User</h6>
      <p class="mb-0">If you've come across a suspicious or inappropriate listing, please use the <strong>"Report Listing"</strong> button on that product's page rather than emailing us — this routes the report directly to our moderation team for faster action.</p>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <h6 class="fw-bold"><i class="bi bi-question-circle-fill text-dark"></i> Frequently Asked</h6>
      <p class="mb-2"><strong>Is ClassSwap free to use?</strong><br>Yes, listing and browsing items is completely free.</p>
      <p class="mb-2"><strong>Does ClassSwap handle payments?</strong><br>No. All transactions are Cash on Meetup, arranged directly between buyer and seller.</p>
      <p class="mb-0"><strong>How accurate are the distances shown?</strong><br>Distances are approximate straight-line calculations between general areas, not exact walking/driving routes.</p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>