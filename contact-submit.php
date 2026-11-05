<?php
/**
 * Customer Inquiry / Pre-Sale Lead Form Submission Handler
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$name            = trim($_POST['name'] ?? '');
$email           = trim($_POST['email'] ?? '');
$phone           = trim($_POST['phone'] ?? '');
$productInterest = trim($_POST['product_interest'] ?? '');
$subject         = trim($_POST['subject'] ?? 'Pre-Sale Product Inquiry');
$message         = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill in your name, email address, and message.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO inquiries (name, email, phone, product_interest, subject, message, status)
        VALUES (?, ?, ?, ?, ?, ?, 'Unread')
    ");
    $stmt->execute([
        substr($name, 0, 120),
        substr($email, 0, 150),
        substr($phone, 0, 40),
        substr($productInterest, 0, 180),
        substr($subject, 0, 200),
        $message
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your inquiry has been received. Our team will respond shortly.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to submit your inquiry right now. Please try again.'
    ]);
}

