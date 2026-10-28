# Module Explanations

## Authentication Module (register.php, login.php, logout.php, includes/auth.php)
Handles student registration with validated inputs, secure password hashing, login with 
session regeneration (prevents session fixation), and logout. `auth.php` provides helper 
functions used across the site: `current_user()`, `require_login()`, and 
`require_product_owner()` for ownership checks.

## Product Management Module (add-product.php, edit-product.php, my-listings.php)
Allows logged-in students to create, edit, hide, unhide, and soft-delete their own listings, 
with multi-image upload support validated through `includes/upload-helper.php`.

## Browse & Discovery Module (index.php, products.php, product-details.php)
Public-facing homepage and browse page with keyword search, category/condition/price/area 
filters, sorting (newest, price, nearest), and pagination. Product details page shows full 
info, image carousel, and distance from the viewer's selected area.

## Location & Distance Module (includes/functions.php — haversineDistanceKm, formatDistance)
Calculates approximate straight-line distance between two areas using the Haversine formula 
based on stored latitude/longitude, and formats it for display, always labeled "(approx.)" 
and never invented when coordinates are missing.

## Favorites Module (favorites.php)
Lets logged-in users save/unsave listings for later viewing, with a heart-toggle button 
available on every product card across the site.

## Communication Module (contact-seller.php, my-inquiries.php)
In-site inquiry system allowing buyers to message sellers about a specific listing, with a 
received/sent inbox view and read/close status tracking. WhatsApp deep links supplement this 
for direct contact when a seller has provided a number.

## Transactions Module (mark-sold.php)
Allows a seller to mark their own listing as sold and optionally record which buyer (from 
their inquiry history) purchased it, strictly under a Cash-on-Meetup payment method — no 
online payment is ever claimed or processed.

## Moderation Module (report-listing.php, admin/reports.php)
Lets users report a listing with a reason category and details; admins review, mark 
reviewed/dismissed, or hide the reported listing directly from the Reports panel.

## Admin Module (admin/*)
Completely separate authentication system (`admins` table, `$_SESSION['admin_id']`) with a 
dashboard of site statistics and full management screens for users (suspend/activate/delete), 
products (hide/unhide), categories (add/edit/activate/deactivate), and locations/areas 
(add/edit coordinates/activate/deactivate).

## Security Module (includes/csrf.php, includes/validation.php, includes/upload-helper.php)
Shared helpers enforcing CSRF protection on all state-changing forms, server-side input 
validation (names, emails, passwords, prices, descriptions), and secure file upload handling 
(extension + MIME + real-image verification + randomized filenames).