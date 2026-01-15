<?php
/**
 * User Sync Test - Motoshapi to Pizzeria
 * 
 * This file tests the automatic user synchronization between Motoshapi and Pizzeria.
 * Only customer accounts are synced, not products or orders.
 */

session_start();
require_once 'includes/PizzeriaAPIClient.php';

// ========================================
// CONFIGURATION
// ========================================
// Replace this with the actual IP address of the computer running Pizzeria
// Find it using: ipconfig (Windows) or ifconfig (Mac/Linux)
$PIZZERIA_SERVER_IP = '10.38.247.140'; // CHANGE THIS!

// Or use 'localhost' if both systems are on the same computer
// $PIZZERIA_SERVER_IP = '10.38.247.140';

$pizzeriaBaseURL = "http://$PIZZERIA_SERVER_IP/pizzeria";

// ========================================
// Initialize API Client
// ========================================
$pizzeria = new PizzeriaAPIClient($pizzeriaBaseURL);

// Set custom timeout (optional)
$pizzeria->setTimeout(10); // 10 seconds timeout

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sync Test - Motoshapi to Pizzeria</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #28a745;
            padding-bottom: 10px;
        }
        h1 .emoji {
            font-size: 2rem;
        }
        h2 {
            color: #495057;
            margin-top: 30px;
        }
        .status {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .pizza-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .user-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            background: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info h3 {
            margin: 0 0 5px 0;
            color: #007bff;
        }
        .user-info p {
            margin: 3px 0;
            color: #6c757d;
        }
        .pizza-card h3 {
            margin: 0 0 10px 0;
            color: #dc3545;
        }
        .price {
            font-size: 18px;
            color: #28a745;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #007bff;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍕 Pizzeria API Integration Test</h1>
        
        <div class="info">
            <strong>🔧 Configuration:</strong><br>
            Pizzeria Server: <code><?= htmlspecialchars($pizzeriaBaseURL) ?></code><br>
            Current Time: <?= date('Y-m-d H:i:s') ?><br>
            <br>
            <strong>📝 Note:</strong> If you get connection errors, update <code>$PIZZERIA_SERVER_IP</code> 
            in this file with the correct IP address of the computer running Pizzeria.
        </div>
        
        <!-- Test 1: Connection Test -->
        <h2>1️⃣ Connection Test</h2>
        <?php
        echo "<p>Testing connection to Pizzeria API...</p>";
        if ($pizzeria->testConnection()) {
            echo '<div class="status success">✅ SUCCESS! Connected to Pizzeria API</div>';
            $connectionSuccess = true;
        } else {
            echo '<div class="status error">❌ FAILED! Cannot connect to Pizzeria API</div>';
            echo "<p>Make sure:</p>";
            echo "<ul>";
            echo "<li>Pizzeria server is running at: <code>$pizzeriaBaseURL</code></li>";
            echo "<li>Both systems are on the same WiFi network</li>";
            echo "<li>Windows Firewall allows Apache (port 80)</li>";
            echo "<li>The IP address in this file is correct</li>";
            echo "</ul>";
            $connectionSuccess = false;
        }
        ?>
        
        <?php if ($connectionSuccess): ?>
            
            <!-- Test 2: Get All Pizzas -->
            <h2>2️⃣ Get All Pizzas</h2>
            <?php
            $response = $pizzeria->getPizzas(['limit' => 10]);
            
            if ($response['success']) {
                $pizzas = $response['data']['pizzas'];
                echo '<div class="status success">✅ Retrieved ' . count($pizzas) . ' pizzas</div>';
                
                echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">';
                foreach ($pizzas as $pizza) {
                    echo '<div class="pizza-card">';
                    echo '<h3>' . htmlspecialchars($pizza['name']) . '</h3>';
                    echo '<p>' . htmlspecialchars($pizza['description']) . '</p>';
                    echo '<p class="price">₱' . number_format($pizza['price'], 2) . '</p>';
                    echo '<p><strong>Category:</strong> ' . htmlspecialchars($pizza['category']) . '</p>';
                    echo '<p><strong>Available:</strong> ' . ($pizza['availability'] ? '✅ Yes' : '❌ No') . '</p>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<div class="status error">❌ Error: ' . htmlspecialchars($response['message'] ?? 'Unknown error') . '</div>';
            }
            ?>
            
            <!-- Test 3: Get Specific Pizza -->
            <h2>3️⃣ Get Specific Pizza (ID: 1)</h2>
            <?php
            $response = $pizzeria->getPizza(1);
            
            if ($response['success']) {
                $pizza = $response['data'];
                echo '<div class="status success">✅ Pizza details retrieved</div>';
                echo '<table>';
                echo '<tr><th>Property</th><th>Value</th></tr>';
                echo '<tr><td>ID</td><td>' . $pizza['id'] . '</td></tr>';
                echo '<tr><td>Name</td><td>' . htmlspecialchars($pizza['name']) . '</td></tr>';
                echo '<tr><td>Price</td><td>₱' . number_format($pizza['price'], 2) . '</td></tr>';
                echo '<tr><td>Category</td><td>' . htmlspecialchars($pizza['category']) . '</td></tr>';
                echo '<tr><td>Description</td><td>' . htmlspecialchars($pizza['description']) . '</td></tr>';
                echo '</table>';
            } else {
                echo '<div class="status error">❌ Error: ' . htmlspecialchars($response['message'] ?? 'Unknown error') . '</div>';
            }
            ?>
            
            <!-- Test 4: Get Pizzas by Category -->
            <h2>4️⃣ Get Classic Pizzas</h2>
            <?php
            $response = $pizzeria->getPizzas(['category' => 'Classic', 'limit' => 5]);
            
            if ($response['success']) {
                $pizzas = $response['data']['pizzas'];
                echo '<div class="status success">✅ Found ' . count($pizzas) . ' Classic pizzas</div>';
                
                if (count($pizzas) > 0) {
                    echo '<table>';
                    echo '<tr><th>Name</th><th>Price</th><th>Category</th><th>Available</th></tr>';
                    foreach ($pizzas as $pizza) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($pizza['name']) . '</td>';
                        echo '<td>₱' . number_format($pizza['price'], 2) . '</td>';
                        echo '<td>' . htmlspecialchars($pizza['category']) . '</td>';
                        echo '<td>' . ($pizza['availability'] ? '✅' : '❌') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<p>No Classic pizzas found.</p>';
                }
            } else {
                echo '<div class="status error">❌ Error: ' . htmlspecialchars($response['message'] ?? 'Unknown error') . '</div>';
            }
            ?>
            
            <!-- Test 5: Get Recent Orders -->
            <h2>5️⃣ Get Recent Orders</h2>
            <?php
            $response = $pizzeria->getOrders(['limit' => 5]);
            
            if ($response['success']) {
                $orders = $response['data']['orders'];
                echo '<div class="status success">✅ Retrieved ' . count($orders) . ' recent orders</div>';
                
                if (count($orders) > 0) {
                    echo '<table>';
                    echo '<tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr>';
                    foreach ($orders as $order) {
                        echo '<tr>';
                        echo '<td>#' . $order['id'] . '</td>';
                        echo '<td>' . htmlspecialchars($order['customer_name']) . '</td>';
                        echo '<td>₱' . number_format($order['total_price'], 2) . '</td>';
                        echo '<td>' . htmlspecialchars($order['status']) . '</td>';
                        echo '<td>' . date('M d, Y H:i', strtotime($order['created_at'])) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<p>No orders found.</p>';
                }
            } else {
                echo '<div class="status error">❌ Error: ' . htmlspecialchars($response['message'] ?? 'Unknown error') . '</div>';
            }
            ?>
            
            <!-- Test 6: Create Test Order (Commented Out) -->
            <h2>6️⃣ Create Order (Example)</h2>
            <div class="info">
                <strong>📌 Creating orders is disabled in this demo.</strong><br>
                To enable, uncomment the code in this file. Example order creation:
                <pre style="background: #282c34; color: #abb2bf; padding: 15px; border-radius: 5px; overflow-x: auto;">
$orderData = [
    'user_id' => 1,
    'items' => [
        ['pizza_id' => 1, 'quantity' => 2, 'price' => 299],
        ['pizza_id' => 3, 'quantity' => 1, 'price' => 329]
    ],
    'delivery_address' => '123 Main Street, City',
    'phone' => '+639171234567',
    'payment_method' => 'cash_on_delivery',
    'notes' => 'Order from Motoshapi system'
];

$result = $pizzeria->createOrder($orderData);
if ($result['success']) {
    echo "Order created! ID: " . $result['data']['order_id'];
}</pre>
            </div>
            
            <?php
            /*
            // UNCOMMENT THIS BLOCK TO TEST ORDER CREATION
            
            $orderData = [
                'user_id' => 1, // Make sure this user exists in Pizzeria DB
                'items' => [
                    ['pizza_id' => 1, 'quantity' => 2, 'price' => 299],
                    ['pizza_id' => 3, 'quantity' => 1, 'price' => 329]
                ],
                'delivery_address' => '123 Main Street, City',
                'phone' => '+639171234567',
                'payment_method' => 'cash_on_delivery',
                'notes' => 'Cross-platform order from Motoshapi'
            ];
            
            $result = $pizzeria->createOrder($orderData);
            
            if ($result['success']) {
                echo '<div class="status success">✅ Order created successfully!</div>';
                echo '<p><strong>Order ID:</strong> ' . $result['data']['order_id'] . '</p>';
                echo '<p><strong>Total:</strong> ₱' . number_format($result['data']['total_price'], 2) . '</p>';
            } else {
                echo '<div class="status error">❌ Error creating order: ' . htmlspecialchars($result['message']) . '</div>';
            }
            */
            ?>
            
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <h2>📚 Additional Resources</h2>
        <div class="info">
            <ul>
                <li><a href="USER_SYNC_EXPLAINED.md" style="color: #007bff;">📖 Complete User Sync Documentation</a></li>
                <li><a href="admin/network_settings.php" style="color: #007bff;">⚙️ Network Settings (Admin)</a></li>
                <li><a href="docs/API_INTEGRATION_GUIDE.md" style="color: #007bff;">📚 Full API Guide</a></li>
            </ul>
        </div>
        
        <div class="info" style="margin-top: 20px;">
            <strong>💡 Important Notes:</strong>
            <ul>
                <li>✅ Only <strong>customer accounts</strong> are synced between systems</li>
                <li>✅ Admin accounts remain <strong>separate</strong> on each system</li>
                <li>❌ Products, orders, and carts are <strong>NOT synced</strong></li>
                <li>🔒 Sync happens automatically when users login or register</li>
                <li>🌐 Both systems must be on the same WiFi network</li>
            </ul>
        </div>
    </div>
</body>
</html>
