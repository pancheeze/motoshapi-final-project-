<?php
/**
 * Motoshapi Authentication API
 * Handles user login, registration, and session management
 * POST /api/auth.php?action=login      - User login
 * POST /api/auth.php?action=register   - User registration
 * POST /api/auth.php?action=validate   - Validate session/token
 * GET  /api/auth.php?action=sync       - Sync user from other system
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    switch ($action) {
        case 'login':
            if ($method !== 'POST') {
                apiError('Method not allowed', 405);
            }
            handleLogin($conn);
            break;
            
        case 'register':
            if ($method !== 'POST') {
                apiError('Method not allowed', 405);
            }
            handleRegister($conn);
            break;
            
        case 'validate':
            if ($method !== 'POST') {
                apiError('Method not allowed', 405);
            }
            handleValidate($conn);
            break;
            
        case 'sync':
            if ($method !== 'POST') {
                apiError('Method not allowed', 405);
            }
            handleSync($conn);
            break;
            
        default:
            apiError('Invalid action. Use: login, register, validate, or sync', 400);
    }
} catch (Exception $e) {
    apiError($e->getMessage(), 500);
}

// Handle user login
function handleLogin($conn) {
    $data = getRequestBody();
    
    if (!isset($data['email']) || !isset($data['password'])) {
        apiError('Email and password are required', 400);
    }
    
    // Check user credentials
    $stmt = $conn->prepare("
        SELECT id, username, email, full_name, phone, address, created_at 
        FROM users 
        WHERE email = ? AND password = ?
    ");
    $stmt->execute([$data['email'], $data['password']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        apiError('Invalid email or password', 401);
    }
    
    // Generate session token (simple implementation)
    $token = bin2hex(random_bytes(32));
    
    // Store token in database (you should create a sessions table for production)
    // For now, just return user data with token
    
    apiResponse(true, [
        'user' => $user,
        'token' => $token,
        'system' => 'motoshapi'
    ], 'Login successful', 200);
}

// Handle user registration
function handleRegister($conn) {
    $data = getRequestBody();
    
    $required = ['username', 'email', 'password', 'full_name'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            apiError("Missing required field: $field", 400);
        }
    }
    
    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$data['email']]);
    if ($checkStmt->fetch()) {
        apiError('Email already registered', 409);
    }
    
    // Check if username already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute([$data['username']]);
    if ($checkStmt->fetch()) {
        apiError('Username already taken', 409);
    }
    
    // Insert new user
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password, full_name, phone, address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    // Hash the password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $stmt->execute([
        $data['username'],
        $data['email'],
        $hashedPassword,
        $data['full_name'],
        $data['phone'] ?? null,
        $data['address'] ?? null
    ]);
    
    $userId = $conn->lastInsertId();
    
    // Get the created user
    $userStmt = $conn->prepare("
        SELECT id, username, email, full_name, phone, address, created_at 
        FROM users WHERE id = ?
    ");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    apiResponse(true, [
        'user' => $user,
        'system' => 'motoshapi'
    ], 'Registration successful', 201);
}

// Validate user session/token
function handleValidate($conn) {
    $data = getRequestBody();
    
    if (!isset($data['user_id'])) {
        apiError('User ID required', 400);
    }
    
    $stmt = $conn->prepare("
        SELECT id, username, email, full_name, phone, address, created_at 
        FROM users WHERE id = ?
    ");
    $stmt->execute([$data['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        apiError('User not found', 404);
    }
    
    apiResponse(true, [
        'user' => $user,
        'valid' => true
    ], 'User validated', 200);
}

// Sync user from another system (Pizzeria)
function handleSync($conn) {
    $data = getRequestBody();
    
    $required = ['username', 'email', 'full_name'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            apiError("Missing required field: $field", 400);
        }
    }
    
    // Check if user already exists by email
    $checkStmt = $conn->prepare("SELECT id, username, email, full_name FROM users WHERE email = ?");
    $checkStmt->execute([$data['email']]);
    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        // User exists, return existing user
        apiResponse(true, [
            'user' => $existingUser,
            'synced' => true,
            'action' => 'existing'
        ], 'User already exists in Motoshapi', 200);
    } else {
        // Create new user
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, full_name, phone, address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        // Generate a sync password (users should be prompted to set their own)
        $syncPassword = $data['password'] ?? bin2hex(random_bytes(8));
        
        // Hash the password
        $hashedPassword = password_hash($syncPassword, PASSWORD_DEFAULT);
        
        $stmt->execute([
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['full_name'],
            $data['phone'] ?? null,
            $data['address'] ?? null
        ]);
        
        $userId = $conn->lastInsertId();
        
        // Get the created user
        $userStmt = $conn->prepare("
            SELECT id, username, email, full_name, phone, address, created_at 
            FROM users WHERE id = ?
        ");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        apiResponse(true, [
            'user' => $user,
            'synced' => true,
            'action' => 'created'
        ], 'User synced to Motoshapi', 201);
    }
}
?>
