<?php
/**
 * Motoshapi Products API Endpoint
 * GET    /api/products.php          - List all products (with pagination)
 * GET    /api/products.php?id=1     - Get single product
 * POST   /api/products.php          - Create product (admin only)
 * PUT    /api/products.php?id=1     - Update product (admin only)
 * DELETE /api/products.php?id=1     - Delete product (admin only)
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$productId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($productId) {
                // Get single product
                getProduct($conn, $productId);
            } else {
                // Get all products
                getProducts($conn);
            }
            break;
            
        case 'POST':
            createProduct($conn);
            break;
            
        case 'PUT':
            updateProduct($conn, $productId);
            break;
            
        case 'DELETE':
            deleteProduct($conn, $productId);
            break;
            
        default:
            apiError('Method not allowed', 405);
    }
} catch (Exception $e) {
    apiError($e->getMessage(), 500);
}

// Get all products with filters
function getProducts($conn) {
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    $categoryId = $_GET['category_id'] ?? null;
    $featured = $_GET['featured'] ?? null;
    $search = $_GET['search'] ?? null;
    
    $pagination = paginate($page, $limit);
    
    // Build query
    $where = ['is_active = 1'];
    $params = [];
    
    if ($categoryId) {
        $where[] = 'category_id = ?';
        $params[] = $categoryId;
    }
    
    if ($featured !== null) {
        $where[] = 'featured = ?';
        $params[] = $featured ? 1 : 0;
    }
    
    if ($search) {
        $where[] = '(name LIKE ? OR description LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Get products
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE $whereClause 
            ORDER BY p.id DESC 
            LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format image URLs for network access
    foreach ($products as &$product) {
        if ($product['image_url']) {
            $product['image_url'] = getFullURL($product['image_url']);
        }
    }
    
    apiResponse(true, [
        'products' => $products,
        'pagination' => [
            'page' => $pagination['page'],
            'limit' => $pagination['limit'],
            'total' => $total,
            'total_pages' => ceil($total / $pagination['limit'])
        ]
    ], 'Products retrieved successfully');
}

// Get single product
function getProduct($conn, $id) {
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ? AND p.is_active = 1
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        apiError('Product not found', 404);
    }
    
    // Format image URL
    if ($product['image_url']) {
        $product['image_url'] = getFullURL($product['image_url']);
    }
    
    apiResponse(true, $product, 'Product retrieved successfully');
}

// Create product (admin only - basic implementation)
function createProduct($conn) {
    $data = getRequestBody();
    
    $required = ['name', 'price', 'category_id'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            apiError("Missing required field: $field", 400);
        }
    }
    
    $stmt = $conn->prepare("
        INSERT INTO products (name, description, price, category_id, stock_quantity, image_url, featured, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->execute([
        $data['name'],
        $data['description'] ?? '',
        $data['price'],
        $data['category_id'],
        $data['stock_quantity'] ?? 0,
        $data['image_url'] ?? null,
        $data['featured'] ?? 0
    ]);
    
    $productId = $conn->lastInsertId();
    
    apiResponse(true, ['id' => $productId], 'Product created successfully', 201);
}

// Update product
function updateProduct($conn, $id) {
    if (!$id) {
        apiError('Product ID required', 400);
    }
    
    $data = getRequestBody();
    
    $fields = [];
    $params = [];
    
    $allowedFields = ['name', 'description', 'price', 'category_id', 'stock_quantity', 'image_url', 'featured', 'is_active'];
    
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
    
    $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    apiResponse(true, ['updated' => $stmt->rowCount()], 'Product updated successfully');
}

// Delete product (soft delete)
function deleteProduct($conn, $id) {
    if (!$id) {
        apiError('Product ID required', 400);
    }
    
    $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        apiError('Product not found', 404);
    }
    
    apiResponse(true, null, 'Product deleted successfully');
}

// Helper function to create full URLs for images
function getFullURL($path) {
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseDir = dirname(dirname($_SERVER['SCRIPT_NAME']));
    
    return "$protocol://$host$baseDir/$path";
}
?>
