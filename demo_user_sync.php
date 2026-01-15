<?php
/**
 * Example: User Account Synchronization Demo
 * Shows how customer accounts are shared between Motoshapi and Pizzeria
 */

session_start();
require_once 'includes/PizzeriaAPIClient.php';

// Pizzeria server IP (change this to actual IP)
$PIZZERIA_IP = '10.38.247.140'; // Change to 192.168.1.101 if on different computer

$pizzeriaAPI = new PizzeriaAPIClient("http://$PIZZERIA_IP/pizzeria");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Account Sync Demo - Motoshapi</title>
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
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #007bff;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .demo-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        label {
            font-weight: bold;
            color: #333;
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
        <h1>👥 User Account Synchronization Demo</h1>
        
        <div class="info">
            <strong>📌 How It Works:</strong><br>
            Customer accounts created on <strong>Motoshapi</strong> can automatically be used on <strong>Pizzeria</strong>.
            When a user logs in to Pizzeria, the system checks Motoshapi and syncs the account if found.
            <br><br>
            <strong>⚠️ Note:</strong> Admin accounts are NOT shared between systems - only customer accounts.
        </div>
        
        <div class="demo-section">
            <h2>🔄 Demo 1: Sync User from Motoshapi to Pizzeria</h2>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_user') {
                require_once 'config/connect.php';
                
                $email = $_POST['email'] ?? '';
                
                // Get user from Motoshapi database
                $stmt = $conn->prepare("
                    SELECT id, username, email, phone, address 
                    FROM users WHERE email = ?
                ");
                $stmt->execute([$email]);
                $motoshapiUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($motoshapiUser) {
                    // Try to sync to Pizzeria
                    $syncData = [
                        'name' => $motoshapiUser['username'],
                        'email' => $motoshapiUser['email'],
                        'phone' => $motoshapiUser['phone'],
                        'address' => $motoshapiUser['address']
                    ];
                    
                    $result = $pizzeriaAPI->syncUser($syncData);
                    
                    if ($result['success']) {
                        echo '<div class="success">';
                        echo '<strong>✅ User Synced Successfully!</strong><br>';
                        echo 'Email: ' . htmlspecialchars($result['data']['user']['email']) . '<br>';
                        echo 'Action: ' . ucfirst($result['data']['action']) . '<br>';
                        if ($result['data']['action'] === 'created') {
                            echo '<em>New account created in Pizzeria</em>';
                        } else {
                            echo '<em>Account already exists in Pizzeria</em>';
                        }
                        echo '</div>';
                        
                        echo '<h3>Synced User Details:</h3>';
                        echo '<table>';
                        echo '<tr><th>Field</th><th>Value</th></tr>';
                        foreach ($result['data']['user'] as $key => $value) {
                            echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
                        }
                        echo '</table>';
                    } else {
                        echo '<div class="error">';
                        echo '<strong>❌ Sync Failed</strong><br>';
                        echo 'Error: ' . htmlspecialchars($result['message'] ?? 'Unknown error');
                        echo '</div>';
                    }
                } else {
                    echo '<div class="error">';
                    echo '<strong>❌ User Not Found</strong><br>';
                    echo 'No user found with email: ' . htmlspecialchars($email);
                    echo '</div>';
                }
            }
            ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="sync_user">
                
                <label for="email">Enter Motoshapi User Email:</label>
                <input type="email" name="email" id="email" placeholder="user@example.com" required>
                
                <button type="submit" class="btn">🔄 Sync User to Pizzeria</button>
            </form>
            
            <div class="info">
                <strong>💡 Tip:</strong> First register a user on Motoshapi, then use this form to sync them to Pizzeria.
            </div>
        </div>
        
        <div class="demo-section">
            <h2>🔐 Demo 2: Test Login on Pizzeria</h2>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_login') {
                $email = $_POST['login_email'] ?? '';
                $password = $_POST['login_password'] ?? '';
                
                $loginResult = $pizzeriaAPI->login($email, $password);
                
                if ($loginResult['success']) {
                    echo '<div class="success">';
                    echo '<strong>✅ Login Successful on Pizzeria!</strong><br>';
                    echo 'User: ' . htmlspecialchars($loginResult['data']['user']['name']) . '<br>';
                    echo 'Email: ' . htmlspecialchars($loginResult['data']['user']['email']) . '<br>';
                    echo 'System: ' . htmlspecialchars($loginResult['data']['system']);
                    echo '</div>';
                    
                    echo '<h3>User Information:</h3>';
                    echo '<table>';
                    foreach ($loginResult['data']['user'] as $key => $value) {
                        echo '<tr><td><strong>' . htmlspecialchars($key) . '</strong></td><td>' . htmlspecialchars($value) . '</td></tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="error">';
                    echo '<strong>❌ Login Failed</strong><br>';
                    echo 'Error: ' . htmlspecialchars($loginResult['message'] ?? 'Invalid credentials');
                    echo '</div>';
                }
            }
            ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="test_login">
                
                <label for="login_email">Email:</label>
                <input type="email" name="login_email" id="login_email" placeholder="user@example.com" required>
                
                <label for="login_password">Password:</label>
                <input type="password" name="login_password" id="login_password" placeholder="Password" required>
                
                <button type="submit" class="btn">🔐 Test Login on Pizzeria</button>
            </form>
        </div>
        
        <div class="demo-section">
            <h2>📋 Demo 3: View Motoshapi Users</h2>
            
            <?php
            require_once 'config/connect.php';
            
            $stmt = $conn->prepare("
                SELECT id, username, email, full_name, phone, created_at 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($users) {
                echo '<p>Recent users in Motoshapi:</p>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Created</th><th>Action</th></tr>';
                foreach ($users as $user) {
                    echo '<tr>';
                    echo '<td>' . $user['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['full_name']) . '</td>';
                    echo '<td>' . date('M d, Y', strtotime($user['created_at'])) . '</td>';
                    echo '<td>';
                    echo '<form method="POST" style="display:inline; margin:0; padding:0;">';
                    echo '<input type="hidden" name="action" value="sync_user">';
                    echo '<input type="hidden" name="email" value="' . htmlspecialchars($user['email']) . '">';
                    echo '<button type="submit" class="btn" style="padding:5px 10px; margin:0;">Sync</button>';
                    echo '</form>';
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p>No users found. Register some users first!</p>';
            }
            ?>
        </div>
        
        <div class="demo-section">
            <h2>📖 How to Use in Production</h2>
            
            <h3>Step 1: User Registers on Motoshapi</h3>
            <pre><code>// In register.php
$user = createUser($username, $email, $password, $full_name);
// User saved to motoshapi_db.users</code></pre>
            
            <h3>Step 2: User Tries to Login on Pizzeria</h3>
            <pre><code>// In pizzeria/login.php
require_once 'includes/MotoshapiAPIClient.php';

// Try local login first
$localUser = checkLocalDatabase($email, $password);

if (!$localUser) {
    // Try Motoshapi API
    $motoshapi = new MotoshapiAPIClient('http://192.168.1.100/motoshapi');
    $result = $motoshapi->login($email, $password);
    
    if ($result['success']) {
        // Sync user to Pizzeria
        $syncData = [
            'name' => $result['data']['user']['full_name'],
            'email' => $result['data']['user']['email'],
            'phone' => $result['data']['user']['phone'],
            'address' => $result['data']['user']['address'],
            'password' => $password
        ];
        
        syncUserToPizzeria($syncData);
        
        // Now login locally
        $localUser = checkLocalDatabase($email, $password);
    }
}

if ($localUser) {
    $_SESSION['user_id'] = $localUser['id'];
    redirect('index.php');
}</code></pre>
            
            <h3>Step 3: User Can Access Both Sites</h3>
            <ul>
                <li>✅ Same email and password works on both sites</li>
                <li>✅ User information synced automatically</li>
                <li>✅ Order history tracked separately per site</li>
                <li>⚠️ Admin accounts remain separate</li>
            </ul>
        </div>
        
        <hr style="margin: 30px 0;">
        
        <div class="info">
            <strong>🔧 Configuration:</strong><br>
            Pizzeria Server: <code><?= htmlspecialchars("http://$PIZZERIA_IP/pizzeria") ?></code><br>
            <br>
            To test with Pizzeria on different computer:
            <ol>
                <li>Find Pizzeria computer's IP with <code>ipconfig</code></li>
                <li>Update <code>$PIZZERIA_IP</code> at the top of this file</li>
                <li>Restart this page</li>
            </ol>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="/motoshapi/" class="btn">← Back to Motoshapi</a>
            <a href="/motoshapi/test_pizzeria_integration.php" class="btn">Test Integration</a>
            <a href="/motoshapi/docs/MULTI_DEVICE_SETUP.md" class="btn">Setup Guide</a>
        </div>
    </div>
</body>
</html>
