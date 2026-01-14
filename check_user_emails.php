<?php
require_once 'config/database.php';

echo "=== Recent Users ===\n\n";
$stmt = $conn->query('SELECT id, username, email FROM users ORDER BY id DESC LIMIT 5');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | User: {$row['username']} | Email: {$row['email']}\n";
}

echo "\n=== Recent Orders ===\n\n";
$stmt = $conn->query('SELECT o.id, o.first_name, o.last_name, s.email, o.created_at FROM orders o LEFT JOIN shipping_information s ON o.id = s.order_id ORDER BY o.id DESC LIMIT 3');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Order #{$row['id']} | Name: {$row['first_name']} {$row['last_name']} | Email: {$row['email']} | Date: {$row['created_at']}\n";
}
