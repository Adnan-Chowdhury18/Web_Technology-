# Test Cases

## Authentication
| # | Test | Expected Result |
|---|------|------------------|
| 1 | Register with valid details | Account created, redirected to login |
| 2 | Register with existing email | Error: email already registered |
| 3 | Register with weak password (e.g. "abc") | Error: password policy not met |
| 4 | Login with correct credentials | Logged in, session started, redirected to homepage |
| 5 | Login with wrong password | Error: invalid email or password |
| 6 | Logout | Session destroyed, redirected to homepage, protected pages inaccessible |

## Product Listings
| # | Test | Expected Result |
|---|------|------------------|
| 7 | Add product with valid data + images | Product created, appears in Browse and My Listings |
| 8 | Add product with invalid price (negative) | Error shown, product not created |
| 9 | Upload a non-image file disguised as .jpg | Rejected by MIME/getimagesize validation |
| 10 | Edit own product | Changes saved successfully |
| 11 | Attempt to edit another user's product via URL | 403 Forbidden / ownership check blocks it |
| 12 | Hide a listing | Status becomes Hidden, disappears from Browse Products |
| 13 | Delete a listing | Status becomes Hidden in database (soft-delete), row still exists |

## Browse & Search
| # | Test | Expected Result |
|---|------|------------------|
| 14 | Search by keyword | Only matching titles/descriptions shown |
| 15 | Filter by category | Only that category's products shown |
| 16 | Filter by price range | Only products within range shown |
| 17 | Filter by area + radius | Only products from areas within radius shown |
| 18 | Sort by Nearest First without selecting an area | Warning shown, falls back to newest-first |
| 19 | Pagination | Page 2+ shows correct next set of results, filters preserved |

## Favorites
| # | Test | Expected Result |
|---|------|------------------|
| 20 | Click heart on a product (logged in) | Added to favorites, icon fills red |
| 21 | Click filled heart again | Removed from favorites |
| 22 | View Favorites page | Shows all currently favorited products |
| 23 | View product card while logged out | No heart button shown |

## Contact & Inquiries
| # | Test | Expected Result |
|---|------|------------------|
| 24 | Contact seller about their product | Inquiry created, seller can see it in "Received" |
| 25 | Attempt to contact seller about own product | Blocked with error message |
| 26 | Mark inquiry as read (as seller) | Status changes to "read" |
| 27 | Close an inquiry (either party) | Status changes to "closed" |

## Transactions
| # | Test | Expected Result |
|---|------|------------------|
| 28 | Mark own Available listing as Sold | Status becomes Sold, transaction record created |
| 29 | Attempt to mark someone else's listing as sold via URL | Blocked by ownership check |
| 30 | View Sold listing's product page | "Mark as Sold" button no longer shown |

## Admin
| # | Test | Expected Result |
|---|------|------------------|
| 31 | Login to /admin/login.php with valid admin account | Redirected to admin dashboard |
| 32 | Access /admin/dashboard.php without logging in | Redirected to admin login |
| 33 | Suspend a user | User's status becomes Suspended, cannot log in to main site |
| 34 | Hide a product from admin panel | Product disappears from Browse Products |
| 35 | Add a new category | Appears immediately in navbar dropdown and homepage |
| 36 | Deactivate a category | Disappears from public site, remains visible (inactive) in admin |
| 37 | Add a new area with coordinates | Appears in area dropdowns across the site |
| 38 | Submit a report on a listing (as student), then review it as admin | Report appears in admin Reports with status Pending, can be marked reviewed/dismissed/hidden |
| 39 | Admin logout | Redirected to admin login; student session (if any, in another tab) remains unaffected |

## Security
| # | Test | Expected Result |
|---|------|------------------|
| 40 | Submit any state-changing form without a valid CSRF token (e.g. via a direct request) | Request rejected |
| 41 | Attempt SQL injection in search box (e.g. `' OR '1'='1`) | No error, no data leak, treated as literal search text |
| 42 | Attempt XSS in product title/description (e.g. `<script>alert(1)</script>`) | Rendered as plain text, not executed |
| 43 | Visit a non-existent URL | Custom 404 page shown, not a raw Apache error |