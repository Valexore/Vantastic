<?php
session_start();
ob_start();
include 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Validate input
if (empty($_POST['email'])) {
    $response['message'] = 'Email is required';
    echo json_encode($response);
    exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'] ?? null;
$security_answer = $_POST['security_answer'] ?? null;

// Start database transaction
$conn->begin_transaction();

try {
    // Get user with row lock
    $stmt = $conn->prepare("SELECT id, email, password, role, is_verified, login_attempts, login_lockout, security_answer 
                           FROM users WHERE email = ? FOR UPDATE");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sleep(1); // Prevent timing attacks
        $response['message'] = 'Invalid email or password';
        $conn->rollback();
        echo json_encode($response);
        exit;
    }

    $user = $result->fetch_assoc();
    
    // Check if user is locked out
    if ($user['login_lockout'] && strtotime($user['login_lockout']) > time()) {
        // Check if security answer is provided and correct
        if ($security_answer !== null && !empty($user['security_answer'])) {
            // Compare security answers (case-insensitive)
            $provided_answer = strtolower(trim($security_answer));
            $stored_answer = strtolower(trim($user['security_answer']));
            
            if ($provided_answer === $stored_answer) {
                // Reset attempts and lockout
                $stmt = $conn->prepare("UPDATE users SET login_attempts = 0, login_lockout = NULL WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $conn->commit();
                
                $response['success'] = true;
                $response['message'] = 'Account unlocked. Please login again.';
                echo json_encode($response);
                exit;
            } else {
                $response['message'] = 'Incorrect security answer';
                $conn->rollback();
                echo json_encode($response);
                exit;
            }
        }
        
        // If no security answer provided or incorrect, show lockout message
        $lockout_time = strtotime($user['login_lockout']) - time();
        $minutes = floor($lockout_time / 60);
        $seconds = $lockout_time % 60;
        $response['message'] = "Account locked. Try again in $minutes minutes, $seconds seconds.";
        $response['needs_security_answer'] = true;
        $response['security_question'] = "Who is your first love?";
        $response['locked_email'] = $email;
        $conn->rollback();
        echo json_encode($response);
        exit;
    }

    // If password is not provided but account isn't locked
    if (empty($password)) {
        $response['message'] = 'Password is required';
        $conn->rollback();
        echo json_encode($response);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        $new_attempts = $user['login_attempts'] + 1;
        
        if ($new_attempts >= 3) {
            $lockout_time = date('Y-m-d H:i:s', time() + 7200);
            $stmt = $conn->prepare("UPDATE users SET login_attempts = ?, login_lockout = ? WHERE email = ?");
            $stmt->bind_param("iss", $new_attempts, $lockout_time, $email);
            $response['message'] = 'Account locked for 2 hours. Too many failed attempts. ' . 
                                 'You can answer your security question to unlock immediately.';
            $response['needs_security_answer'] = true;
            $response['security_question'] = "Who is your first love?";
            $response['locked_email'] = $email;
        } else {
            $stmt = $conn->prepare("UPDATE users SET login_attempts = ? WHERE email = ?");
            $stmt->bind_param("is", $new_attempts, $email);
            $remaining = 3 - $new_attempts;
            $response['message'] = "Invalid credentials. $remaining attempts remaining.";
        }
        
        $stmt->execute();
        $conn->commit();
        echo json_encode($response);
        exit;
    }

    // Check email verification
    if (!$user['is_verified']) {
        $response['message'] = 'Please verify your email first. <a href="resend_verification.php?email='.urlencode($email).'">Resend verification</a>';
        $conn->rollback();
        echo json_encode($response);
        exit;
    }

    // Successful login - Set PH time for session only
    $original_timezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Manila');

    $current_time = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE users SET login_attempts = 0, login_lockout = NULL, last_login_session = ? WHERE email = ?");
    $stmt->bind_param("ss", $current_time, $email);
    $stmt->execute();

    session_regenerate_id(true);

    $_SESSION = [
        'user_id' => $user['id'],
        'user_email' => $user['email'],
        'user_role' => $user['role'],
        'last_activity' => time(),
        'login_time' => time(),
        'login_time_ph' => $current_time,
        'ip_address' => $_SERVER['REMOTE_ADDR']
    ];

    date_default_timezone_set($original_timezone);

    $cookieParams = session_get_cookie_params();
    setcookie(
        session_name(),
        session_id(),
        [
            'expires' => time() + 86400,
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );

    $response = [
        'success' => true,
        'message' => 'Login successful',
        'redirect' => match($user['role']) {
            'admin' => 'admin.php',
            'manager' => 'manager.php',
            default => 'customer-dashboard.php'
        }
    ];

    $conn->commit();
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System error. Please try again.']);
    exit;
}
?>