<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

if (empty($_SESSION['panier'])) {
    echo json_encode(['error' => 'Le panier est vide.']);
    exit;
}

// On s'assure que le total final a bien été calculé et stocké par la page de paiement
if (!isset($_SESSION['last_order_total'])) {
    echo json_encode(['error' => 'Le montant final de la commande est manquant.']);
    exit;
}

$total_final = $_SESSION['last_order_total'];

// --- 1. Obtenir le token d'accès ---
$auth = base64_encode(PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET);
$ch_token = curl_init();
curl_setopt($ch_token, CURLOPT_URL, PAYPAL_API_URL . "/v1/oauth2/token");
curl_setopt($ch_token, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch_token, CURLOPT_POST, 1);
curl_setopt($ch_token, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch_token, CURLOPT_HTTPHEADER, ["Authorization: Basic $auth"]);
curl_setopt($ch_token, CURLOPT_SSL_VERIFYPEER, true);

$response_token = curl_exec($ch_token);
if (curl_errno($ch_token)) {
    echo json_encode(['error' => 'Erreur cURL (token): ' . curl_error($ch_token)]);
    curl_close($ch_token);
    exit;
}
curl_close($ch_token);

$authData = json_decode($response_token);
if (!isset($authData->access_token)) {
    echo json_encode(['error' => 'Erreur d\'authentification PayPal.', 'details' => $authData]);
    exit;
}
$accessToken = $authData->access_token;

// --- 2. Créer la commande avec le bon montant ---
$ch_order = curl_init();
curl_setopt($ch_order, CURLOPT_URL, PAYPAL_API_URL . "/v2/checkout/orders");
curl_setopt($ch_order, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch_order, CURLOPT_POST, 1);
curl_setopt($ch_order, CURLOPT_POSTFIELDS, json_encode([
    "intent" => "CAPTURE",
    "purchase_units" => [[
        "amount" => [
            "currency_code" => "EUR",
            "value" => number_format($total_final, 2, '.', '') // On utilise le total final correct
        ]
    ]]
]));
curl_setopt($ch_order, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $accessToken
]);
curl_setopt($ch_order, CURLOPT_SSL_VERIFYPEER, true);

$response_order = curl_exec($ch_order);
if (curl_errno($ch_order)) {
    echo json_encode(['error' => 'Erreur cURL (order): ' . curl_error($ch_order)]);
    curl_close($ch_order);
    exit;
}
curl_close($ch_order);

echo $response_order;