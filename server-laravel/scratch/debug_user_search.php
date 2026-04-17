<?php

require_once 'vendor/autoload.php';

// Mock config from .env (simplified)
$url = 'http://localhost:8080/api.php/v1';
$appToken = 'znSSmElYIy2PHXLnmab5WZTGCcys8QoBcUmoAosL';
$userToken = '0U3haWM3njJVO2pz8DLmKZvXvyYyuBaodjI1jIhm';

function getSession($url, $appToken, $userToken) {
    $ch = curl_init("$url/initSession");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "App-Token: $appToken",
        "Authorization: user_token $userToken"
    ]);
    $resp = curl_exec($ch);
    $data = json_decode($resp, true);
    return $data['session_token'] ?? null;
}

$session = getSession($url, $appToken, $userToken);
if (!$session) die("Failed to get session\n");

$email = 'louisegimenez@biblioteca.com';

// Búsqueda forzando el ID (2) en el resultado
$searchUrl = "$url/search/User?criteria[0][field]=5&criteria[0][searchtype]=contains&criteria[0][value]=" . urlencode($email) . "&forcedisplay[0]=2&range=0-1";

$ch = curl_init($searchUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "App-Token: $appToken",
    "Session-Token: $session"
]);
$resp = curl_exec($ch);

echo "Search Response with forced display ID:\n";
$data = json_decode($resp, true);
print_r($data);
