<?php

require_once 'vendor/autoload.php';

// Mock config
$url = 'http://localhost:8080/api.php/v1';
$appToken = 'znSSmElYIy2PHXLnmab5WZTGCcys8QoBcUmoAosL';
$userToken = '0U3haWM3njJVO2pz8DLmKZvXvyYyuBaodjI1jIhm';
$itemtype = 'Glpi\CustomAsset\LibrosAsset';

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

// Try specific search
$searchUrl = "$url/search/$itemtype?range=0-10";
$ch = curl_init($searchUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "App-Token: $appToken",
    "Session-Token: $session"
]);
$resp = curl_exec($ch);
echo "Search Response for $itemtype:\n";
echo $resp . "\n\n";

// Manufacturers
echo "\n--- Manufacturers ---\n";
$mansUrl = "$url/Manufacturer?range=0-10";
curl_setopt($ch, CURLOPT_URL, $mansUrl);
$resp = curl_exec($ch);
echo $resp . "\n";

// LibrosAssetType
echo "\n--- LibrosAssetType ---\n";
$genreItemtype = 'Glpi\CustomAsset\LibrosAssetType';
$genresUrl = "$url/$genreItemtype?range=0-10";
curl_setopt($ch, CURLOPT_URL, $genresUrl);
$resp = curl_exec($ch);
echo $resp . "\n";
