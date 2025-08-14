<?php
// Enhanced error logging and debugging
ini_set('log_errors', 1);
ini_set('error_log', '../logs/form_errors.log'); // Move logs outside web root
error_reporting(E_ALL);

// Include PHPMailer - Downloaded manually
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load configuration securely
function load_config()
{
    return [
        'followupboss_email' => 'palma.miami.beach@followupboss.me',
        'from_email' => 'noreply@palma.dreamhosters.com',
        'smtp_password' => '6Pl#}e$*L7k6',
        'widget_tracker_id' => 'WT-JKXYEMIJ',
        'data_dir' => '../data/',
        'max_requests_per_hour' => 15,
        'max_message_length' => 5000,
        'max_name_length' => 50
    ];
}

$config = load_config();

error_log("=== NEW PALMA MIAMI BEACH REQUEST ===");
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

function check_rate_limit($ip, $config)
{
    $rate_file = $config['data_dir'] . 'rate_limits.json';
    $max_requests = $config['max_requests_per_hour'];
    $time_window = 3600;

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

    // Clean up old IPs to prevent file bloat
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

// Enhanced validation functions
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
        !preg_match('/[<>\r\n]/', $email) && // Prevent injection
        !empty($email);
}

function validate_phone($phone)
{
    if (empty($phone)) return true; // Phone is optional
    $phone = preg_replace('/[^\d+\-\(\)\s]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 20;
}

function validate_message($message, $max_length = 5000)
{
    return strlen($message) <= $max_length;
}

function sanitize_input($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
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

// Create FollowUpBoss formatted email
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
    
    $subject = 'New Lead - Palma Miami Beach - ' . $firstName . ' ' . $lastName;

    $html_message = '
<html>
<head>
<meta name="fub-notification" content="New lead activity notification">
<meta name="fub-first-name" content="' . htmlspecialchars($firstName) . '">
<meta name="fub-last-name" content="' . htmlspecialchars($lastName) . '">
<meta name="fub-email" content="' . htmlspecialchars($email) . '">
<meta name="fub-phone" content="' . htmlspecialchars($phone) . '">
<meta name="fub-source" content="palma.dreamhosters.com">
<meta name="fub-source-url" content="' . htmlspecialchars($sourceUrl) . '">
<meta name="fub-contacted" content="No">
<meta name="fub-event-type" content="Property Inquiry">
<meta name="fub-lead-type" content="' . $leadType . '">
<meta name="fub-lead-stage" content="Lead">
<meta name="fub-tags" content="' . htmlspecialchars(implode(', ', $tags)) . '">
<meta name="fub-message" content="' . htmlspecialchars($comments) . '">
<meta name="fub-description" content="Property inquiry from ' . htmlspecialchars($firstName . ' ' . $lastName) . '">
<meta name="fub-background" content="Inquiry type: ' . htmlspecialchars($buyerBroker) . ', Is realtor: ' . htmlspecialchars($realtor) . ', Source: ' . htmlspecialchars($hearAbout) . '">
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

Source: palma.dreamhosters.com
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

    return [$subject, $html_message];
}

// Enhanced email function using PHPMailer with SMTP
function send_email_to_followupboss($to, $subject, $html_message, $config)
{
    error_log("Attempting to send lead to FollowUpBoss:");
    error_log("To: " . $to);
    error_log("Subject: " . $subject);

    $mail = new PHPMailer(true);

    try {
        // Server settings for DreamHost
        $mail->isSMTP();
        $mail->Host = 'smtp.dreamhost.com';
        $mail->SMTPAuth = true;
        $mail->Username = $config['from_email'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom($config['from_email'], 'Palma Miami Beach');
        $mail->addAddress($to);
        $mail->addReplyTo($config['from_email'], 'Palma Miami Beach');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_message;

        // Create plain text version for alt body
        $text_message = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html_message));
        $mail->AltBody = $text_message;

        // Additional headers
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'All');
        $mail->addCustomHeader('X-ContactForm', 'palma.dreamhosters.com');
        $mail->addCustomHeader('X-Lead-Source', 'Website');

        $result = $mail->send();

        if ($result) {
            error_log("FollowUpBoss email sent successfully");
        }

        return $result;
    } catch (Exception $e) {
        error_log("FollowUpBoss email failed: " . $mail->ErrorInfo);
        error_log("Exception: " . $e->getMessage());
        return false;
    }
}

// Check rate limit first
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!check_rate_limit($user_ip, $config)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please try again in an hour.']);
    exit;
}

// Get and validate input
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

error_log("Processing Palma Miami Beach contact form");

$firstName = $input['firstName'] ?? '';
$lastName = $input['lastName'] ?? '';
$email = $input['email'] ?? '';
$phone = $input['phone'] ?? '';
$comments = $input['comments'] ?? '';
$hearAbout = $input['hearAbout'] ?? '';
$realtor = $input['realtor'] ?? 'no';
$buyerBroker = $input['buyerBroker'] ?? 'buyer';
$terms = $input['terms'] ?? false;
$sourceUrl = $_SERVER['HTTP_REFERER'] ?? 'https://palma.dreamhosters.com';

error_log("Form data - Name: '$firstName $lastName', Email: '$email', Phone: '$phone', Hear About: '$hearAbout'");

// Validate all inputs
if (!validate_name($firstName, $config['max_name_length'])) {
    error_log("Invalid first name: " . $firstName);
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid first name (letters, spaces, hyphens, apostrophes only)']);
    exit;
}

if (!validate_name($lastName, $config['max_name_length'])) {
    error_log("Invalid last name: " . $lastName);
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid last name (letters, spaces, hyphens, apostrophes only)']);
    exit;
}

if (!validate_email($email)) {
    error_log("Invalid email: " . $email);
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid email address']);
    exit;
}

if (!validate_phone($phone)) {
    error_log("Invalid phone: " . $phone);
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid phone number']);
    exit;
}

if (!validate_message($comments, $config['max_message_length'])) {
    error_log("Message too long: " . strlen($comments) . " characters");
    http_response_code(400);
    echo json_encode(['error' => 'Message is too long. Please limit to ' . $config['max_message_length'] . ' characters.']);
    exit;
}

if (!$terms) {
    error_log("Terms not accepted");
    http_response_code(400);
    echo json_encode(['error' => 'Please accept the terms and privacy policy']);
    exit;
}

// Sanitize after validation
$firstName = sanitize_input($firstName);
$lastName = sanitize_input($lastName);
$email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
$phone = sanitize_input($phone);
$comments = sanitize_input($comments);
$hearAbout = sanitize_input($hearAbout);

error_log("Validated data - Name: '$firstName $lastName', Email: '$email'");

// Save lead submission for record keeping
$leads_file = $config['data_dir'] . 'palma-leads.json';
$lead_record = [
    'timestamp' => date('Y-m-d H:i:s'),
    'firstName' => $firstName,
    'lastName' => $lastName,
    'email' => $email,
    'phone' => $phone,
    'comments' => $comments,
    'hearAbout' => $hearAbout,
    'realtor' => $realtor,
    'buyerBroker' => $buyerBroker,
    'sourceUrl' => $sourceUrl,
    'ip' => $user_ip,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

try {
    // Create data directory if it doesn't exist
    if (!file_exists($config['data_dir'])) {
        if (!mkdir($config['data_dir'], 0755, true)) {
            error_log("Failed to create data directory");
        }
    }

    // Read existing leads
    $leads = [];
    if (file_exists($leads_file)) {
        $content = file_get_contents($leads_file);
        if ($content !== false) {
            $leads = json_decode($content, true) ?: [];
        }
    }

    $leads[] = $lead_record;

    // Keep only last 1000 submissions to prevent file bloat
    if (count($leads) > 1000) {
        $leads = array_slice($leads, -1000);
    }

    file_put_contents($leads_file, json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    chmod($leads_file, 0644);
} catch (Exception $e) {
    error_log("Failed to save lead record: " . $e->getMessage());
    // Don't fail the form submission if record keeping fails
}

// Create FollowUpBoss formatted email
list($subject, $html_message) = create_followupboss_email(
    $firstName, 
    $lastName, 
    $email, 
    $phone, 
    $comments, 
    $hearAbout, 
    $realtor, 
    $buyerBroker, 
    $user_ip, 
    $sourceUrl
);

error_log("About to send lead to FollowUpBoss: " . $config['followupboss_email']);

if (send_email_to_followupboss($config['followupboss_email'], $subject, $html_message, $config)) {
    error_log("Lead sent to FollowUpBoss successfully");
    echo json_encode(['success' => true, 'message' => 'Thank you for your inquiry! We will contact you soon.']);
} else {
    error_log("Failed to send lead to FollowUpBoss");
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit your inquiry. Please try again or contact us directly.']);
}

error_log("=== PALMA REQUEST COMPLETED ===\n");
?>