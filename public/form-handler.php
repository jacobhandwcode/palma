<?php
// --- CONFIG & ERROR HANDLING ---
error_reporting(E_ALL);
ini_set('display_errors', 0); // hide errors from users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/form_errors.log');

header('Content-Type: application/json');

// --- GET RAW POST DATA ---
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid form submission.']);
    exit;
}

// --- REQUIRED FIELDS ---
$required = ['firstName', 'lastName', 'email', 'terms', 'cf-turnstile-response'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'error' => 'Missing required field: ' . $field]);
        exit;
    }
}

// --- VERIFY TURNSTILE ---
$secret = '0x4AAAAAACEZfzDracOLfA9mjOUCmhb-NTc';
$token = $data['cf-turnstile-response'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'secret' => $secret,
    'response' => $token,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (!$result || empty($result['success'])) {
    echo json_encode(['success' => false, 'error' => 'Security verification failed.']);
    exit;
}

// --- SUCCESS RESPONSE ---
echo json_encode([
    'success' => true,
    'message' => 'Your inquiry has been successfully submitted.'
]);
exit;
