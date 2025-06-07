<?php
session_start();
ob_start();
include 'config.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $response['message'] = 'Both email and password are required';
        echo json_encode($response);
        exit;
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, email, password, role, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Delay response to prevent timing attacks
        sleep(1);
        $response['message'] = 'Invalid email or password';
        echo json_encode($response);
        exit;
    }

    $user = $result->fetch_assoc();
    
    if (!password_verify($password, $user['password'])) {
        // Delay response to prevent timing attacks
        sleep(1);
        $response['message'] = 'Invalid email or password';
        echo json_encode($response);
        exit;
    }

    // Check if email is verified
    if (!$user['is_verified']) {
        $response['message'] = 'Please verify your email address before logging in. <a href="resend_verification.php?email='.urlencode($user['email']).'">Resend verification email</a>';
        echo json_encode($response);
        exit;
    }

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    
    // Set secure session cookie parameters
    $cookieParams = session_get_cookie_params();
    setcookie(
        session_name(),
        session_id(),
        [
            'expires' => time() + 86400, // 1 day
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => true, // Only send over HTTPS
            'httponly' => true, // Prevent JavaScript access
            'samesite' => 'Strict' // Prevent CSRF
        ]
    );

    $response['success'] = true;
    $response['message'] = 'Login successful';
    
    // Determine redirect based on role
    switch($user['role']) {
        case 'admin':
            $response['redirect'] = 'admin.php';
            break;
        case 'manager':
            $response['redirect'] = 'manager.php';
            break;
        default:
            $response['redirect'] = 'customer-dashboard.php';
    }

    echo json_encode($response);
    exit;
} else {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}
?>