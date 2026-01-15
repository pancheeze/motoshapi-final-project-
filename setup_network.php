<?php
/**
 * Network Setup Helper
 * Run this on BOTH computers to configure IP addresses automatically
 */

// Get this computer's IP address
function getLocalIP() {
    $output = shell_exec('ipconfig');
    preg_match('/IPv4 Address[^\d]+([\d\.]+)/', $output, $matches);
    return $matches[1] ?? 'Not Found';
}

$myIP = getLocalIP();
$isMotoshapi = file_exists(__DIR__ . '/api/products.php');
$isPizzeria = file_exists(__DIR__ . '/api/pizzas.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Setup - <?php echo $isMotoshapi ? 'Motoshapi' : 'Pizzeria'; ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ip-box {
            background: #f8f9fa;
            border: 3px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            color: #667eea;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
        }
        .step {
            background: #e8f4f8;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .step h3 {
            margin-top: 0;
            color: #1976D2;
        }
        .code-box {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            box-sizing: border-box;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #5568d3;
        }
        .file-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .file-list li {
            padding: 5px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .copy-btn {
            background: #28a745;
            padding: 5px 15px;
            font-size: 12px;
            margin-left: 10px;
        }
        .copy-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>
            🌐 Network Setup - <?php echo $isMotoshapi ? 'Motoshapi' : 'Pizzeria'; ?>
        </h1>

        <div class="success">
            <strong>✅ This Computer's IP Address:</strong>
        </div>

        <div class="ip-box">
            <?php echo $myIP; ?>
            <button class="copy-btn" onclick="copyIP('<?php echo $myIP; ?>')">📋 Copy</button>
        </div>

        <?php if ($isMotoshapi): ?>
        <!-- MOTOSHAPI SETUP -->
        <div class="step">
            <h3>📍 Step 1: Note This IP Address</h3>
            <p>This is <strong>Computer A (Motoshapi)</strong> with IP: <code><?php echo $myIP; ?></code></p>
            <p>Write this down or copy it. You'll need it for Computer B.</p>
        </div>

        <div class="step">
            <h3>💻 Step 2: Setup Computer B (Pizzeria)</h3>
            <p>On the <strong>other computer</strong> that will run Pizzeria:</p>
            <ol>
                <li>Start XAMPP (Apache + MySQL)</li>
                <li>Go to: <code>http://localhost/pizzeria/setup_network.php</code></li>
                <li>Copy that computer's IP address</li>
                <li>Come back here to Step 3</li>
            </ol>
        </div>

        <div class="step">
            <h3>🔗 Step 3: Enter Computer B's IP Address</h3>
            <p>Enter the IP address you got from Computer B (Pizzeria):</p>
            <form method="POST" action="">
                <input type="text" name="pizzeria_ip" placeholder="Example: 192.168.1.90" required 
                       value="<?php echo $_POST['pizzeria_ip'] ?? ''; ?>">
                <button type="submit" name="configure_motoshapi">⚙️ Configure Motoshapi</button>
            </form>
        </div>

        <?php
        if (isset($_POST['configure_motoshapi']) && !empty($_POST['pizzeria_ip'])) {
            $pizzeriaIP = trim($_POST['pizzeria_ip']);
            
            echo '<div class="success">';
            echo '<h3>✅ Configuration Complete!</h3>';
            echo '<p>Updated files to connect to Pizzeria at: <strong>' . htmlspecialchars($pizzeriaIP) . '</strong></p>';
            
            // Files to update
            $filesToUpdate = [
                'test_pizzeria_integration.php' => "\$PIZZERIA_SERVER_IP = '{$pizzeriaIP}';",
                'demo_user_sync.php' => "\$PIZZERIA_IP = '{$pizzeriaIP}';",
            ];
            
            echo '<div class="file-list"><strong>Updated Files:</strong><ul>';
            
            foreach ($filesToUpdate as $file => $searchString) {
                $filePath = __DIR__ . '/' . $file;
                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                    
                    // Update test_pizzeria_integration.php
                    if ($file === 'test_pizzeria_integration.php') {
                        $content = preg_replace(
                            '/\$PIZZERIA_SERVER_IP\s*=\s*[\'"][^\'"]*[\'"];/',
                            "\$PIZZERIA_SERVER_IP = '{$pizzeriaIP}';",
                            $content
                        );
                    }
                    
                    // Update demo_user_sync.php
                    if ($file === 'demo_user_sync.php') {
                        $content = preg_replace(
                            '/\$PIZZERIA_IP\s*=\s*[\'"][^\'"]*[\'"];/',
                            "\$PIZZERIA_IP = '{$pizzeriaIP}';",
                            $content
                        );
                    }
                    
                    file_put_contents($filePath, $content);
                    echo '<li>✅ ' . $file . '</li>';
                } else {
                    echo '<li>⚠️ ' . $file . ' (not found)</li>';
                }
            }
            
            echo '</ul></div>';
            
            echo '<h4>🧪 Next: Test the Connection</h4>';
            echo '<p><a href="test_pizzeria_integration.php" target="_blank" style="color: #667eea; font-weight: bold;">→ Open Test Page</a></p>';
            echo '</div>';
        }
        ?>

        <?php else: ?>
        <!-- PIZZERIA SETUP -->
        <div class="step">
            <h3>📍 Step 1: Note This IP Address</h3>
            <p>This is <strong>Computer B (Pizzeria)</strong> with IP: <code><?php echo $myIP; ?></code></p>
            <p>Copy this IP and go back to Computer A (Motoshapi).</p>
        </div>

        <div class="step">
            <h3>🔙 Step 2: Return to Computer A</h3>
            <p>On Computer A (Motoshapi), go to:</p>
            <div class="code-box">http://localhost/motoshapi/setup_network.php</div>
            <p>Enter <strong>this computer's IP (<?php echo $myIP; ?>)</strong> there.</p>
        </div>

        <div class="step">
            <h3>🔗 Step 3: Enter Computer A's IP Address</h3>
            <p>Enter the IP address from Computer A (Motoshapi):</p>
            <form method="POST" action="">
                <input type="text" name="motoshapi_ip" placeholder="Example: 192.168.1.85" required
                       value="<?php echo $_POST['motoshapi_ip'] ?? ''; ?>">
                <button type="submit" name="configure_pizzeria">⚙️ Configure Pizzeria</button>
            </form>
        </div>

        <?php
        if (isset($_POST['configure_pizzeria']) && !empty($_POST['motoshapi_ip'])) {
            $motoshapiIP = trim($_POST['motoshapi_ip']);
            
            echo '<div class="success">';
            echo '<h3>✅ Configuration Complete!</h3>';
            echo '<p>Updated files to connect to Motoshapi at: <strong>' . htmlspecialchars($motoshapiIP) . '</strong></p>';
            
            // Update test_motoshapi_integration.php
            $testFile = __DIR__ . '/test_motoshapi_integration.php';
            if (file_exists($testFile)) {
                $content = file_get_contents($testFile);
                $content = preg_replace(
                    '/\$MOTOSHAPI_SERVER_IP\s*=\s*[\'"][^\'"]*[\'"];/',
                    "\$MOTOSHAPI_SERVER_IP = '{$motoshapiIP}';",
                    $content
                );
                file_put_contents($testFile, $content);
                
                echo '<div class="file-list"><strong>Updated Files:</strong><ul>';
                echo '<li>✅ test_motoshapi_integration.php</li>';
                echo '</ul></div>';
                
                echo '<h4>🧪 Next: Test the Connection</h4>';
                echo '<p><a href="test_motoshapi_integration.php" target="_blank" style="color: #667eea; font-weight: bold;">→ Open Test Page</a></p>';
            }
            
            echo '</div>';
        }
        ?>
        <?php endif; ?>

        <div class="warning">
            <strong>⚠️ Important:</strong>
            <ul>
                <li>Both computers must be on the <strong>same WiFi network</strong></li>
                <li>Windows Firewall must allow <strong>port 80</strong> (Apache)</li>
                <li>Test connectivity: <code>ping <?php echo $isMotoshapi ? '[Computer B IP]' : '[Computer A IP]'; ?></code></li>
            </ul>
        </div>

        <div class="step">
            <h3>🔥 Firewall Configuration</h3>
            <p>If you can't connect, allow Apache through Windows Firewall:</p>
            <ol>
                <li>Open: <strong>Windows Defender Firewall</strong> → Advanced Settings</li>
                <li>Click: <strong>Inbound Rules</strong> → New Rule</li>
                <li>Select: <strong>Port</strong> → TCP → Specific port: <strong>80</strong></li>
                <li>Action: <strong>Allow the connection</strong></li>
                <li>Apply to: <strong>All profiles</strong></li>
            </ol>
        </div>

        <div class="step">
            <h3>🌐 Access URLs</h3>
            <p>After configuration, you can access from any device on the same WiFi:</p>
            <?php if ($isMotoshapi): ?>
            <div class="code-box">
                Motoshapi (This Computer):<br>
                http://<?php echo $myIP; ?>/motoshapi/<br><br>
                Pizzeria (Computer B):<br>
                http://[Computer B IP]/pizzeria/
            </div>
            <?php else: ?>
            <div class="code-box">
                Pizzeria (This Computer):<br>
                http://<?php echo $myIP; ?>/pizzeria/<br><br>
                Motoshapi (Computer A):<br>
                http://[Computer A IP]/motoshapi/
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function copyIP(ip) {
            navigator.clipboard.writeText(ip).then(() => {
                alert('✅ IP Address copied: ' + ip);
            });
        }
    </script>
</body>
</html>
