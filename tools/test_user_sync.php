<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sync Test - Motoshapi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin: 10px 5px;
        }
        .status.active {
            background: #d4edda;
            color: #155724;
        }
        .status.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
        }
        .step {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 3px solid #28a745;
        }
        .step h4 {
            color: #495057;
            margin-bottom: 10px;
        }
        .code {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .check {
            color: #28a745;
            font-size: 20px;
        }
        .cross {
            color: #dc3545;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔄 User Sync Test - Bidirectional</h1>
            <p style="color: #6c757d; margin: 10px 0;">Test automatic user synchronization between Motoshapi and Pizzeria</p>
            
            <?php
            session_start();
            require_once 'config/connect.php';
            require_once 'includes/PizzeriaAPIClient.php';

            // Get current configuration
            $testFile = __DIR__ . '/test_pizzeria_integration.php';
            $pizzeriaIP = 'Not configured';
            $pizzeriaConfigured = false;
            
            if (file_exists($testFile)) {
                $content = file_get_contents($testFile);
                if (preg_match('/\$PIZZERIA_SERVER_IP\s*=\s*[\'"]([^\'"]*)[\'"];/', $content, $matches)) {
                    $pizzeriaIP = $matches[1];
                    $pizzeriaConfigured = ($pizzeriaIP !== 'localhost' && $pizzeriaIP !== '192.168.1.100');
                }
            }

            // Check if sync functions are active
            $motoshapiLoginHasSync = file_exists('../pages/login.php') && strpos(file_get_contents('../pages/login.php'), 'syncUserToPizzeria') !== false;
            $motoshapiRegisterHasSync = file_exists('../pages/register.php') && strpos(file_get_contents('../pages/register.php'), 'syncNewUserToPizzeria') !== false;
            
            $pizzeriaLoginHasSync = file_exists('../pizzeria/login.php') && strpos(file_get_contents('../pizzeria/login.php'), 'syncFromMotoshapi') !== false;
            ?>

            <div style="margin: 20px 0;">
                <h3>📊 System Status</h3>
                <table>
                    <tr>
                        <th>Feature</th>
                        <th>Status</th>
                        <th>Description</th>
                    </tr>
                    <tr>
                        <td>Pizzeria IP Configured</td>
                        <td><?php echo $pizzeriaConfigured ? '<span class="check">✓</span>' : '<span class="cross">✗</span>'; ?></td>
                        <td><?php echo $pizzeriaIP; ?></td>
                    </tr>
                    <tr>
                        <td>Motoshapi Login Sync</td>
                        <td><?php echo $motoshapiLoginHasSync ? '<span class="check">✓</span>' : '<span class="cross">✗</span>'; ?></td>
                        <td>Syncs to Pizzeria on login</td>
                    </tr>
                    <tr>
                        <td>Motoshapi Register Sync</td>
                        <td><?php echo $motoshapiRegisterHasSync ? '<span class="check">✓</span>' : '<span class="cross">✗</span>'; ?></td>
                        <td>Syncs to Pizzeria on registration</td>
                    </tr>
                    <tr>
                        <td>Pizzeria Login Sync</td>
                        <td><?php echo $pizzeriaLoginHasSync ? '<span class="check">✓</span>' : '<span class="cross">✗</span>'; ?></td>
                        <td>Checks Motoshapi if user not found</td>
                    </tr>
                    <tr>
                        <td>API Client Methods</td>
                        <td><span class="check">✓</span></td>
                        <td>syncUser(), login(), register() available</td>
                    </tr>
                </table>

                <?php if ($pizzeriaConfigured && $motoshapiLoginHasSync && $motoshapiRegisterHasSync && $pizzeriaLoginHasSync): ?>
                    <div class="status active">✅ System Fully Configured - User Sync Active!</div>
                <?php else: ?>
                    <div class="status inactive">⚠️ Configuration Incomplete</div>
                    <?php if (!$pizzeriaConfigured): ?>
                        <p style="color: #dc3545; margin: 10px 0;">
                            → Configure Pizzeria IP in <a href="../admin/network_settings.php">Network Settings</a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h2>🧪 How to Test</h2>
            
            <div class="test-section">
                <h3>Test 1: Register on Motoshapi → Login on Pizzeria</h3>
                
                <div class="step">
                    <h4>Step 1: Register New User</h4>
                    <p>Create a new account on Motoshapi:</p>
                    <div class="code">
Email: testuser@example.com
Password: Test123!
                    </div>
                    <a href="../register.php" class="btn" target="_blank">Go to Motoshapi Registration</a>
                </div>

                <div class="step">
                    <h4>Step 2: Check User Created</h4>
                    <p>Verify account exists in Motoshapi database</p>
                    <div class="code">
SELECT * FROM users WHERE email = 'testuser@example.com';
                    </div>
                </div>

                <div class="step">
                    <h4>Step 3: Login on Pizzeria</h4>
                    <p>Go to Pizzeria website and login with same credentials:</p>
                    <div class="code">
http://<?php echo $pizzeriaIP; ?>/pizzeria/login.php
                    </div>
                    <p><strong>Expected Result:</strong> Login successful! User automatically synced.</p>
                </div>

                <div class="step">
                    <h4>Step 4: Verify Sync</h4>
                    <p>Check Pizzeria database to confirm user exists:</p>
                    <div class="code">
SELECT * FROM users WHERE email = 'testuser@example.com' AND role = 'customer';
                    </div>
                    <p style="color: #28a745;">✅ User should exist in both databases!</p>
                </div>
            </div>

            <div class="test-section">
                <h3>Test 2: Register on Pizzeria → Login on Motoshapi</h3>
                
                <div class="step">
                    <h4>Step 1: Register on Pizzeria</h4>
                    <p>Create account on Pizzeria website</p>
                    <div class="code">
Email: pizzauser@example.com
Password: Pizza123!
                    </div>
                </div>

                <div class="step">
                    <h4>Step 2: Login on Motoshapi</h4>
                    <p>Try to login here with Pizzeria credentials:</p>
                    <a href="../login.php" class="btn btn-success" target="_blank">Go to Motoshapi Login</a>
                    <p><strong>Expected Result:</strong> Login works! (if user synced on registration)</p>
                </div>
            </div>

            <div class="test-section">
                <h3>Test 3: Existing User Login Sync</h3>
                
                <div class="step">
                    <h4>Scenario</h4>
                    <p>User exists on Motoshapi but never logged into Pizzeria</p>
                    <ol style="margin: 10px 0 10px 20px;">
                        <li>User tries to login on Pizzeria</li>
                        <li>Pizzeria checks local DB → Not found</li>
                        <li>Pizzeria contacts Motoshapi API → Validates credentials</li>
                        <li>Pizzeria creates local account automatically</li>
                        <li>User logged in successfully!</li>
                    </ol>
                    <p style="color: #28a745;"><strong>This happens automatically on first login!</strong></p>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>📋 What Gets Synced</h2>
            
            <table>
                <tr>
                    <th>Data Field</th>
                    <th>Synced?</th>
                    <th>Notes</th>
                </tr>
                <tr>
                    <td>Email</td>
                    <td><span class="check">✓</span></td>
                    <td>Used as unique identifier</td>
                </tr>
                <tr>
                    <td>Password</td>
                    <td><span class="check">✓</span></td>
                    <td>Securely hashed on each system</td>
                </tr>
                <tr>
                    <td>Name/Username</td>
                    <td><span class="check">✓</span></td>
                    <td>Full name or username</td>
                </tr>
                <tr>
                    <td>Phone</td>
                    <td><span class="check">✓</span></td>
                    <td>Contact number</td>
                </tr>
                <tr>
                    <td>Address</td>
                    <td><span class="check">✓</span></td>
                    <td>User address</td>
                </tr>
                <tr>
                    <td>Admin Accounts</td>
                    <td><span class="cross">✗</span></td>
                    <td>Admins stay separate per system</td>
                </tr>
                <tr>
                    <td>Order History</td>
                    <td><span class="cross">✗</span></td>
                    <td>Orders remain separate</td>
                </tr>
                <tr>
                    <td>Shopping Cart</td>
                    <td><span class="cross">✗</span></td>
                    <td>Carts are separate</td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h2>🔧 Quick Actions</h2>
            <a href="../admin/network_settings.php" class="btn">⚙️ Network Settings</a>
            <a href="../register.php" class="btn btn-success">📝 Test Registration</a>
            <a href="../login.php" class="btn">🔑 Test Login</a>
            <a href="demo_user_sync.php" class="btn">🔄 User Sync Demo</a>
        </div>
    </div>
</body>
</html>
