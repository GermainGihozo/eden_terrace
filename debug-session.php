<?php
session_start();
echo "<h2>Session Debug</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "\n\nCookies:\n";
print_r($_COOKIE);
echo "</pre>";

// Test login
echo "<h3>Test Login Function</h3>";
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

echo "<p>Is logged in? " . (isLoggedIn() ? 'YES' : 'NO') . "</p>";
echo "<p>Current user: ";
print_r(getCurrentUser());
echo "</p>";

// Test database user
if (isset($_SESSION['user_id'])) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $db_user = $stmt->fetch();
    
    echo "<h3>Database User Data:</h3>";
    echo "<pre>";
    print_r($db_user);
    echo "</pre>";
}
?>