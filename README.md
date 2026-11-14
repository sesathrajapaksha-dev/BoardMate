# BoardMate - Find. Compare. Stay.

A boarding discovery and management platform for University of Moratuwa students.
Built with HTML5, CSS3, vanilla JavaScript, PHP (PDO) and MySQL. Runs locally on XAMPP.

---

## 1. Install and run (XAMPP)

1. Install **XAMPP** and start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy the `BoardMate` folder into `C:\xampp\htdocs\` so you have `C:\xampp\htdocs\BoardMate\index.php`.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) -> **Import** -> choose `database/boardmate_db.sql` -> **Go**.
   This creates the database `boardmate_db`, all tables and the 10 facilities.
4. Check `config/database.php`. The XAMPP defaults already work (`root`, empty password, `/BoardMate`).
   If you put the project in a different folder name, change `BASE_URL` there.
5. Open `http://localhost/BoardMate/database/setup.php` and create the administrator account (see section 3).
6. Open `http://localhost/BoardMate/` and start using the site.

## 2. Sample accounts

If you tick **"Add demo data"** on the setup page you get these accounts (all with password `Demo@1234`):

| Role | Username |
|---|---|
| Student | `kasun_s`, `nimali_s`, `tharindu_s` |
| Owner | `owner_silva`, `owner_jaya`, `owner_fernando` |

It also adds 8 boarding advertisements around Katubedda, Moratuwa, Rawathawatta, Egodauyana, Angulana and Rathmalana, with 9 reviews.
Demo advertisements have no photos, so a placeholder image is shown. Log in as an owner and edit them to add photos.

## 3. Creating the administrator account securely

There is **no default admin password** in the code or SQL file. The administrator is created by `database/setup.php`:

1. Open `http://localhost/BoardMate/database/setup.php`.
2. Enter **your own** username, email and a strong password (minimum 8 characters). The password is stored with `password_hash()`.
3. The page only works while **no admin exists**, so it cannot create a second admin.
4. **Delete `database/setup.php` immediately afterwards.**
5. Nobody can register as admin through the website: the registration form only accepts Student or Owner, and the server rejects anything else.

## 4. Database relationships

```
users (user_id PK)
  |--< boardings (owner_id FK)              one owner has many advertisements
  |       |--< boarding_images              one advertisement has many photos
  |       |--< boarding_facilities >-- facilities    many-to-many (composite PK)
  |       |--< reviews (boarding_id FK)     one advertisement has many reviews
  |--< reviews (student_id FK)              one student writes many reviews
  |--< activity_logs (user_id FK)           audit trail for the admin
```

- **users -> boardings**, **boardings -> images / facility links / reviews**, **users -> reviews**: `ON DELETE CASCADE`.
  Deleting an owner removes their advertisements, photos and the reviews on them. Deleting a student removes their reviews.
- **boarding_facilities** is the join table for the many-to-many link between boardings and facilities. Its primary key is `(boarding_id, facility_id)`.
- **reviews** has `UNIQUE (student_id, boarding_id)`: a student can review a boarding only once (a second submit updates the first).
- **activity_logs** uses `ON DELETE SET NULL`, so the history is kept even after a user is deleted.
- Indexes exist on username, email, role, location, price, created date, university and rating.
- `boardings.university` lets the platform grow to other universities later.

## 5. CRUD operations

| | Create | Read | Update | Delete |
|---|---|---|---|---|
| **Users** | `register_action.php` | profile, dashboards, admin users list | `student/profile.php` (details, password) | admin: `admin/delete_user.php` |
| **Boardings** | `owner/add_boarding.php` | home, browse, details, compare, owner dashboard, admin list | `owner/edit_boarding.php` | `owner/delete_boarding.php` (owner: own only, admin: any) |
| **Photos** | upload in add / edit | gallery, cards | add or remove in edit | removed with the file from disk |
| **Facilities** | ticked in add / edit | details, compare | edit | edit |
| **Reviews** | `actions/add_review.php` | details, my reviews, owner reviews, admin list | `actions/update_review.php` | `actions/delete_review.php` (own), `admin/delete_review.php` (any) |

Every operation uses PDO prepared statements.

## 6. Implemented functionality

**Students:** register / login / logout, browse with cards, search, filters (price, location, beds, rating), 4 sort orders, pagination, boarding details with gallery, compare up to 3 boardings (localStorage + table with lowest price and top rating highlighted), 1-5 star reviews (add, edit, delete, one per boarding), My Reviews, dashboard, account and password management.

**Owners:** register / login, dashboard (advertisements, total beds, average rating, review count, recent reviews, rating breakdown), add / edit / delete advertisements with multiple photos and facilities, My Boardings, all reviews on their advertisements. Owners can only touch their own advertisements.

**Administrator:** dashboard with statistics (users, students, owners, advertisements, reviews, failed logins), manage users (search, role filter, delete), manage advertisements (search, remove any), manage reviews (search, rating filter, delete), activity log (search, action filter). Confirmation pop-up before every destructive action.

**Security:** `password_hash` / `password_verify`, PDO prepared statements, role checks on every protected page, ownership checks, CSRF tokens on every state-changing form, output escaping (XSS), image validation by real content (not just file name), random safe file names, execution blocked in `uploads/`, login lockout (5 failures / 5 minutes), session regeneration on login, `.htaccess` protection for `includes/` and `config/`.

**Design:** responsive layout (Flexbox and Grid), Inter and Poppins fonts, Font Awesome icons, the BoardMate colour palette, mobile navigation.

## 7. Testing

See **`TESTING.md`**: results of 192 automated checks, the bugs that were found and fixed, a manual browser checklist and a 10-minute test script.

## 8. Folder structure

```
BoardMate/
|- index.php  login.php  register.php  logout.php
|- config/        database.php
|- includes/      header, navbar, footer, auth, functions and feature helpers
|- student/       dashboard, browse, boarding_details, compare, my_reviews, profile
|- owner/         dashboard, add_boarding, my_boardings, edit_boarding, delete_boarding, reviews
|- admin/         dashboard, users, boardings, reviews, activity, delete_user, delete_review
|- actions/       register_action, login_action, add_review, update_review, delete_review
|- assets/        css/style.css, js/*.js, images/
|- uploads/boardings/    uploaded photos (script execution blocked)
|- database/      boardmate_db.sql, setup.php (delete after use)
```
