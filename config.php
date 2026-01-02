<?php
header('Access-Control-Allow-Origin: *');

$_INFO = array();
$_INFO[ 'hostname' ] = "127.0.0.1";
$_INFO[ 'username' ] = "root";
$_INFO[ 'password' ] = "d0f8534d479a88488c44";
$_INFO[ 'dbname' ] = "product_manager";


$rTelegram = 'xtreamMasters';
$rTelegram = '';
$rEmail = 'skycccamd@gmail.com';
$rWhatsapp = '+447307530066';

$client_url = 'http://test.oscam.fun/product_panel/';
$sub_url = 'http://test.oscam.fun/product_panel/';


// 2. Block common attack patterns in input
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    // Common attack patterns to block
    $attackPatterns = [
        '/<script\b[^>]*>(.*?)<\/script>/is',
        '/on[a-z]+\s*=/i',
        '/javascript:/i',
        '/eval\s*\(/i',
        '/union\s+select/i',
        '/select\s+from/i',
        '/insert\s+into/i',
        '/update\s+set/i',
        '/delete\s+from/i',
        '/drop\s+table/i',
        '/\/\*.*?\*\//s',
        '/<\?php/i',
        '/\.\.\//',
        '/\0/'
    ];
    
    foreach ($attackPatterns as $pattern) {
        if (preg_match($pattern, $input)) {
            header('HTTP/1.1 400 Bad Request');
            die('Malicious input detected');
        }
    }
    
    return $input;
}

// Sanitize all input
$_GET = sanitizeInput($_GET);
$_POST = sanitizeInput($_POST);
$_REQUEST = sanitizeInput($_REQUEST);
$_COOKIE = sanitizeInput($_COOKIE);
