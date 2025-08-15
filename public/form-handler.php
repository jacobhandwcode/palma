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

$firstName = trim($input['firstName'] ?? '');
$lastName = trim($input['lastName'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$comments = trim($input['comments'] ?? '');
$hearAbout = trim($input['hearAbout'] ?? '');
$realtor = $input['realtor'] ?? 'no';
$buyerBroker = $input['buyerBroker'] ?? 'buyer';
$terms = $input['terms'] ?? false;

if (empty($firstName) || empty($lastName) || empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please fill in all required fields (first name, last name, email)']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid email address']);
    exit;
}

if (!$terms) {
    http_response_code(400);
    echo json_encode(['error' => 'Please accept the terms and privacy policy']);
    exit;
}

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
    $firstName, $lastName, $email, $phone, $comments, 
    $hearAbout, $realtor, $buyerBroker, 
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $_SERVER['HTTP_REFERER'] ?? 'https://palma.dreamhosters.com'
);

$to = 'palma.miami.beach@followupboss.me';
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: no-reply@palma.dreamhosters.com" . "\r\n";

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
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
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
?>