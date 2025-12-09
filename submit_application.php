<?php
// Apply Now form submission handler
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once 'config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$full_name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$mobile = trim($input['mobile'] ?? '');
$course = trim($input['course'] ?? '');
$consent = isset($input['consent']) && $input['consent'] ? 1 : 0;

// Validate required fields
if (empty($full_name) || empty($email) || empty($mobile) || empty($course)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate mobile (exactly 10 digits)
if (!preg_match('/^[0-9]{10}$/', $mobile)) {
    echo json_encode(['success' => false, 'message' => 'Mobile number must be exactly 10 digits']);
    exit;
}

// OTP verification disabled - will enable when SMS gateway is purchased
// if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
//     echo json_encode(['success' => false, 'message' => 'Please verify your phone number with OTP']);
//     exit;
// }
// 
// if (!isset($_SESSION['verified_phone']) || $_SESSION['verified_phone'] !== $mobile) {
//     echo json_encode(['success' => false, 'message' => 'Phone number verification mismatch']);
//     exit;
// }

// Connect to database
$conn = getDBConnection();

// Set form type
$form_type = 'application';

// Prepare and bind
$stmt = $conn->prepare("INSERT INTO leads (full_name, email, phone, course, form_type, consent) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssi", $full_name, $email, $mobile, $course, $form_type, $consent);

// Execute query
if ($stmt->execute()) {
    // Clear OTP verification from session after successful submission
    unset($_SESSION['otp_verified'], $_SESSION['verified_phone']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! We will contact you soon.',
        'lead_id' => $conn->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error saving data. Please try again.'
    ]);
}

$stmt->close();
closeDBConnection($conn);
?>
