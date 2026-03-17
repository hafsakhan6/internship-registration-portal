<?php
// check_email.php  – AJAX endpoint (Part A #8 + Part B)
header('Content-Type: application/json');
require 'config.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['available' => false, 'message' => 'Invalid request.']);
    exit;
}

$email = trim($_POST['email'] ?? '');

// Server-side format check
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['available' => false, 'message' => 'Invalid email format.']);
    exit;
}

// Prepared statement – prevents SQL Injection
$stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    echo json_encode(['available' => false, 'message' => 'Email already registered.']);
} else {
    echo json_encode(['available' => true,  'message' => 'Email is available.']);
}
?>