<?php
require_once '../config/connect.php';

// Default admin credentials
$default_admin = [
    'username' => 'admin',
    'password' => 'admin123',
    'email' => 'admin@motoshapi.com',
    'full_name' => 'System Administrator'
];

try {
    // Check if admin user exists in admin table
    $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ?");
    $stmt->execute([$default_admin['username']]);
    $admin = $stmt->fetch();

    if (!$admin) {
        // Create admin user if it doesn't exist
        $stmt = $conn->prepare("INSERT INTO admin (username, password, email, full_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$default_admin['username'], $default_admin['password'], $default_admin['email'], $default_admin['full_name']]);
        echo "Admin user created successfully!<br>";
        echo "Username: " . $default_admin['username'] . "<br>";
        echo "Password: " . $default_admin['password'] . "<br>";
        echo "<strong>Please change these credentials after your first login!</strong>";
    } else {
        echo "Admin user already exists in the database.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 