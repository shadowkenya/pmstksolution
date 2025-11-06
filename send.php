<?php
error_reporting(0); // disable warnings/notices
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Read input from fetch
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['phone_number'], $input['amount'])) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Invalid input']);
    exit;
}

$phone = $input['phone_number'];
$amount = (int)$input['amount'];

// ==============================
// PAYHERO CREDENTIALS
// ==============================
$channel_id = 3997; // Your Account ID
$basicAuth = 'Basic bE15RzdWZVF0a2kzUDBDblMzWnI6c1JJTFFuUjNMMEg4MGNucFJYQTQ3dmZSRDJLMGhHN0JwampvUDlWTA==';
$callback_url = 'https://app.payhero.co.ke/lipwa/3513/callback.php'; // your HTTPS callback URL
$provider = 'm-pesa';
$externalRef = 'INV-'.time(); // unique reference per transaction
$customer_name = 'Customer'; // optional

// Prepare STK push payload
$data = [
    'amount' => $amount,
    'phone_number' => $phone,
    'channel_id' => $channel_id,
    'provider' => $provider,
    'external_reference' => $externalRef,
    'customer_name' => $customer_name,
    'callback_url' => $callback_url
];

// Initialize cURL
$curl = curl_init('https://backend.payhero.co.ke/api/v2/payments');
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Authorization: ' . $basicAuth,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode(['success'=>false, 'message'=>'Curl Error: '.$err]);
    exit;
}

// Decode API response
$result = json_decode($response, true);
if (!$result) {
    echo json_encode(['success'=>false, 'message'=>'Invalid response from PayHero']);
    exit;
}

// Return result
if (!empty($result['success'])) {
    echo json_encode([
        'success' => true,
        'reference' => $result['reference'] ?? '',
        'checkout_id' => $result['CheckoutRequestID'] ?? '',
        'message' => 'STK Push sent successfully. Check your phone.'
    ]);
} else {
    $msg = $result['message'] ?? json_encode($result);
    echo json_encode(['success'=>false,'message'=>$msg]);
}
