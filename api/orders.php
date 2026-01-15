<?php
/**
 * Motoshapi Orders API Endpoint
 * GET    /api/orders.php             - List orders
 * GET    /api/orders.php?id=1        - Get single order with items
 * POST   /api/orders.php             - Create order
 * PUT    /api/orders.php?id=1        - Update order status
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$orderId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($orderId) {
                getOrder($conn, $orderId);
            } else {
                getOrders($conn);
            }
            break;
            
        case 'POST':
            createOrder($conn);
            break;
            
        case 'PUT':
            updateOrder($conn, $orderId);
            break;
            
        default:
            apiError('Method not allowed', 405);
    }
} catch (Exception $e) {
    apiError($e->getMessage(), 500);
}

// Get all orders
function getOrders($conn) {
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    $userId = $_GET['user_id'] ?? null;
    $status = $_GET['status'] ?? null;
    
    $pagination = paginate($page, $limit);
    
    $where = ['1=1'];
    $params = [];
    
    if ($userId) {
        $where[] = 'o.user_id = ?';
        $params[] = $userId;
    }
    
    if ($status) {
        $where[] = 'o.status = ?';
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM orders o WHERE $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Get orders
    $sql = "SELECT o.*, u.username, u.email, u.phone,
                   pm.name as payment_method
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN payment_modes pm ON o.payment_mode_id = pm.id
            WHERE $whereClause
            ORDER BY o.created_at DESC
            LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    apiResponse(true, [
        'orders' => $orders,
        'pagination' => [
            'page' => $pagination['page'],
            'limit' => $pagination['limit'],
            'total' => $total,
            'total_pages' => ceil($total / $pagination['limit'])
        ]
    ], 'Orders retrieved successfully');
}

// Get single order with items
function getOrder($conn, $id) {
    $stmt = $conn->prepare("
        SELECT o.*, u.username, u.email, u.phone,
               pm.name as payment_method
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN payment_modes pm ON o.payment_mode_id = pm.id
        WHERE o.id = ?
    ");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        apiError('Order not found', 404);
    }
    
    // Get order items
    $itemsStmt = $conn->prepare("
        SELECT oi.*, p.name as product_name, p.image_url
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $itemsStmt->execute([$id]);
    $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    apiResponse(true, $order, 'Order retrieved successfully');
}

// Create order
function createOrder($conn) {
    $data = getRequestBody();
    
    $required = ['user_id', 'items', 'payment_mode_id', 'shipping_address'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            apiError("Missing required field: $field", 400);
        }
    }
    
    if (empty($data['items'])) {
        apiError('Order must contain at least one item', 400);
    }
    
    $conn->beginTransaction();
    
    try {
        // Calculate total
        $total = 0;
        foreach ($data['items'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        // Insert order
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, total_amount, status, payment_mode_id, 
                              shipping_address, phone, notes)
            VALUES (?, ?, 'pending', ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['user_id'],
            $total,
            $data['payment_mode_id'],
            $data['shipping_address'],
            $data['phone'] ?? null,
            $data['notes'] ?? null
        ]);
        
        $orderId = $conn->lastInsertId();
        
        // Insert order items
        $itemStmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($data['items'] as $item) {
            $itemStmt->execute([
                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ]);
        }
        
        $conn->commit();
        
        apiResponse(true, [
            'order_id' => $orderId,
            'total_amount' => $total
        ], 'Order created successfully', 201);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

// Update order status
function updateOrder($conn, $id) {
    if (!$id) {
        apiError('Order ID required', 400);
    }
    
    $data = getRequestBody();
    
    if (!isset($data['status'])) {
        apiError('Status field required', 400);
    }
    
    $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($data['status'], $validStatuses)) {
        apiError('Invalid status value', 400);
    }
    
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$data['status'], $id]);
    
    if ($stmt->rowCount() === 0) {
        apiError('Order not found', 404);
    }
    
    apiResponse(true, ['status' => $data['status']], 'Order updated successfully');
}
?>
