<?php
// PayPal Configuration
// Create sandbox credentials at https://developer.paypal.com/ (Dashboard -> My Apps & Credentials)

// IMPORTANT: keep this file out of version control if you ever commit.
// For local XAMPP usage, this is fine.

define('PAYPAL_ENV', 'sandbox'); // 'sandbox' or 'live'
// sandbox ni jc
// sb-rrito48710828@personal.example.com
//7/!_Cz)Q
define('PAYPAL_CLIENT_ID', 'AatUfZCNTsVPwiyJ4L5oKfiFbXTAlsUM1zu4A_PMEZ7D7V1E1Wy_bEYklEvnZ73m5LDWQJiU_yBWI-G5');
define('PAYPAL_CLIENT_SECRET', 'EAAc0pCdIJwHvX1AUGiYnhxRRsE6PYV_laQj_eiKqRSyRusm0jQH95BngLrRXjP_E422nJ_ctAoyCw85');

define('PAYPAL_CURRENCY_CODE', defined('CURRENCY_CODE') ? CURRENCY_CODE : 'PHP');
