<?php
// Currency Configuration
define('CURRENCY_SYMBOL', '₱');
define('CURRENCY_CODE', 'PHP');
define('CURRENCY_NAME', 'Philippine Peso');
define('CURRENCY_DECIMAL_PLACES', 2);
define('CURRENCY_DECIMAL_SEPARATOR', '.');
define('CURRENCY_THOUSANDS_SEPARATOR', ',');

// Function to format price in Philippine Peso
function format_price($price) {
    return CURRENCY_SYMBOL . ' ' . number_format(
        $price,
        CURRENCY_DECIMAL_PLACES,
        CURRENCY_DECIMAL_SEPARATOR,
        CURRENCY_THOUSANDS_SEPARATOR
    );
}
?> 