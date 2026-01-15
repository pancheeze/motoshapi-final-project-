<?php
/**
 * Motoshapi API Test File
 * Test all API endpoints to ensure they're working correctly
 */

require_once 'config.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoshapi API Tester</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .endpoint {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .endpoint h3 {
            color: #007bff;
            margin-top: 0;
        }
        .method {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            margin-right: 10px;
        }
        .get { background: #28a745; color: white; }
        .post { background: #007bff; color: white; }
        .put { background: #ffc107; color: black; }
        .delete { background: #dc3545; color: white; }
        .test-btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .test-btn:hover {
            background: #0056b3;
        }
        .response {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin-top: 10px;
            border-radius: 4px;
            display: none;
        }
        .success { border-left-color: #28a745; }
        .error { border-left-color: #dc3545; }
        pre {
            background: #282c34;
            color: #abb2bf;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .server-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>🔧 Motoshapi REST API Tester</h1>
    
    <div class="server-info">
        <strong>📡 Server Information:</strong><br>
        <strong>Local IP:</strong> <?= getServerIP() ?><br>
        <strong>API Base URL:</strong> http://<?= $_SERVER['HTTP_HOST'] ?>/motoshapi/api/<br>
        <strong>API Version:</strong> <?= API_VERSION ?><br>
        <strong>Time:</strong> <?= date('Y-m-d H:i:s') ?>
    </div>
    
    <div class="info-box">
        <strong>ℹ️ How to Test:</strong>
        <ul>
            <li>Click "Test" buttons to try each API endpoint</li>
            <li>Responses will appear below each test button</li>
            <li>Use these URLs from other devices on your network: http://<?= getServerIP() ?>/motoshapi/api/</li>
            <li>CORS is enabled - you can call these APIs from any domain</li>
        </ul>
    </div>
    
    <!-- PRODUCTS ENDPOINTS -->
    <div class="endpoint">
        <h3>📦 Products API</h3>
        
        <div>
            <span class="method get">GET</span>
            <strong>/api/products.php</strong> - Get all products
            <button class="test-btn" onclick="testEndpoint('products.php', 'products-list')">Test</button>
            <div id="products-list" class="response"></div>
        </div>
        
        <div style="margin-top: 15px;">
            <span class="method get">GET</span>
            <strong>/api/products.php?id=1</strong> - Get single product
            <button class="test-btn" onclick="testEndpoint('products.php?id=1', 'product-single')">Test</button>
            <div id="product-single" class="response"></div>
        </div>
        
        <div style="margin-top: 15px;">
            <span class="method get">GET</span>
            <strong>/api/products.php?category_id=2</strong> - Filter by category
            <button class="test-btn" onclick="testEndpoint('products.php?category_id=2', 'products-category')">Test</button>
            <div id="products-category" class="response"></div>
        </div>
    </div>
    
    <!-- CATEGORIES ENDPOINTS -->
    <div class="endpoint">
        <h3>📂 Categories API</h3>
        
        <div>
            <span class="method get">GET</span>
            <strong>/api/categories.php</strong> - Get all categories
            <button class="test-btn" onclick="testEndpoint('categories.php', 'categories-list')">Test</button>
            <div id="categories-list" class="response"></div>
        </div>
    </div>
    
    <!-- ORDERS ENDPOINTS -->
    <div class="endpoint">
        <h3>🛒 Orders API</h3>
        
        <div>
            <span class="method get">GET</span>
            <strong>/api/orders.php</strong> - Get all orders
            <button class="test-btn" onclick="testEndpoint('orders.php', 'orders-list')">Test</button>
            <div id="orders-list" class="response"></div>
        </div>
    </div>
    
    <!-- USERS ENDPOINTS -->
    <div class="endpoint">
        <h3>👥 Users API</h3>
        
        <div>
            <span class="method get">GET</span>
            <strong>/api/users.php</strong> - Get all users
            <button class="test-btn" onclick="testEndpoint('users.php', 'users-list')">Test</button>
            <div id="users-list" class="response"></div>
        </div>
    </div>
    
    <script>
        async function testEndpoint(endpoint, responseId) {
            const responseDiv = document.getElementById(responseId);
            responseDiv.style.display = 'block';
            responseDiv.className = 'response';
            responseDiv.innerHTML = '<em>Loading...</em>';
            
            try {
                const response = await fetch('/motoshapi/api/' + endpoint);
                const data = await response.json();
                
                if (data.success) {
                    responseDiv.className = 'response success';
                    responseDiv.innerHTML = '<strong>✅ Success!</strong><pre>' + 
                        JSON.stringify(data, null, 2) + '</pre>';
                } else {
                    responseDiv.className = 'response error';
                    responseDiv.innerHTML = '<strong>❌ Error:</strong> ' + data.message + 
                        '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                }
            } catch (error) {
                responseDiv.className = 'response error';
                responseDiv.innerHTML = '<strong>❌ Request Failed:</strong> ' + error.message;
            }
        }
    </script>
</body>
</html>
