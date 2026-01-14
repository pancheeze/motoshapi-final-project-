<?php
/**
 * Test Connection and Path Verification
 * This file tests database connectivity and file paths
 */

session_start();
echo "<h2>Connection and Path Test</h2>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

// Test 1: Database Connection
echo "<h3>1. Database Connection Test</h3>";
try {
    require_once 'config/database.php';
    echo "<p class='success'>✓ Database connection successful!</p>";
    echo "<p class='info'>Database: " . DB_NAME . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 2: Currency Config
echo "<h3>2. Currency Config Test</h3>";
try {
    require_once 'config/currency.php';
    echo "<p class='success'>✓ Currency config loaded successfully!</p>";
    if (defined('CURRENCY_SYMBOL')) {
        echo "<p class='info'>Currency Symbol: " . CURRENCY_SYMBOL . "</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Currency config failed: " . $e->getMessage() . "</p>";
}

// Test 3: Key Files Exist
echo "<h3>3. Key Files Existence Test</h3>";
$keyFiles = [
    'includes/header.php',
    'includes/footer.php',
    'index.php',
    'products.php',
    'cart.php',
    'profile.php',
    'orders.php',
    'login.php',
    'register.php',
    'forgot_password.php',
    'reset_password.php'
];

foreach ($keyFiles as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✓ $file exists</p>";
    } else {
        echo "<p class='error'>✗ $file NOT FOUND</p>";
    }
}

// Test 4: Asset Files
echo "<h3>4. Asset Files Test</h3>";
$assetFiles = [
    'assets/css/style.css',
    'assets/css/modern-theme.css',
    'assets/css/spare-parts-theme.css',
    'assets/js/dark-mode.js',
    'assets/js/chatbot-widget.js',
    'uploads/logo/motologo.svg'
];

foreach ($assetFiles as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✓ $file exists</p>";
    } else {
        echo "<p class='error'>✗ $file NOT FOUND</p>";
    }
}

// Test 5: Database Tables
echo "<h3>5. Database Tables Test</h3>";
if (isset($conn)) {
    $requiredTables = [
        'users',
        'products',
        'categories',
        'orders',
        'order_items',
        'cart',
        'variations',
        'about_us',
        'admins'
    ];
    
    foreach ($requiredTables as $table) {
        try {
            // Validate table name to prevent SQL injection
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                echo "<p class='error'>✗ Invalid table name '$table'</p>";
                continue;
            }
            $stmt = $conn->query("SELECT 1 FROM `$table` LIMIT 1");
            echo "<p class='success'>✓ Table '$table' exists</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Table '$table' NOT FOUND or inaccessible</p>";
        }
    }
}

// Test 6: Navigation Links Test
echo "<h3>6. Navigation Test</h3>";
$navLinks = [
    'Home' => 'index.php',
    'Products' => 'products.php',
    'Cart' => 'cart.php',
    'Orders' => 'orders.php',
    'Profile' => 'profile.php',
    'Login' => 'login.php',
    'Register' => 'register.php',
    'Admin' => 'admin/login.php'
];

foreach ($navLinks as $name => $link) {
    if (file_exists($link)) {
        echo "<p class='success'>✓ $name ($link) is accessible</p>";
    } else {
        echo "<p class='error'>✗ $name ($link) NOT FOUND</p>";
    }
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p class='info'>If all tests show ✓, your website should work properly.</p>";
echo "<p class='info'>If any test shows ✗, please check the corresponding file or configuration.</p>";
echo "<br><a href='index.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Homepage</a>";
?>
