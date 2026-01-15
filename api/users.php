<?php
/**
 * Motoshapi Users API Endpoint
 * GET    /api/users.php              - List users
 * GET    /api/users.php?id=1         - Get single user
 * POST   /api/users.php              - Create user
 * PUT    /api/users.php?id=1         - Update user
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($userId) {
                getUser($conn, $userId);
            } else {
                getUsers($conn);
            }
            break;
            
        case 'POST':
            createUser($conn);
            break;
            
        case 'PUT':
            updateUser($conn, $userId);
            break;
            
        default:
            apiError('Method not allowed', 405);
    }
} catch (Exception $e) {
    apiError($e->getMessage(), 500);
}

// Get all users
function getUsers($conn) {
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    $search = $_GET['search'] ?? null;
    
    $pagination = paginate($page, $limit);
    
    $where = ['1=1'];
    $params = [];
    
    if ($search) {
        $where[] = '(username LIKE ? OR email LIKE ? OR full_name LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Get users (exclude password)
    $sql = "SELECT id, username, email, full_name, phone, address, created_at 
            FROM users 
            WHERE $whereClause 
            ORDER BY id DESC 
            LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    apiResponse(true, [
        'users' => $users,
        'pagination' => [
            'page' => $pagination['page'],
            'limit' => $pagination['limit'],
            'total' => $total,
            'total_pages' => ceil($total / $pagination['limit'])
        ]
    ], 'Users retrieved successfully');
}

// Get single user
function getUser($conn, $id) {
    $stmt = $conn->prepare("
        SELECT id, username, email, full_name, phone, address, created_at 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        apiError('User not found', 404);
    }
    
    // Get user's order count
    $orderStmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
    $orderStmt->execute([$id]);
    $user['order_count'] = $orderStmt->fetch()['order_count'];
    
    apiResponse(true, $user, 'User retrieved successfully');
}

// Create user
function createUser($conn) {
    $data = getRequestBody();
    
    $required = ['username', 'email', 'password'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            apiError("Missing required field: $field", 400);
        }
    }
    
    // Check if username or email exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $checkStmt->execute([$data['username'], $data['email']]);
    if ($checkStmt->fetch()) {
        apiError('Username or email already exists', 409);
    }
    
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password, full_name, phone, address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['username'],
        $data['email'],
        $data['password'], // In production, use password_hash()
        $data['full_name'] ?? null,
        $data['phone'] ?? null,
        $data['address'] ?? null
    ]);
    
    $userId = $conn->lastInsertId();
    
    apiResponse(true, ['id' => $userId], 'User created successfully', 201);
}

// Update user
function updateUser($conn, $id) {
    if (!$id) {
        apiError('User ID required', 400);
    }
    
    $data = getRequestBody();
    
    $fields = [];
    $params = [];
    
    $allowedFields = ['username', 'email', 'full_name', 'phone', 'address'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        apiError('No fields to update', 400);
    }
    
    $params[] = $id;
    
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() === 0) {
        apiError('User not found', 404);
    }
    
    apiResponse(true, ['updated' => $stmt->rowCount()], 'User updated successfully');
}
?>
