<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to homepage
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$baseUrl = $scriptDir;
if (str_ends_with($baseUrl, '/actions')) {
    $baseUrl = dirname($baseUrl);
}
if ($baseUrl === '' || $baseUrl === '\\') {
    $baseUrl = '';
}
header('Location: ' . $baseUrl . '/pages/index.php');
exit();
?> 