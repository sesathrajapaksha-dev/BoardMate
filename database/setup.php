<?php
/**
 * BoardMate one-time setup page.
 * Creates the admin account (with YOUR password) and optional demo data.
 * DELETE THIS FILE after running it.
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$errors = [];
$success = false;
$summary = [];

$adminExists = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

if (!$adminExists && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $withDemo = isset($_POST['demo']);

    if (!preg_match('/^[A-Za-z0-9_]{4,30}$/', $username)) {
        $errors[] = 'Username must be 4-30 characters (letters, numbers, underscore).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // ----- Admin account -----
            $stmt = $pdo->prepare(
                "INSERT INTO users (full_name, username, email, phone, password, role)
                 VALUES (?, ?, ?, ?, ?, 'admin')"
            );
            $stmt->execute(['Site Administrator', $username, $email, '0110000000',
                            password_hash($password, PASSWORD_DEFAULT)]);
            $summary[] = "Admin account '{$username}' created.";

            // ----- Demo data -----
            if ($withDemo) {
                $demoHash = password_hash('Demo@1234', PASSWORD_DEFAULT);
                $userIns = $pdo->prepare(
                    "INSERT INTO users (full_name, username, email, phone, password, role)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                $demoUsers = [
                    ['Kasun Perera',      'kasun_s',   'kasun@example.com',   '0711111111', 'student'],
                    ['Nimali Fernando',   'nimali_s',  'nimali@example.com',  '0712222222', 'student'],
                    ['Tharindu Silva',    'tharindu_s','tharindu@example.com','0713333333', 'student'],
                    ['Mr. Silva',         'owner_silva',   'silva@example.com',   '0771111111', 'owner'],
                    ['Mrs. Jayasinghe',   'owner_jaya',    'jaya@example.com',    '0772222222', 'owner'],
                    ['Mr. Fernando',      'owner_fernando','fernando@example.com','0773333333', 'owner'],
                ];
                $uid = [];
                foreach ($demoUsers as $u) {
                    $userIns->execute([$u[0], $u[1], $u[2], $u[3], $demoHash, $u[4]]);
                    $uid[$u[1]] = (int)$pdo->lastInsertId();
                }

                // facility name => id
                $fac = [];
                foreach ($pdo->query("SELECT facility_id, facility_name FROM facilities") as $f) {
                    $fac[$f['facility_name']] = (int)$f['facility_id'];
                }

                $demoBoardings = [
                    ['owner_silva', 'Campus View Boarding House', 'Katubedda',
                     'No. 12, Katubedda Road, Moratuwa', 9500, 4, '0771111111',
                     'A quiet, clean boarding house just a 5 minute walk from the university main gate. Spacious rooms with study tables and a friendly family environment. Ideal for engineering and architecture students.',
                     ['Wi-Fi','Electricity','Water','Shared bathroom','Kitchen','Furnished rooms']],
                    ['owner_silva', 'Green Leaf Residence', 'Katubedda',
                     'No. 45, Sri Jinarathana Road, Katubedda', 12000, 2, '0771111111',
                     'Modern rooms with attached bathrooms and a peaceful garden area. Fast Wi-Fi suitable for online lectures and project work.',
                     ['Wi-Fi','Electricity','Water','Attached bathroom','Furnished rooms','Parking']],
                    ['owner_jaya', 'Moratuwa Student Lodge', 'Moratuwa',
                     'No. 8, Galle Road, Moratuwa', 7500, 6, '0772222222',
                     'Affordable shared rooms close to the Moratuwa town and bus routes. Common kitchen and laundry area available for all residents.',
                     ['Electricity','Water','Shared bathroom','Kitchen','Laundry facilities']],
                    ['owner_jaya', 'Rawathawatta Comfort Stay', 'Rawathawatta',
                     'No. 21, Rawathawatta Road, Moratuwa', 8500, 3, '0772222222',
                     'Comfortable rooms in a calm neighbourhood. Walking distance to the university and nearby food shops. Safe and secure for female and male students.',
                     ['Wi-Fi','Electricity','Water','Shared bathroom','Kitchen']],
                    ['owner_fernando', 'Egodauyana Boys Hostel', 'Egodauyana',
                     'No. 3, Egodauyana Lane, Moratuwa', 6500, 8, '0773333333',
                     'Budget friendly hostel with shared rooms. Basic facilities with 24 hour water and electricity. Great for first year students.',
                     ['Electricity','Water','Shared bathroom','Kitchen']],
                    ['owner_fernando', 'Angulana Executive Rooms', 'Angulana',
                     'No. 77, Station Road, Angulana', 15000, 2, '0773333333',
                     'Premium air-conditioned rooms with attached bathroom, fully furnished. Close to Angulana railway station for easy travel.',
                     ['Wi-Fi','Electricity','Water','Attached bathroom','Furnished rooms','Air conditioning','Parking','Laundry facilities']],
                    ['owner_silva', 'Rathmalana Garden Boarding', 'Rathmalana',
                     'No. 19, Airport Road, Rathmalana', 8000, 5, '0771111111',
                     'Spacious house with a big garden. Rooms shared by two students. Bus halt right in front of the house.',
                     ['Electricity','Water','Shared bathroom','Kitchen','Parking']],
                    ['owner_jaya', 'Katubedda Scholars Inn', 'Katubedda',
                     'No. 60, Kandawala Road, Katubedda', 10500, 3, '0772222222',
                     'Study friendly environment with quiet hours, Wi-Fi in every room and a shared study hall. Only 3 minutes from the campus.',
                     ['Wi-Fi','Electricity','Water','Shared bathroom','Kitchen','Furnished rooms','Laundry facilities']],
                ];

                $bIns = $pdo->prepare(
                    "INSERT INTO boardings
                     (owner_id, boarding_name, description, location, full_address,
                      price_per_person, available_beds, contact_number)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $fIns = $pdo->prepare("INSERT INTO boarding_facilities (boarding_id, facility_id) VALUES (?, ?)");

                $bid = [];
                foreach ($demoBoardings as $i => $b) {
                    $bIns->execute([$uid[$b[0]], $b[1], $b[7], $b[2], $b[3], $b[4], $b[5], $b[6]]);
                    $bid[$i] = (int)$pdo->lastInsertId();
                    foreach ($b[8] as $fname) {
                        $fIns->execute([$bid[$i], $fac[$fname]]);
                    }
                }

                $demoReviews = [
                    ['kasun_s',    0, 5, 'Excellent place! Very close to the university and the owner is very helpful.'],
                    ['nimali_s',   0, 4, 'Clean rooms and good water supply. Gets a bit noisy during weekends.'],
                    ['tharindu_s', 0, 5, 'Best boarding I have stayed in near campus. Highly recommended.'],
                    ['kasun_s',    1, 4, 'Wi-Fi is fast and rooms are modern. A little pricey but worth it.'],
                    ['nimali_s',   2, 3, 'Affordable, but the kitchen gets crowded in the evenings.'],
                    ['tharindu_s', 2, 4, 'Good value for money, easy bus access to town.'],
                    ['kasun_s',    4, 3, 'Basic but okay for the price. Needs better maintenance.'],
                    ['nimali_s',   5, 5, 'Luxury! The AC rooms are amazing for hot days.'],
                    ['tharindu_s', 7, 5, 'Perfect for studying. Quiet and very close to campus.'],
                ];
                $rIns = $pdo->prepare(
                    "INSERT INTO reviews (student_id, boarding_id, rating, review_text) VALUES (?, ?, ?, ?)"
                );
                foreach ($demoReviews as $r) {
                    $rIns->execute([$uid[$r[0]], $bid[$r[1]], $r[2], $r[3]]);
                }

                $summary[] = 'Demo data added: 3 students, 3 owners, 8 boardings, 9 reviews.';
                $summary[] = 'All demo accounts use the password: Demo@1234';
            }

            $pdo->commit();
            $success = true;
            $adminExists = true;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors[] = 'Setup failed: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>BoardMate Setup</title>
<style>
  body{font-family:Arial,sans-serif;background:#F8FAFC;color:#1E293B;margin:0;padding:30px}
  .box{max-width:480px;margin:auto;background:#fff;padding:30px;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.08)}
  h1{color:#172554;margin-top:0}
  label{display:block;margin:14px 0 4px;font-weight:bold;font-size:14px}
  input[type=text],input[type=email],input[type=password]{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;box-sizing:border-box}
  button{margin-top:20px;background:#2563EB;color:#fff;border:0;padding:12px 20px;border-radius:8px;font-size:15px;cursor:pointer;width:100%}
  .err{background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:10px}
  .ok{background:#dcfce7;color:#166534;padding:10px;border-radius:8px;margin-bottom:10px}
  .warn{background:#fef9c3;color:#854d0e;padding:10px;border-radius:8px;margin-top:16px;font-size:14px}
</style>
</head>
<body>
<div class="box">
  <h1>BoardMate Setup</h1>

  <?php foreach ($errors as $er): ?><div class="err"><?= e($er) ?></div><?php endforeach; ?>
  <?php foreach ($summary as $s): ?><div class="ok"><?= e($s) ?></div><?php endforeach; ?>

  <?php if ($adminExists): ?>
    <p>Setup is complete. An administrator account already exists.</p>
    <div class="warn"><b>Important:</b> delete <code>database/setup.php</code> from your project now.</div>
  <?php else: ?>
    <p>Create the administrator account. Choose a strong password.</p>
    <form method="post" autocomplete="off">
      <label>Admin username</label>
      <input type="text" name="username" required value="<?= e($_POST['username'] ?? 'admin') ?>">
      <label>Admin email</label>
      <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
      <label>Password (min 8 characters)</label>
      <input type="password" name="password" required>
      <label>Confirm password</label>
      <input type="password" name="confirm" required>
      <label style="font-weight:normal;margin-top:18px">
        <input type="checkbox" name="demo" checked> Also insert demo students, owners, boardings and reviews
      </label>
      <button type="submit">Run Setup</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
