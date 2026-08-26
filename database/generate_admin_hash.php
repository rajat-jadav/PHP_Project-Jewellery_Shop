<?php
/**
 * Run this once from browser or CLI after importing schema.sql:
 *   http://localhost/jewellery-shop/database/generate_admin_hash.php
 * It prints a fresh bcrypt hash for password "admin1234" and updates
 * the admins table directly so login works on your machine.
 * DELETE this file after running it once (security).
 */
require_once __DIR__ . '/../config/db.php';

$plainPassword = 'admin1234';
$hash = password_hash($plainPassword, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE email = 'admin@jewellery.com'");
$stmt->execute([$hash]);

echo "Admin password reset to: <b>admin1234</b><br>";
echo "New hash stored: " . htmlspecialchars($hash) . "<br><br>";
echo "You can now log in at /admin/login.php with:<br>";
echo "Email: admin@jewellery.com<br>Password: admin1234<br><br>";
echo "<b>Delete this file now.</b>";
