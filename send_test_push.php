<?php
header('Content-Type: application/json');

$APP_ID    = "7e511f6c-c42e-4e6f-a989-b7eb636981bd";
$API_KEY   = "os_v2_app_pzir63gefzhg7kmjw7vwg2mbxuw5fkwgb3peeammsrqeru76byresteqwxx3tf7ucszixn3k2awo22uv2k45upx2h53z4ilxioj4eei"; 

$payload = [
    "app_id" => $APP_ID,
    "included_segments" => ["All"], // VERY SIMPLE — send to all subscribed users
    "headings" => ["en" => "New Order!"],
    "contents" => ["en" => "Hey Azeezat you have a new order, check your dashboard."],
];

$ch = curl_init("https://api.onesignal.com/notifications");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json; charset=utf-8",
    "Authorization: Basic $API_KEY"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
curl_close($ch);

echo $response;
