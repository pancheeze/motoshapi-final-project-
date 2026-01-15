<?php
/**
 * Motoshapi Categories API Endpoint
 * GET    /api/categories.php         - List all categories
 * GET    /api/categories.php?id=1    - Get single category
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$categoryId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($categoryId) {
                getCategory($conn, $categoryId);
            } else {
                getCategories($conn);
            }
            break;
            
        default:
            apiError('Method not allowed', 405);
    }
} catch (Exception $e) {
    apiError($e->getMessage(), 500);
}

// Get all categories
function getCategories($conn) {
    $stmt = $conn->prepare("
        SELECT c.*, COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
        GROUP BY c.id
        ORDER BY c.name ASC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    apiResponse(true, $categories, 'Categories retrieved successfully');
}

// Get single category with products
function getCategory($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        apiError('Category not found', 404);
    }
    
    // Get products in this category
    $productsStmt = $conn->prepare("
        SELECT * FROM products 
        WHERE category_id = ? AND is_active = 1 
        ORDER BY name ASC
    ");
    $productsStmt->execute([$id]);
    $category['products'] = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    apiResponse(true, $category, 'Category retrieved successfully');
}
?>
