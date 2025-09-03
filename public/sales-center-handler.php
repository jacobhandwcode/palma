<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/sales_center_errors.log');

error_log("=== NEW SALES CENTER FORM SUBMISSION ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
error_log("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$config = [
    'data_dir' => './data/',
    'max_message_length' => 5000,
    'max_name_length' => 100
];

function validate_name($name, $max_length = 100)
{
    return strlen($name) <= $max_length;
}

function validate_email($email)
{
    if (empty($email)) return true;
    $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) &&
        strlen($email) <= 254 &&
        !preg_match('/[<>\r\n]/', $email);
}

function sanitize_input($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function sanitize_array($array)
{
    if (!is_array($array)) return [];
    return array_map('sanitize_input', $array);
}

$raw_input = file_get_contents('php://input');
if (empty($raw_input)) {
    error_log("Empty input received");
    http_response_code(400);
    echo json_encode(['error' => 'No data received']);
    exit;
}

$input = json_decode($raw_input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

$visitDate = sanitize_input($input['visitDate'] ?? '');
$name = sanitize_input($input['name'] ?? '');
$address = sanitize_input($input['address'] ?? '');
$email = sanitize_input($input['email'] ?? '');
$city = sanitize_input($input['city'] ?? '');
$state = sanitize_input($input['state'] ?? '');
$zip = sanitize_input($input['zip'] ?? '');
$country = sanitize_input($input['country'] ?? '');
$phone = sanitize_input($input['phone'] ?? '');
$countryOfResidence = sanitize_input($input['countryOfResidence'] ?? '');
$countryOfOrigin = sanitize_input($input['countryOfOrigin'] ?? '');

$priceRange = sanitize_input($input['priceRange'] ?? '');
$unitType = sanitize_input($input['unitType'] ?? '');
$purchaseReason = sanitize_input($input['purchaseReason'] ?? '');

$officeName = sanitize_input($input['officeName'] ?? '');
$brokerName = sanitize_input($input['brokerName'] ?? '');
$brokerLic = sanitize_input($input['brokerLic'] ?? '');
$agentName = sanitize_input($input['agentName'] ?? '');
$agentLic = sanitize_input($input['agentLic'] ?? '');
$agentEmail = sanitize_input($input['agentEmail'] ?? '');
$agentPhone = sanitize_input($input['agentPhone'] ?? '');

$hearAbout = sanitize_input($input['hearAbout'] ?? '');
$hearAboutOther = sanitize_input($input['hearAboutOther'] ?? '');

$associate = sanitize_input($input['associate'] ?? '');
$initialVisit = sanitize_input($input['initialVisit'] ?? '');
$comments = sanitize_input($input['comments'] ?? '');
$initialVisitType = sanitize_input($input['initialVisitType'] ?? '');

if (!validate_email($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid email address']);
    exit;
}

if (!validate_name($name, $config['max_name_length'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Name is too long']);
    exit;
}

function map_sales_center_hear_about($hearAbout)
{
    $mapping = [
        'website' => ['source' => 'direct', 'medium' => 'direct'],
        'online-advertising' => ['source' => 'display', 'medium' => 'display'],
        'drove-by' => ['source' => 'local', 'medium' => 'driveby'],
        'broker' => ['source' => 'broker', 'medium' => 'referral'],
        'newspaper' => ['source' => 'print', 'medium' => 'print'],
        'email' => ['source' => 'email', 'medium' => 'email'],
        'friends-relative' => ['source' => 'referral', 'medium' => 'referral'],
        'magazine' => ['source' => 'print', 'medium' => 'print'],
        'event' => ['source' => 'event', 'medium' => 'event'],
        'other' => ['source' => 'other', 'medium' => 'other']
    ];

    return $mapping[$hearAbout] ?? ['source' => 'unknown', 'medium' => 'unknown'];
}

$campaign = map_sales_center_hear_about($hearAbout);

$tags = [
    'Palma Miami Beach',
    'Sales Center Visit',
    'In-Person Lead',
    $hearAbout ? ucwords(str_replace('-', ' ', $hearAbout)) : 'Uknown Source',
    $priceRange ? str_replace(['$', '-'], ['', ' to '], $priceRange) : 'Unknown Price Range',
    $unitType ? ucwords(str_replace('-', ' ', $unitType)) : 'Unknown',
    $purchaseReason ? ucwords(str_replace('-', ' ', $purchaseReason)) : 'Unknown Purpose',
    $brokerName ? 'Has Broker' : 'No Broker',
    'Blackline',
];

$subject = 'Sales Center Visit - Palma Miami Beach - ' . $name;

$html_message = create_sales_center_followupboss_email(
    $visitDate,
    $name,
    $address,
    $email,
    $city,
    $state,
    $zip,
    $country,
    $phone,
    $countryOfResidence,
    $countryOfOrigin,
    $priceRange,
    $unitType,
    $purchaseReason,
    $officeName,
    $brokerName,
    $brokerLic,
    $agentName,
    $agentLic,
    $agentEmail,
    $agentPhone,
    $hearAbout,
    $hearAboutOther,
    $associate,
    $initialVisit,
    $comments,
    $initialVisitType,
    $tags,
    $campaign,
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $_SERVER['HTTP_REFERER'] ?? 'https://palmamiamibeach.com/sales-center'
);

$to = 'palma.miami.beach@followupboss.me';
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: no-reply@palmamiamibeach.com" . "\r\n";

$mailSent = mail($to, $subject, $html_message, $headers);

if ($mailSent) {
    save_sales_center_record([
        'id' => uniqid('sales_center_', true),
        'timestamp' => date('Y-m-d H:i:s'),
        'visitDate' => $visitDate,
        'name' => $name,
        'address' => $address,
        'email' => $email,
        'city' => $city,
        'state' => $state,
        'zip' => $zip,
        'country' => $country,
        'phone' => $phone,
        'countryOfResidence' => $countryOfResidence,
        'countryOfOrigin' => $countryOfOrigin,
        'priceRange' => $priceRange,
        'unitType' => $unitType,
        'purchaseReason' => $purchaseReason,
        'officeName' => $officeName,
        'brokerName' => $brokerName,
        'brokerLic' => $brokerLic,
        'agentName' => $agentName,
        'agentLic' => $agentLic,
        'agentEmail' => $agentEmail,
        'agentPhone' => $agentPhone,
        'hearAbout' => $hearAbout,
        'hearAboutOther' => $hearAboutOther,
        'associate' => $associate,
        'initialVisit' => $initialVisit,
        'comments' => $comments,
        'initialVisitType' => $initialVisitType,
        'tags' => $tags,
        'campaign' => $campaign,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
    ], $config);

    echo json_encode(['success' => true, 'message' => 'Thank you for visiting our sales center! Your information has been recorded.']);
    error_log("Sales center form submission successful - ID: " . $data['id'] . ", Name: " . $name . ", Email: " . $email);
} else {
    error_log("Failed to send email to FollowUpBoss for sales center form");
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit your information. Please try again or contact us directly.']);
}

error_log("=== SALES CENTER FORM SUBMISSION COMPLETED ===\n");

function create_sales_center_followupboss_email($visitDate, $name, $address, $email, $city, $state, $zip, $country, $phone, $countryOfResidence, $countryOfOrigin, $priceRange, $unitType, $purchaseReason, $officeName, $brokerName, $brokerLic, $agentName, $agentLic, $agentEmail, $agentPhone, $hearAbout, $hearAboutOther, $associate, $initialVisit, $comments, $initialVisitType, $tags, $campaign, $user_ip, $sourceUrl)
{
    $nameParts = explode(' ', $name, 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';

    $html_message = '
<html>
<head>
<meta name="fub-notification" content="Sales center visit notification">
<meta name="fub-first-name" content="' . htmlspecialchars($firstName) . '">
<meta name="fub-last-name" content="' . htmlspecialchars($lastName) . '">
<meta name="fub-email" content="' . htmlspecialchars($email) . '">
<meta name="fub-phone" content="' . htmlspecialchars($phone) . '">
<meta name="fub-source" content="Sales Center - PalmaMiamiBeach.com">
<meta name="fub-source-url" content="' . htmlspecialchars($sourceUrl) . '">
<meta name="fub-contacted" content="No">
<meta name="fub-event-type" content="Sales Center Visit">
<meta name="fub-lead-type" content="Prospect">
<meta name="fub-lead-stage" content="Prospect">
<meta name="fub-tags" content="' . htmlspecialchars(implode(', ', $tags)) . '">
<meta name="fub-message" content="' . htmlspecialchars($comments) . '">
<meta name="fub-description" content="Sales center visit from ' . htmlspecialchars($name) . '">
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

Sales center visit notification

First Name: ' . htmlspecialchars($firstName) . '
Last Name: ' . htmlspecialchars($lastName) . '
Full Name: ' . htmlspecialchars($name) . '
Email: ' . htmlspecialchars($email) . '
Phone: ' . htmlspecialchars($phone) . '

Visit Date: ' . htmlspecialchars($visitDate) . '

Address Information:
Address: ' . htmlspecialchars($address) . '
City: ' . htmlspecialchars($city) . '
State: ' . htmlspecialchars($state) . '
ZIP: ' . htmlspecialchars($zip) . '
Country: ' . htmlspecialchars($country) . '
Country of Residence: ' . htmlspecialchars($countryOfResidence) . '
Country of Origin: ' . htmlspecialchars($countryOfOrigin) . '

What They\'re Looking For:
Price Range: ' . htmlspecialchars($priceRange) . '
Unit Type: ' . htmlspecialchars($unitType) . '
Purchase Reason: ' . htmlspecialchars($purchaseReason) . '

Broker Information:
Office Name: ' . htmlspecialchars($officeName) . '
Broker Name: ' . htmlspecialchars($brokerName) . '
Broker License: ' . htmlspecialchars($brokerLic) . '
Agent Name: ' . htmlspecialchars($agentName) . '
Agent License: ' . htmlspecialchars($agentLic) . '
Agent Email: ' . htmlspecialchars($agentEmail) . '
Agent Phone: ' . htmlspecialchars($agentPhone) . '

How They Heard About Project: ' . htmlspecialchars($hearAbout) . '
Other Details: ' . htmlspecialchars($hearAboutOther) . '

Office Use Only:
Associate: ' . htmlspecialchars($associate) . '
Initial Visit: ' . htmlspecialchars($initialVisit) . '
Initial Visit Type: ' . htmlspecialchars($initialVisitType) . '
Comments: ' . htmlspecialchars($comments) . '

Source: Sales Center - PalmaMiamiBeach.com
Source URL: ' . htmlspecialchars($sourceUrl) . '
Contacted: No
Event Type: Sales Center Visit
Lead Type: Prospect
Lead Stage: Prospect
Tags: ' . htmlspecialchars(implode(', ', $tags)) . '
Description: Sales center visit from ' . htmlspecialchars($name) . '

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

Notes: Sales center visit form submitted on ' . date('Y-m-d H:i:s') . ' from IP: ' . htmlspecialchars($user_ip) . '
</body>
</html>';

    return $html_message;
}

function save_sales_center_record($data, $config)
{
    $file = $config['data_dir'] . 'sales_center_data.json';
    $records = [];

    if (!file_exists($config['data_dir'])) {
        if (!mkdir($config['data_dir'], 0755, true)) {
            error_log("Failed to create data directory for sales center records");
            return false;
        }
    }

    if (file_exists($file)) {
        $content = file_get_contents($file);
        if ($content) {
            $records = json_decode($content, true) ?: [];
        }
    }

    $records[] = $data;

    if (count($records) > 1000) {
        $records = array_slice($records, -1000);
    }

    $result = file_put_contents($file, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    if ($result === false) {
        error_log("Failed to save sales center record to sales_center_data.json");
        return false;
    }

    return true;
}
