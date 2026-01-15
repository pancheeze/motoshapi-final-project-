<?php
/**
 * Pizzeria API Client for Motoshapi
 * Helper class to consume Pizzeria REST API endpoints from Motoshapi system
 */

class PizzeriaAPIClient {
    private $baseURL;
    private $apiKey;
    private $timeout = 30;
    
    /**
     * Constructor
     * @param string $baseURL Base URL of Pizzeria API (e.g., http://192.168.1.100/pizzeria)
     * @param string $apiKey Optional API key for authentication
     */
    public function __construct($baseURL, $apiKey = null) {
        $this->baseURL = rtrim($baseURL, '/');
        $this->apiKey = $apiKey;
    }
    
    /**
     * Make HTTP request to Pizzeria API
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseURL . '/api/' . ltrim($endpoint, '/');
        
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($this->apiKey) {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false // For local development
        ]);
        
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
                
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
                
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'Connection error: ' . $error,
                'http_code' => $httpCode
            ];
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'http_code' => $httpCode,
                'raw_response' => $response
            ];
        }
        
        $result['http_code'] = $httpCode;
        return $result;
    }
    
    // ===== PIZZAS ENDPOINTS =====
    
    /**
     * Get all pizzas
     * @param array $params Query parameters (page, limit, category, available, search)
     */
    public function getPizzas($params = []) {
        $query = http_build_query($params);
        $endpoint = 'pizzas.php' . ($query ? '?' . $query : '');
        return $this->request($endpoint);
    }
    
    /**
     * Get single pizza by ID
     */
    public function getPizza($id) {
        return $this->request("pizzas.php?id=$id");
    }
    
    /**
     * Create new pizza
     */
    public function createPizza($data) {
        return $this->request('pizzas.php', 'POST', $data);
    }
    
    /**
     * Update pizza
     */
    public function updatePizza($id, $data) {
        return $this->request("pizzas.php?id=$id", 'PUT', $data);
    }
    
    /**
     * Delete pizza
     */
    public function deletePizza($id) {
        return $this->request("pizzas.php?id=$id", 'DELETE');
    }
    
    // ===== ORDERS ENDPOINTS =====
    
    /**
     * Get all orders
     * @param array $params Query parameters (page, limit, user_id, status)
     */
    public function getOrders($params = []) {
        $query = http_build_query($params);
        $endpoint = 'orders.php' . ($query ? '?' . $query : '');
        return $this->request($endpoint);
    }
    
    /**
     * Get single order by ID
     */
    public function getOrder($id) {
        return $this->request("orders.php?id=$id");
    }
    
    /**
     * Create new order
     */
    public function createOrder($data) {
        return $this->request('orders.php', 'POST', $data);
    }
    
    /**
     * Update order status
     */
    public function updateOrderStatus($id, $status) {
        return $this->request("orders.php?id=$id", 'PUT', ['status' => $status]);
    }
    
    // ===== USERS ENDPOINTS =====
    
    /**
     * Get all users
     * @param array $params Query parameters (page, limit, search, role)
     */
    public function getUsers($params = []) {
        $query = http_build_query($params);
        $endpoint = 'users.php' . ($query ? '?' . $query : '');
        return $this->request($endpoint);
    }
    
    /**
     * Get single user by ID
     */
    public function getUser($id) {
        return $this->request("users.php?id=$id");
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        return $this->request('users.php', 'POST', $data);
    }
    
    /**
     * Update user
     */
    public function updateUser($id, $data) {
        return $this->request("users.php?id=$id", 'PUT', $data);
    }
    
    // ===== AUTHENTICATION ENDPOINTS =====
    
    /**
     * Login user
     * @param string $email User email
     * @param string $password User password
     */
    public function login($email, $password) {
        $data = [
            'email' => $email,
            'password' => $password
        ];
        return $this->request('auth.php?action=login', 'POST', $data);
    }
    
    /**
     * Register new user
     * @param array $userData User data (name, email, password, phone, address)
     */
    public function register($userData) {
        return $this->request('auth.php?action=register', 'POST', $userData);
    }
    
    /**
     * Validate user session
     * @param int $userId User ID to validate
     */
    public function validateUser($userId) {
        return $this->request('auth.php?action=validate', 'POST', ['user_id' => $userId]);
    }
    
    /**
     * Sync user to Pizzeria from Motoshapi
     * @param array $userData User data to sync
     */
    public function syncUser($userData) {
        return $this->request('auth.php?action=sync', 'POST', $userData);
    }
    
    // ===== HELPER METHODS =====
    
    /**
     * Check if Pizzeria API is reachable
     */
    public function testConnection() {
        $result = $this->getPizzas(['limit' => 1]);
        return isset($result['success']) && $result['success'];
    }
    
    /**
     * Set custom timeout
     */
    public function setTimeout($seconds) {
        $this->timeout = $seconds;
    }
}

// === USAGE EXAMPLE ===
/*
// Initialize client with Pizzeria IP address
$pizzeriaClient = new PizzeriaAPIClient('http://192.168.1.100/pizzeria');

// Test connection
if ($pizzeriaClient->testConnection()) {
    echo "Connected to Pizzeria API!\n";
    
    // Get all pizzas
    $response = $pizzeriaClient->getPizzas(['category' => 'Classic']);
    if ($response['success']) {
        foreach ($response['data']['pizzas'] as $pizza) {
            echo "{$pizza['name']} - ₱{$pizza['price']}\n";
        }
    }
    
    // Get specific pizza
    $pizza = $pizzeriaClient->getPizza(1);
    
    // Create order from Motoshapi for Pizzeria
    $orderData = [
        'user_id' => 5,
        'items' => [
            ['pizza_id' => 1, 'quantity' => 2, 'price' => 299],
            ['pizza_id' => 3, 'quantity' => 1, 'price' => 329]
        ],
        'delivery_address' => '123 Main St, City',
        'phone' => '+639171234567',
        'payment_method' => 'cash_on_delivery',
        'notes' => 'Cross-platform order from Motoshapi'
    ];
    
    $result = $pizzeriaClient->createOrder($orderData);
    if ($result['success']) {
        echo "Order created! ID: " . $result['data']['order_id'];
    }
}
*/
?>
