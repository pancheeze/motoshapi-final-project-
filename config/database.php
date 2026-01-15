<?php
/**
 * Database Configuration and Connection Module
 * Motoshapi - Modularized Database Layer
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'motoshapi_db');

// Global PDO connection instance
$conn = null;

/**
 * Initialize database connection
 * @return PDO connection object
 */
function initDatabaseConnection() {
    global $conn;
    
    if ($conn !== null) {
        return $conn; // Return existing connection
    }
    
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

/**
 * Get database connection (lazy initialization)
 * @return PDO connection object
 */
function getConnection() {
    global $conn;
    if ($conn === null) {
        return initDatabaseConnection();
    }
    return $conn;
}

/**
 * Execute a prepared query with parameters
 * @param string $query SQL query with placeholders
 * @param array $params Parameters to bind
 * @return PDOStatement
 */
function executeQuery($query, $params = []) {
    $conn = getConnection();
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch all results from a query
 * @param string $query SQL query
 * @param array $params Parameters to bind
 * @return array
 */
function fetchAll($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchAll();
}

/**
 * Fetch single row from a query
 * @param string $query SQL query
 * @param array $params Parameters to bind
 * @return array|false
 */
function fetchOne($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetch();
}

/**
 * Execute INSERT/UPDATE/DELETE query
 * @param string $query SQL query
 * @param array $params Parameters to bind
 * @return bool Success status
 */
function executeUpdate($query, $params = []) {
    try {
        executeQuery($query, $params);
        return true;
    } catch(PDOException $e) {
        error_log("Database update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get last inserted ID
 * @return string
 */
function getLastInsertId() {
    $conn = getConnection();
    return $conn->lastInsertId();
}

// Initialize connection on file include (maintains backward compatibility)
$conn = initDatabaseConnection();
?> 