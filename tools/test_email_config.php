<?php
require_once '../email/vendor/autoload.php';
require_once '../email/config/email.php';

echo "=== Email Configuration Test ===\n\n";
echo "SMTP Host: " . SMTP_HOST . "\n";
echo "SMTP Port: " . SMTP_PORT . "\n";
echo "SMTP Username: " . SMTP_USERNAME . "\n";
echo "SMTP Password Length: " . strlen(SMTP_PASSWORD) . " characters\n";
echo "SMTP Password (masked): " . str_repeat('*', strlen(SMTP_PASSWORD)) . "\n";
echo "SMTP Password (first 4 chars): " . substr(SMTP_PASSWORD, 0, 4) . "\n";
echo "SMTP Encryption: " . SMTP_ENCRYPTION . "\n";
echo "From Email: " . FROM_EMAIL . "\n";
echo "From Name: " . FROM_NAME . "\n";
