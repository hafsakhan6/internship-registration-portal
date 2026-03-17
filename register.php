<?php
// register.php  – PART B: Server-side validation & registration
header('Content-Type: application/json');
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// ── Helper: sanitize output to prevent XSS ──────────────────────────────────
function clean($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

$errors = [];

// ── 1. Collect & trim inputs ─────────────────────────────────────────────────
$student_id = trim($_POST['student_id'] ?? '');
$full_name  = trim($_POST['full_name']  ?? '');
$email      = trim($_POST['email']      ?? '');
$password   = $_POST['password']        ?? '';
$confirm_pw = $_POST['confirm_password'] ?? '';
$cnic       = trim($_POST['cnic']       ?? '');
$phone      = trim($_POST['phone']      ?? '');
$cgpa       = trim($_POST['cgpa']       ?? '');
$department = trim($_POST['department'] ?? '');

// ── 2. Re-validate everything (Part B #1) ────────────────────────────────────

// Student ID
if (!preg_match('/^[A-Z]{2}\d{2}-[A-Z]{3}-\d{3}$/', $student_id)) {
    $errors[] = 'Invalid Student ID format (e.g. FA21-BCS-001).';
}

// Full name
if (empty($full_name) || strlen($full_name) < 3) {
    $errors[] = 'Full name must be at least 3 characters.';
}

// Email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address.';
}

// Password strength
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
    $errors[] = 'Password must be 8+ chars with uppercase, lowercase, number & special character.';
}

// Confirm password
if ($password !== $confirm_pw) {
    $errors[] = 'Passwords do not match.';
}

// CNIC
if (!preg_match('/^\d{5}-\d{7}-\d$/', $cnic)) {
    $errors[] = 'Invalid CNIC format (e.g. 12345-1234567-1).';
}

// Phone
if (!preg_match('/^03\d{9}$/', $phone)) {
    $errors[] = 'Invalid phone number (format: 03XXXXXXXXX).';
}

// CGPA
$cgpa_float = floatval($cgpa);
if (!is_numeric($cgpa) || $cgpa_float < 0.00 || $cgpa_float > 4.00) {
    $errors[] = 'CGPA must be between 0.00 and 4.00.';
}

// Department
if (empty($department)) {
    $errors[] = 'Department is required.';
}

// ── 3. File Upload Validation (Part B #4) ────────────────────────────────────
$resume_path = '';

if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Resume upload failed or missing.';
} else {
    $file      = $_FILES['resume'];
    $file_tmp  = $file['tmp_name'];
    $file_size = $file['size'];
    $file_name = basename($file['name']);

    // a) Check real MIME type using finfo (not just extension) – prevents .php renamed as .pdf
    $finfo     = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);

    if ($mime_type !== 'application/pdf') {
        $errors[] = 'Resume must be a real PDF file. Renaming a .php file as .pdf is detected and blocked.';
    }

    // b) Size limit: 2 MB
    if ($file_size > 2 * 1024 * 1024) {
        $errors[] = 'Resume must be smaller than 2MB.';
    }

    // c) Sanitize filename & generate unique name
    $safe_name   = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file_name);
    $unique_name = uniqid('resume_', true) . '_' . $safe_name;
    $upload_dir  = __DIR__ . '/uploads/resumes/';   // directory OUTSIDE web root is even better

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $resume_path = $upload_dir . $unique_name;

    if (!move_uploaded_file($file_tmp, $resume_path)) {
        $errors[] = 'Failed to save resume. Please try again.';
    }
}

// Return early if any errors
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ── 4. Check duplicates (Part B #7) ──────────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? OR student_id = ? LIMIT 1");
$stmt->execute([$email, $student_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'errors' => ['Email or Student ID already registered.']]);
    exit;
}

// ── 5. Hash password (Part B #3) ─────────────────────────────────────────────
$hashed_pw = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// ── 6. Insert with prepared statement (Part B #2) ────────────────────────────
// Relative path stored, not absolute (Part B #6)
$relative_path = 'uploads/resumes/' . basename($resume_path);

try {
    $stmt = $pdo->prepare("
        INSERT INTO students (student_id, full_name, email, password, cnic, phone, cgpa, department, resume_path)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $student_id,
        $full_name,
        $email,
        $hashed_pw,
        $cnic,
        $phone,
        $cgpa_float,
        $department,
        $relative_path
    ]);

    echo json_encode(['success' => true, 'message' => 'Registration successful! Welcome, ' . clean($full_name)]);

} catch (PDOException $e) {
    // Log the real error, show generic message
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'errors' => ['Registration failed. Please try again.']]);
}
?>