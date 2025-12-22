<?php
// test-login.php
session_start();
echo "<h3>Login Debug Test</h3>";

// Test database connection
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $db = getDB();
    echo "<p style='color:green;'>✓ Database connected successfully</p>";
    
    // Test users table
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>Users in database: " . $result['count'] . "</p>";
    
    // Show all users
    $stmt = $db->query("SELECT id, email, full_name FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    
    echo "<h4>Sample Users:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Name</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['full_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test password verification
    echo "<h4>Password Test:</h4>";
    $test_password = "password123";
    $test_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    if (password_verify($test_password, $test_hash)) {
        echo "<p style='color:green;'>✓ Password verification works</p>";
    } else {
        echo "<p style='color:red;'>✗ Password verification failed</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test session
echo "<h4>Session Status:</h4>";
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";
echo "Session variables: ";
print_r($_SESSION);
?>