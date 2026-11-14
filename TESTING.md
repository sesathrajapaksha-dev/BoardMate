# BoardMate - Testing Report (Phase 8)

## 1. How the testing was done

The whole site was run on PHP 8.3 + MariaDB 10.11 (the same stack as XAMPP), loaded with the
demo data from `database/setup.php`. A script then logged in as a **guest, student, owner and
administrator**, sent real HTTP requests (including file uploads and forged requests) and checked
both the page output and the database after every step.

**Result: 192 checks, 192 passed, 0 PHP warnings or notices in the server log.**

| Area | Checks |
|---|---|
| Access control (guest / student / owner / admin) | 30 |
| Registration and login | 19 |
| CSRF, SQL injection, XSS | 25 |
| Owner: add / edit / delete + image uploads | 34 |
| Student: reviews, compare, profile | 26 |
| Search, filters, sorting, pagination | 19 |
| Administrator (Phase 7) | 39 |

All 8 JavaScript files were also syntax-checked with Node (`node --check`) and every PHP file with `php -l`.

## 2. Bugs found and fixed

| # | Where | Problem | Fix |
|---|---|---|---|
| 1 | `student/boarding_details.php` | **Fatal error (HTTP 500) for every logged-in student.** A statement had been split across lines (`... === (int)` / `require_once ...` / `$b['owner_id'];`), so `review_functions.php` was only loaded when an owner viewed the page. | Moved the `require_once` to the top and rewrote the line. |
| 2 | `student/boarding_details.php` | The "Edit advertisement" button was compared against the number `1` (the result of `require_once`) instead of the boarding's owner ID. | `$isOwnerView` now compares with `$b['owner_id']`. |
| 3 | `includes/add_review.php` | The review form posts to `actions/add_review.php`, but the file was saved in `includes/`. Submitting a new review gave **404**. | File moved to `actions/add_review.php`. |
| 4 | Phase 6 | The commit "Owner dashboard" was incomplete: `owner/dashboard.php` was still the placeholder, `owner/my_boardings.php` and `includes/owner_functions.php` did not exist (so `owner/reviews.php` crashed and many buttons led to 404). | Added `owner_functions.php`, `owner_boarding_table.php`, `my_boardings.php` and a real `dashboard.php`. |
| 5 | `actions/login_action.php` | The 5-attempt lockout was stored only in the session, so deleting the cookie removed the lock. | Failed attempts are now also counted in `activity_logs` (per username + IP). |
| 6 | Git | 26 files showed as "modified" although nothing changed (Windows CRLF vs LF). | Added `.gitattributes`. |
| 7 | Hardening | `includes/` and `config/` could be opened directly in a browser. | Added `.htaccess` (`Require all denied`) to both folders. |

## 3. Manual checks to do in a browser (cannot be automated here)

Please tick these off on your own XAMPP:

- [ ] **Responsive layout** at 360 px (phone), 768 px (tablet), 1024 px and 1280 px. Use the browser's device toolbar (F12). Check the navbar menu, browse filters button, details gallery, compare table (scrolls sideways), and the admin tables.
- [ ] **Compare tray**: add 3 boardings on Browse, the tray appears, the 4th is refused with a message, "Compare now" opens the table, removing works, list survives a page refresh.
- [ ] **Image gallery**: arrows, thumbnails and photo counter on a boarding with several photos.
- [ ] **Star rating input**: hovering and selecting stars in the review form.
- [ ] **Confirm pop-ups** appear before every delete (own review, own advertisement, and every admin delete).
- [ ] **Upload limits on XAMPP**: upload 8 photos of about 2 MB each. If it fails, raise `post_max_size` and `upload_max_filesize` in `php.ini` (XAMPP default is high enough).
- [ ] Open `http://localhost/BoardMate/includes/functions.php` and `.../config/database.php`: both must show **403 Forbidden** (proves `.htaccess` is active; XAMPP uses `AllowOverride All` by default).
- [ ] **Delete `database/setup.php`** after creating the administrator account.

## 4. Quick manual test script (about 10 minutes)

1. Register a student and an owner. Log in as the owner, add a boarding with 2 photos, edit its price, remove one photo.
2. Log in as the student, find it with search and filters, add it to compare with two others, write a review, edit it, try to review again (it must update, not duplicate).
3. As the owner, open the dashboard: counts, rating breakdown and the new review must appear.
4. As the administrator: check the five statistics cards, search a user, delete a test student, delete a review, remove the owner's advertisement, and look at the Activity log for those actions.
5. Try opening `/admin/dashboard.php` while logged in as a student: you must be redirected away.

## 5. Known limits (by design, for a university project)

- Demo accounts all use the password `Demo@1234`. Do not use the demo data on a public server.
- There is no password-reset by email (outside the project scope).
- The login lockout is per username and IP address for 5 minutes.
