<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();

$pageTitle = 'About Us';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5" style="max-width: 800px;">
  <h1 class="fw-bold mb-4">About ClassSwap</h1>

  <p class="lead">ClassSwap is a student-to-student marketplace built to make buying and selling used items within your campus community simple, safe, and local.</p>

  <h5 class="fw-bold mt-4">Why ClassSwap?</h5>
  <p>Every semester, students buy textbooks, calculators, laptops, and hostel essentials — and at the end of the semester, many of these items just sit unused. ClassSwap connects students who want to sell these items with students nearby who need them, without the hassle of shipping, courier fees, or strangers from across the country.</p>

  <h5 class="fw-bold mt-4">How It Works</h5>
  <ul>
    <li><strong>List an item</strong> — Add photos, a description, price, condition, and your area.</li>
    <li><strong>Browse nearby</strong> — Search and filter by category, price, or distance from your area.</li>
    <li><strong>Connect directly</strong> — Message sellers through ClassSwap or reach out via WhatsApp.</li>
    <li><strong>Meet and pay safely</strong> — All transactions are Cash on Meetup. Meet in a safe, public campus location to exchange the item and payment in person.</li>
  </ul>

  <h5 class="fw-bold mt-4">Our Approach to Location</h5>
  <p>Distances shown on ClassSwap are approximate straight-line distances between general campus areas, calculated using the Haversine formula. They are meant to give you a rough sense of how close a listing is — always confirm exact meeting details directly with the seller.</p>

  <h5 class="fw-bold mt-4">Safety First</h5>
  <p>ClassSwap does not process any online payments and never will — all deals are Cash on Meetup, arranged directly between buyer and seller. We encourage meeting in public, well-lit areas on or near campus, and reporting any suspicious listings using the "Report Listing" button on a product page.</p>

  <p class="text-muted mt-5">ClassSwap was built as an academic Web Technology project, designed and developed with students in mind.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>