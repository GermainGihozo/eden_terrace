<?php
// reset-passwords.php - Run this once to reset all passwords
require_once 'includes/db.php';

$db = getDB();

// Update all passwords to 'password123'
$hashed_password = password_hash('password123', PASSWORD_DEFAULT);

$stmt = $db->prepare("UPDATE users SET password = ?");
$stmt->execute([$hashed_password]);

echo "All passwords reset to 'password123'<br>";
echo "New hash: " . $hashed_password;

// Show all users with their new passwords
$stmt = $db->query("SELECT id, email, full_name FROM users");
$users = $stmt->fetchAll();

echo "<h3>All Users:</h3>";
echo "<table border='1'>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . $user['email'] . "</td>";
    echo "<td>" . $user['full_name'] . "</td>";
    echo "<td>password123</td>";
    echo "</tr>";
}
echo "</table>";
?>