# ClassSwap — Student Marketplace

ClassSwap is a web-based marketplace that allows university students to buy and sell used items 
(textbooks, electronics, hostel essentials, etc.) within their campus community.

## Tech Stack
- PHP 8 (plain, no framework)
- MySQL (via PDO with prepared statements)
- Bootstrap 5
- Vanilla JavaScript
- XAMPP (Apache + MySQL) for local hosting

## Features
- Student registration, login, logout, and profile management
- Product listing CRUD with multi-image upload
- Browse, search, filter (category/condition/price/area), sort, and paginate products
- Product detail pages with image carousel
- Favorites (save/unsave listings)
- Area-based distance estimation using the Haversine formula (approximate, clearly labeled)
- Radius filtering and "Nearest First" sorting
- Seller contact via in-site inquiry system and WhatsApp deep link
- Cash-on-Meetup transaction recording ("Mark as Sold")
- Listing reporting / moderation system
- Full separate admin panel: dashboard, user management, product management, 
  category management, location management, report moderation

## Installation (XAMPP)
1. Copy the `classswap` folder into `C:\xampp\htdocs\`
2. Start Apache and MySQL in XAMPP Control Panel
3. Open phpMyAdmin, create a database named `classswap`
4. Import `database/schema.sql`, then `database/seed.sql`
5. Visit `http://localhost/classswap/tools/create_admin.php` to create your first admin account 
   (then delete that file)
6. Visit `http://localhost/classswap/` to use the site

## Folder Structure
- `/admin` — Admin panel (separate session/auth system)
- `/assets` — CSS, JS, images
- `/config` — Database connection and app configuration
- `/database` — SQL schema and seed data
- `/includes` — Shared PHP components (header, navbar, footer, auth, CSRF, validation, uploads)
- `/uploads` — User-uploaded images (products, profiles)
- Root-level `.php` files — Public-facing pages

## Security Measures
- PDO prepared statements throughout (no raw SQL concatenation)
- Passwords hashed with `password_hash()` / verified with `password_verify()`
- CSRF tokens on all state-changing forms
- Session regenerated on login (student and admin)
- File upload validation: extension whitelist, MIME check, `getimagesize()` verification, 
  random filenames, `.htaccess` blocking script execution in upload directories
- Ownership checks before edit/delete actions
- Admin accounts stored in a fully separate `admins` table with a separate session namespace
- Soft-delete only (no destructive SQL DELETE on products)
- No raw database errors ever shown to end users

## Known Limitations
See `LIMITATIONS.md`

## Future Upgrades
See `FUTURE-UPGRADES.md`

## Test Cases
See `TEST-CASES.md`

## Module Explanations
See `MODULES.md`