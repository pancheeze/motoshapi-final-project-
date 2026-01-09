<?php
require_once '../config/database.php';

// Admin credentials
$admin = [
    'username' => 'admin',
    'password' => 'admin123',
    'email' => 'admin@motoshapi.com',
    'full_name' => 'System Administrator'
];

try {
    // First, check if the admin table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'admin'");
    if ($stmt->rowCount() == 0) {
        echo "Error: Admin table does not exist. Please import the database schema first.";
        exit();
    }

    // Delete existing admin user if exists
    $stmt = $conn->prepare("DELETE FROM admin WHERE username = ?");
    $stmt->execute([$admin['username']]);

    // Create new admin user
    $stmt = $conn->prepare("INSERT INTO admin (username, password, email, full_name) VALUES (?, ?, ?, ?)");
    $stmt->execute([$admin['username'], $admin['password'], $admin['email'], $admin['full_name']]);

    echo "<h2>Admin Account Reset Successful!</h2>";
    echo "<p>You can now log in with these credentials:</p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> " . $admin['username'] . "</li>";
    echo "<li><strong>Password:</strong> " . $admin['password'] . "</li>";
    echo "</ul>";
    echo "<p><a href='login.php'>Click here to go to the login page</a></p>";
    echo "<p><strong>Important:</strong> Please change these credentials after your first login!</p>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 