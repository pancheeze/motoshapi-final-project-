<?php
/**
 * Motoshapi REST API Configuration
 * Handles CORS, authentication, and API settings for local network access
 */

// Allow cross-origin requests from local network
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
require_once dirname(__DIR__) . '/config/connect.php';

// API Configuration
define('API_VERSION', 'v1');
define('API_KEY_REQUIRED', false); // Set to true for production

// Get local network IP
function getServerIP() {
    $hostname = gethostname();
    $localIP = gethostbyname($hostname);
    return $localIP;
}

// API Authentication (Simple API Key - enhance for production)
function validateAPIKey() {
    if (!API_KEY_REQUIRED) {
        return true;
    }
    
    $headers = getallheaders();
    $apiKey = $headers['X-API-Key'] ?? $_GET['api_key'] ?? null;
    
    // For demo purposes - replace with database lookup in production
    $validKeys = ['motoshapi_dev_key_123', 'pizzeria_integration_key'];
    
    return in_array($apiKey, $validKeys);
}

// Standard API Response Format
function apiResponse($success, $data = null, $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s'),
        'api_version' => API_VERSION
    ], JSON_PRETTY_PRINT);
    exit();
}

// Error Handler
function apiError($message, $statusCode = 400) {
    apiResponse(false, null, $message, $statusCode);
}

// Get request body as JSON
function getRequestBody() {
    $json = file_get_contents('php://input');
    return json_decode($json, true);
}

// Pagination helper
function paginate($page = 1, $limit = 20) {
    $page = max(1, intval($page));
    $limit = min(100, max(1, intval($limit)));
    $offset = ($page - 1) * $limit;
    
    return [
        'limit' => $limit,
        'offset' => $offset,
        'page' => $page
    ];
}

// Log API requests (optional)
function logAPIRequest($endpoint, $method, $data = []) {
    $logFile = dirname(__DIR__) . '/logs/api_log.txt';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $logEntry = sprintf(
        "[%s] %s %s | IP: %s | Data: %s\n",
        date('Y-m-d H:i:s'),
        $method,
        $endpoint,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        json_encode($data)
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
?>
