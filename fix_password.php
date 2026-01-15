<?php
require_once 'config/connect.php';

// Hash the password properly
$hashedPassword = password_hash('qwerty', PASSWORD_DEFAULT);

// Update the user
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$result = $stmt->execute([$hashedPassword, 'paulanthny21@gmail.com']);

if ($result) {
    echo "Password updated successfully!\n";
    echo "User can now login with email: paulanthny21@gmail.com and password: qwerty\n";
    
    // Verify
    $checkStmt = $conn->prepare("SELECT id, email, LEFT(password, 30) as pass FROM users WHERE email = ?");
    $checkStmt->execute(['paulanthny21@gmail.com']);
    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
    echo "\nPassword hash now starts with: {$user['pass']}...\n";
} else {
    echo "Failed to update password\n";
}
?>
