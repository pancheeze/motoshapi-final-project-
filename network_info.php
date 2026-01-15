<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Integration - Network Info</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 900px;
            width: 100%;
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .info-card h3 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-card .value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            word-break: break-all;
        }
        .info-card .copy-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
            transition: all 0.3s;
        }
        .info-card .copy-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        .api-links {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
        }
        .api-links h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .link-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .link-item a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            word-break: break-all;
        }
        .link-item a:hover {
            text-decoration: underline;
        }
        .link-item button {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            white-space: nowrap;
            margin-left: 10px;
        }
        .link-item button:hover {
            background: #5568d3;
        }
        .instructions {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .instructions h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        .instructions ol {
            margin-left: 20px;
            color: #856404;
        }
        .instructions li {
            margin: 10px 0;
        }
        .success-msg {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            display: none;
        }
        code {
            background: #f4f4f4;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 Network Information</h1>
        <p class="subtitle">API Integration for Motoshapi & Pizzeria</p>
        
        <div class="info-grid">
            <div class="info-card">
                <h3>🖥️ Server IP Address</h3>
                <div class="value" id="serverIP"><?php echo $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()); ?></div>
                <button class="copy-btn" onclick="copyToClipboard('<?php echo $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()); ?>', this)">📋 Copy IP</button>
            </div>
            
            <div class="info-card">
                <h3>🌐 Server Name</h3>
                <div class="value"><?php echo $_SERVER['SERVER_NAME'] ?? 'localhost'; ?></div>
            </div>
            
            <div class="info-card">
                <h3>🔌 Server Port</h3>
                <div class="value"><?php echo $_SERVER['SERVER_PORT'] ?? '80'; ?></div>
            </div>
            
            <div class="info-card">
                <h3>📍 Your IP (Client)</h3>
                <div class="value"><?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?></div>
            </div>
        </div>
        
        <div class="instructions">
            <h3>📝 How to Use from Another Device:</h3>
            <ol>
                <li>Copy the <strong>Server IP Address</strong> above</li>
                <li>On another device (phone, tablet, laptop) connected to the <strong>same WiFi</strong></li>
                <li>Open a browser and replace <code>localhost</code> with the IP address</li>
                <li>Example: <code>http://<?php echo $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()); ?>/motoshapi/api/products.php</code></li>
            </ol>
        </div>
        
        <div class="api-links">
            <h2>🔗 Quick Access Links</h2>
            
            <?php
            $serverIP = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            ?>
            
            <h3 style="color: #007bff; margin: 20px 0 10px 0;">Motoshapi API</h3>
            
            <div class="link-item">
                <a href="http://<?= $serverIP ?>/motoshapi/" target="_blank">
                    Motoshapi Website
                </a>
                <button onclick="copyToClipboard('http://<?= $serverIP ?>/motoshapi/', this)">Copy</button>
            </div>
            
            <div class="link-item">
                <a href="http://<?= $serverIP ?>/motoshapi/api/test_api.php" target="_blank">
                    API Test Dashboard
                </a>
                <button onclick="copyToClipboard('http://<?= $serverIP ?>/motoshapi/api/test_api.php', this)">Copy</button>
            </div>
            
            <div class="link-item">
                <a href="http://<?= $serverIP ?>/motoshapi/api/products.php" target="_blank">
                    Products API
                </a>
                <button onclick="copyToClipboard('http://<?= $serverIP ?>/motoshapi/api/products.php', this)">Copy</button>
            </div>
            
            <div class="link-item">
                <a href="http://<?= $serverIP ?>/motoshapi/test_pizzeria_integration.php" target="_blank">
                    Pizzeria Integration Test
                </a>
                <button onclick="copyToClipboard('http://<?= $serverIP ?>/motoshapi/test_pizzeria_integration.php', this)">Copy</button>
            </div>
            
            <h3 style="color: #dc3545; margin: 30px 0 10px 0;">Pizzeria API</h3>
            
            <div class="link-item">
                <a href="http://<?= $serverIP ?>/pizzeria/" target="_blank">
                    Pizzeria Website
                </a>
                <button onclick="copyToClipboard('http://<?= $serverIP ?>/pizzeria/', this)">Copy</button>
            </div>
            
            <div class="link-item">
                <a href="http://<?= $serverIP ?>/pizzeria/api/test_api.php" target="_blank">
                    API Test Dashboard
                </a>
                <button onclick="copyToClipboard('http://<?= $serverIP ?>/pizzeria/api/test_api.php', this)">Copy</button>
            </div>
            
            <div class="link-item">
                <a href="http://<?= $serverIP ?>/pizzeria/api/pizzas.php" target="_blank">
                    Pizzas API
                </a>
                <button onclick="copyToClipboard('http://<?= $serverIP ?>/pizzeria/api/pizzas.php', this)">Copy</button>
            </div>
            
            <div class="link-item">
                <a href="http://<?= $serverIP ?>/pizzeria/test_motoshapi_integration.php" target="_blank">
                    Motoshapi Integration Test
                </a>
                <button onclick="copyToClipboard('http://<?= $serverIP ?>/pizzeria/test_motoshapi_integration.php', this)">Copy</button>
            </div>
        </div>
        
        <div class="success-msg" id="successMsg">
            ✅ Copied to clipboard!
        </div>
        
        <div class="instructions">
            <h3>🔥 Quick Test Commands</h3>
            <p><strong>Windows PowerShell:</strong></p>
            <code>curl http://<?= $serverIP ?>/motoshapi/api/products.php</code>
            <br><br>
            <p><strong>Check if port 80 is open:</strong></p>
            <code>Test-NetConnection -ComputerName <?= $serverIP ?> -Port 80</code>
        </div>
    </div>
    
    <script>
        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.textContent;
                button.textContent = '✅ Copied!';
                button.style.background = 'rgba(40, 167, 69, 0.3)';
                
                const successMsg = document.getElementById('successMsg');
                successMsg.style.display = 'block';
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '';
                    successMsg.style.display = 'none';
                }, 2000);
            });
        }
    </script>
</body>
</html>
