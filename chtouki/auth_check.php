<?php

function get_ip_server()
{
    $ip = 'UNKNOWN';

    $ip_headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($ip_headers as $header) {
        if (isset($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            break;
        }
    }

    return $ip;
}

$ip = get_ip_server();
$access_key = '0d5ad827034ecba160bc18dc7f52098e';

// Initialize CURL:
$ch = curl_init('https://api.ipstack.com/' . $ip . '?access_key=' . $access_key . '');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Store the data:
$json = curl_exec($ch);
curl_close($ch);

// Decode JSON response:
$api_result = json_decode($json, true);

$allowedCountries = ['CH','FR','BE','MA'];
$redirectUrl = 'https://www.sony.com/en/';

$nobots = 0;

if (in_array($api_result['country_code'], $allowedCountries)) {
    $nobots = 1;
} else {
    header('Location: ' . $redirectUrl);
    exit;
}