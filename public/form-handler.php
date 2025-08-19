<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Configuration
$config = [
    'turnstile_secret' => '0x4AAAAAABnGYtiTkDJKVBmOOTO3fKRvsDM',
    'max_requests_per_hour' => 10,
    'data_dir' => './data/',
    'max_message_length' => 5000,
    'max_name_length' => 50
];

// Rate limiting function
function check_rate_limit($ip, $config)
{
    $rate_file = $config['data_dir'] . 'rate_limits.json';
    $max_requests = $config['max_requests_per_hour'];
    $time_window = 3600; // 1 hour

    if (!file_exists($config['data_dir'])) {
        if (!mkdir($config['data_dir'], 0755, true)) {
            error_log("Failed to create data directory");
            return false;
        }
    }

    $rates = [];
    if (file_exists($rate_file)) {
        $content = file_get_contents($rate_file);
        if ($content !== false) {
            $rates = json_decode($content, true) ?: [];
        }
    }

    $now = time();
    $user_requests = $rates[$ip] ?? [];

    // Clean old requests
    $user_requests = array_filter($user_requests, function ($time) use ($now, $time_window) {
        return ($now - $time) < $time_window;
    });

    if (count($user_requests) >= $max_requests) {
        error_log("Rate limit exceeded for IP: $ip");
        return false;
    }

    // Add current request
    $user_requests[] = $now;
    $rates[$ip] = array_values($user_requests);

    // Clean up old IPs
    foreach ($rates as $stored_ip => $requests) {
        $rates[$stored_ip] = array_filter($requests, function ($time) use ($now, $time_window) {
            return ($now - $time) < $time_window;
        });

        if (empty($rates[$stored_ip])) {
            unset($rates[$stored_ip]);
        }
    }

    file_put_contents($rate_file, json_encode($rates));
    return true;
}

// Verify Turnstile CAPTCHA
function verify_turnstile($token, $secret)
{
    if (empty($token) || empty($secret)) {
        error_log("Turnstile: Missing token or secret");
        return false;
    }

    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    $data = [
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ]);

    $result = file_get_contents($url, false, $context);
    if ($result === false) {
        error_log("Turnstile: Failed to connect to verification server");
        return false;
    }

    $response = json_decode($result, true);
    $success = $response['success'] ?? false;

    if (!$success) {
        error_log("Turnstile verification failed: " . json_encode($response));
    } else {
        error_log("Turnstile verification successful");
    }

    return $success;
}

// Validation functions
function validate_name($name, $max_length = 50)
{
    return !empty($name) &&
        strlen($name) <= $max_length &&
        preg_match('/^[a-zA-Z\s\-\'\.]+$/u', trim($name));
}

function validate_email($email)
{
    $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) &&
        strlen($email) <= 254 &&
        !preg_match('/[<>\r\n]/', $email) &&
        !empty($email);
}

function sanitize_input($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Check rate limit
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!check_rate_limit($user_ip, $config)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please try again in an hour.']);
    exit;
}

$raw_input = file_get_contents('php://input');
if (empty($raw_input)) {
    http_response_code(400);
    echo json_encode(['error' => 'No data received']);
    exit;
}

$input = json_decode($raw_input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Verify Turnstile CAPTCHA
$turnstile_token = $input['cf-turnstile-response'] ?? '';
if (!verify_turnstile($turnstile_token, $config['turnstile_secret'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Security verification failed. Please try again.']);
    exit;
}

$firstName = trim($input['firstName'] ?? '');
$lastName = trim($input['lastName'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$comments = trim($input['comments'] ?? '');
$hearAbout = trim($input['hearAbout'] ?? '');
$realtor = $input['realtor'] ?? 'no';
$buyerBroker = $input['buyerBroker'] ?? 'buyer';
$terms = $input['terms'] ?? false;

// Validate required fields
if (!validate_name($firstName, $config['max_name_length'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid first name (letters, spaces, hyphens, apostrophes only)']);
    exit;
}

if (!validate_name($lastName, $config['max_name_length'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid last name (letters, spaces, hyphens, apostrophes only)']);
    exit;
}

if (!validate_email($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid email address']);
    exit;
}

if (!$terms) {
    http_response_code(400);
    echo json_encode(['error' => 'Please accept the terms and privacy policy']);
    exit;
}

// Sanitize inputs
$firstName = sanitize_input($firstName);
$lastName = sanitize_input($lastName);
$email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
$phone = sanitize_input($phone);
$comments = sanitize_input($comments);
$hearAbout = sanitize_input($hearAbout);

$campaign = map_hear_about_to_campaign($hearAbout);
$leadType = ($buyerBroker === 'buyer') ? 'Buyer' : 'Seller';
$tags = [
    'Palma Miami Beach',
    $hearAbout ? ucwords(str_replace('-', ' ', $hearAbout)) : 'Unknown Source',
    $realtor === 'yes' ? 'Realtor' : 'Non-Realtor',
    'Website Lead'
];

$subject = 'New Lead - Palma Miami Beach - ' . $firstName . ' ' . $lastName;

$html_message = create_followupboss_email(
    $firstName,
    $lastName,
    $email,
    $phone,
    $comments,
    $hearAbout,
    $realtor,
    $buyerBroker,
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $_SERVER['HTTP_REFERER'] ?? 'https://palmamiamibeach.com'
);

$to = 'palma.miami.beach@followupboss.me';
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: no-reply@palmamiamibeach.com" . "\r\n";

$mailSent = mail($to, $subject, $html_message, $headers);

if ($mailSent) {
    save_lead_record([
        'timestamp' => date('Y-m-d H:i:s'),
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'comments' => $comments,
        'hearAbout' => $hearAbout,
        'realtor' => $realtor,
        'buyerBroker' => $buyerBroker,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'turnstile_verified' => true
    ]);

    echo json_encode(['success' => true, 'message' => 'Thank you for your inquiry! We will contact you soon.']);
} else {
    error_log("Failed to send email to FollowUpBoss");
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit your inquiry. Please try again or contact us directly.']);
}

function map_hear_about_to_campaign($hearAbout)
{
    $mapping = [
        'google-organic' => ['source' => 'google', 'medium' => 'organic'],
        'google-keywords' => ['source' => 'google', 'medium' => 'cpc'],
        'social-media' => ['source' => 'social', 'medium' => 'social'],
        'internet-ad' => ['source' => 'display', 'medium' => 'display'],
        'internet-search' => ['source' => 'google', 'medium' => 'organic'],
        'real-estate-site' => ['source' => 'referral', 'medium' => 'referral'],
        'referral' => ['source' => 'referral', 'medium' => 'referral'],
        'direct' => ['source' => 'direct', 'medium' => 'direct'],
        'magazine' => ['source' => 'print', 'medium' => 'print'],
        'newspaper' => ['source' => 'print', 'medium' => 'print'],
        'broker' => ['source' => 'broker', 'medium' => 'referral'],
        'event' => ['source' => 'event', 'medium' => 'event'],
        'area-driveby' => ['source' => 'local', 'medium' => 'driveby'],
        'gale-south-beach-loyalty-guest' => ['source' => 'gale', 'medium' => 'loyalty']
    ];

    return $mapping[$hearAbout] ?? ['source' => 'unknown', 'medium' => 'unknown'];
}

function create_followupboss_email($firstName, $lastName, $email, $phone, $comments, $hearAbout, $realtor, $buyerBroker, $user_ip, $sourceUrl)
{
    $campaign = map_hear_about_to_campaign($hearAbout);
    $leadType = ($buyerBroker === 'buyer') ? 'Buyer' : 'Seller';
    $tags = [
        'Palma Miami Beach',
        $hearAbout ? ucwords(str_replace('-', ' ', $hearAbout)) : 'Unknown Source',
        $realtor === 'yes' ? 'Realtor' : 'Non-Realtor',
        'Website Lead'
    ];

    $html_message = '
<html>
<head>
<meta name="fub-notification" content="New lead activity notification">
<meta name="fub-first-name" content="' . htmlspecialchars($firstName) . '">
<meta name="fub-last-name" content="' . htmlspecialchars($lastName) . '">
<meta name="fub-email" content="' . htmlspecialchars($email) . '">
<meta name="fub-phone" content="' . htmlspecialchars($phone) . '">
<meta name="fub-source" content="PalmaMiamiBeach.com">
<meta name="fub-source-url" content="' . htmlspecialchars($sourceUrl) . '">
<meta name="fub-contacted" content="No">
<meta name="fub-event-type" content="Property Inquiry">
<meta name="fub-lead-type" content="' . $leadType . '">
<meta name="fub-lead-stage" content="Lead">
<meta name="fub-tags" content="' . htmlspecialchars(implode(', ', $tags)) . '">
<meta name="fub-message" content="' . htmlspecialchars($comments) . '">
<meta name="fub-description" content="Property inquiry from ' . htmlspecialchars($firstName . ' ' . $lastName) . '">
<meta name="fub-background" content="Inquiry type: ' . htmlspecialchars($buyerBroker) . ', Source: ' . htmlspecialchars($hearAbout) . '">
<meta name="fub-property-street" content="600 71st Street">
<meta name="fub-property-city" content="Miami Beach">
<meta name="fub-property-state" content="FL">
<meta name="fub-property-code" content="33141">
<meta name="fub-property-type" content="Residential">
<meta name="fub-address-street" content="600 71st Street">
<meta name="fub-address-city" content="Miami Beach">
<meta name="fub-address-state" content="FL">
<meta name="fub-address-code" content="33141">
<meta name="fub-campaign-source" content="' . htmlspecialchars($campaign['source']) . '">
<meta name="fub-campaign-medium" content="' . htmlspecialchars($campaign['medium']) . '">
</head>
<body>

New lead activity notification

First Name: ' . htmlspecialchars($firstName) . '
Last Name: ' . htmlspecialchars($lastName) . '
Email: ' . htmlspecialchars($email) . '
Phone: ' . htmlspecialchars($phone) . '

Source: PalmaMiamiBeach.com
Source URL: ' . htmlspecialchars($sourceUrl) . '
Contacted: No
Event Type: Property Inquiry
Lead Type: ' . $leadType . '
Lead Stage: Lead
Tags: ' . htmlspecialchars(implode(', ', $tags)) . '
Message: ' . htmlspecialchars($comments) . '
Description: Property inquiry from ' . htmlspecialchars($firstName . ' ' . $lastName) . '

Background: Inquiry type: ' . htmlspecialchars($buyerBroker) . ', Is realtor: ' . htmlspecialchars($realtor) . ', Source: ' . htmlspecialchars($hearAbout) . '

Property Street: 600 71st Street
Property City: Miami Beach
Property State: FL
Property Postal Code: 33141
Property Type: Residential

Address Street: 600 71st Street
Address City: Miami Beach
Address State: FL
Address Postal Code: 33141

Campaign Source: ' . htmlspecialchars($campaign['source']) . '
Campaign Medium: ' . htmlspecialchars($campaign['medium']) . '
Campaign Term: 
Campaign Content: 
Campaign Campaign: 

Notes: Palma Miami Beach website inquiry submitted on ' . date('Y-m-d H:i:s') . ' from IP: ' . htmlspecialchars($user_ip) . '
</body>
</html>';

    return $html_message;
}

function save_lead_record($data)
{
    $file = 'leads.json';
    $leads = [];

    if (file_exists($file)) {
        $content = file_get_contents($file);
        if ($content) {
            $leads = json_decode($content, true) ?: [];
        }
    }

    $leads[] = $data;

    if (count($leads) > 100) {
        $leads = array_slice($leads, -100);
    }

    file_put_contents($file, json_encode($leads, JSON_PRETTY_PRINT));
}
