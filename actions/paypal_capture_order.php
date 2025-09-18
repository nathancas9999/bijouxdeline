<?php
session_start();
header('Content-Type: application/json');

$configFile = __DIR__ . '/../config.php';
if (!file_exists($configFile)) {
    echo json_encode(['success' => false, 'error' => 'Fichier de configuration introuvable.']);
    exit;
}
require_once $configFile;

$data = json_decode(file_get_contents('php://input'));
if (!isset($data->orderID)) {
    echo json_encode(['success' => false, 'error' => 'Order ID manquant']);
    exit;
}
$orderID = $data->orderID;

$auth = base64_encode(PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET);
$ch_token = curl_init();
curl_setopt($ch_token, CURLOPT_URL, PAYPAL_API_URL . "/v1/oauth2/token");
curl_setopt($ch_token, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch_token, CURLOPT_POST, 1);
curl_setopt($ch_token, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch_token, CURLOPT_HTTPHEADER, ["Authorization: Basic $auth"]);
curl_setopt($ch_token, CURLOPT_SSL_VERIFYPEER, true);
$response_token = curl_exec($ch_token);
curl_close($ch_token);

$authData = json_decode($response_token);
if (!isset($authData->access_token)) {
    echo json_encode(['success' => false, 'error' => 'Erreur d\'authentification PayPal']);
    exit;
}
$accessToken = $authData->access_token;

$ch_capture = curl_init();
curl_setopt($ch_capture, CURLOPT_URL, PAYPAL_API_URL . "/v2/checkout/orders/" . $orderID . "/capture");
curl_setopt($ch_capture, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch_capture, CURLOPT_POST, 1);
curl_setopt($ch_capture, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $accessToken
]);
curl_setopt($ch_capture, CURLOPT_SSL_VERIFYPEER, true);
$response_capture = curl_exec($ch_capture);
curl_close($ch_capture);

$result = json_decode($response_capture);
if (isset($result->status) && $result->status == 'COMPLETED') {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'details' => $result]);
}