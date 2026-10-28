<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();

$pageTitle = 'Privacy Policy';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5" style="max-width: 800px;">
  <h1 class="fw-bold mb-4">Privacy Policy</h1>
  <p class="text-muted">Last updated: <?= date('F Y') ?></p>

  <h5 class="fw-bold mt-4">1. Information We Collect</h5>
  <p>When you register, we collect your full name, email address, password (securely hashed — we never store plain-text passwords), and optionally your university, area, and WhatsApp number. When you list a product, we store the details and photos you provide.</p>

  <h5 class="fw-bold mt-4">2. How We Use Your Information</h5>
  <ul>
    <li>To create and manage your account</li>
    <li>To display your listings and contact details to interested buyers (only what you choose to share, such as your WhatsApp number)</li>
    <li>To calculate approximate distances between your selected area and listings</li>
    <li>To allow other users to contact you about your listings via our inquiry system</li>
  </ul>

  <h5 class="fw-bold mt-4">3. Information Sharing</h5>
  <p>We do not sell or share your personal information with third parties. Your name and area are visible to other logged-in users browsing your listings. Your WhatsApp number is only shown to users viewing your product listing, if you've provided one.</p>

  <h5 class="fw-bold mt-4">4. Data Security</h5>
  <p>Passwords are hashed using industry-standard algorithms and never stored or displayed in readable form. We use secure coding practices to protect against common vulnerabilities, including validated file uploads and protection against SQL injection and cross-site request forgery.</p>

  <h5 class="fw-bold mt-4">5. Your Choices</h5>
  <p>You can update or remove your profile information, hide or delete your listings, and manage your favorites and inquiries at any time from your account.</p>

  <h5 class="fw-bold mt-4">6. Contact</h5>
  <p>Questions about this policy can be sent to <a href="mailto:support@classswap.test">support@classswap.test</a>.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>