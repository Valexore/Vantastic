<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

include 'config.php';

$response = ['success' => false, 'message' => ''];

// Handle OTP verification if it's being submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['otp'])) {
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);
    
    // Validate OTP (6 digits)
    if (strlen($otp) !== 6 || !ctype_digit($otp)) {
        $response['message'] = 'Invalid OTP format';
        echo json_encode($response);
        exit;
    }
    
    // Check if OTP matches and is not expired
    $current_time = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT id, verification_token FROM users WHERE email = ? AND verification_token_expiry > ?");
    $stmt->bind_param("ss", $email, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if ($user['verification_token'] === $otp) {
            // OTP is valid - mark user as verified
            $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, verification_token_expiry = NULL WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            
            if ($update_stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Verification successful! Your account is now active.';
            } else {
                $response['message'] = 'Error updating verification status';
            }
        } else {
            $response['message'] = 'Invalid OTP code';
        }
    } else {
        $response['message'] = 'OTP expired or invalid. Please request a new one.';
    }
    
    echo json_encode($response);
    exit;
}

// Handle registration if it's being submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['otp'])) {
    // Verify all required fields exist
    $required_fields = ['full_name', 'email', 'password', 'confirm_password', 'security_answer'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $response['message'] = 'Error: All fields are required';
            echo json_encode($response);
            exit;
        }
    }

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $security_answer = $_POST['security_answer'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Error: Invalid email format';
        echo json_encode($response);
        exit;
    }

    // Validate full name (letters and spaces only)
    if (!preg_match('/^[a-zA-Z ]+$/', $full_name)) {
        $response['message'] = 'Error: Full name can only contain letters and spaces';
        echo json_encode($response);
        exit;
    }

    // Validate password strength
    if (strlen($password) < 8) {
        $response['message'] = 'Error: Password must be at least 8 characters long';
        echo json_encode($response);
        exit;
    }

    if ($password !== $confirm_password) {
        $response['message'] = 'Error: Passwords do not match';
        echo json_encode($response);
        exit;
    }

    // Check if email or full name already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR full_name = ?");
    $stmt->bind_param("ss", $email, $full_name);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        // Check which one exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $response['message'] = 'Error: Email already exists!';
        } else {
            $response['message'] = 'Error: Username already registered!';
        }
        echo json_encode($response);
        exit;
    }

    // Generate 6-digit OTP and expiry (10 minutes from now)
    $otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $verification_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $hashed_security_answer = password_hash($security_answer, PASSWORD_DEFAULT);
    
    // Insert new user (initially unverified)
    $insert_stmt = $conn->prepare("INSERT INTO users (full_name, email, password, security_answer, verification_token, verification_token_expiry, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
    $insert_stmt->bind_param("ssssss", $full_name, $email, $hashed_password, $hashed_security_answer, $otp, $verification_expiry);
    
    if ($insert_stmt->execute()) {
        $user_id = $insert_stmt->insert_id;
        
        // Send verification email using PHPMailer
        require_once 'PHPMailer/src/Exception.php';
        require_once 'PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/src/SMTP.php';
        
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'van.tastic.vt0@gmail.com';
            $mail->Password   = 'tkxxzhjghozjjcvl';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            // Recipients
            $mail->setFrom('van.tastic.vt0@gmail.com', 'Van Tastic');
            $mail->addAddress($email, $full_name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Verification OTP';
            
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>OTP Verification | Van Tastic</title>
                <style>
                    body {
                        font-family: 'Poppins', sans-serif;
                        background-color: #f5f7fa;
                        margin: 0;
                        padding: 0;
                        color: #0e386a;
                        line-height: 1.6;
                    }
                    .email-container {
                        max-width: 600px;
                        margin: 0 auto;
                        background-color: #ffffff;
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                    }
                    .email-header {
                        background-color: #0e386a;
                        padding: 30px;
                        text-align: center;
                        color: white;
                    }
                    .email-content {
                        padding: 30px;
                    }
                    h1 {
                        color: #722f37;
                        margin-top: 0;
                        font-size: 24px;
                    }
                    .welcome-text {
                        font-size: 18px;
                        margin-bottom: 20px;
                    }
                    .otp-code {
                        font-size: 32px;
                        letter-spacing: 5px;
                        color: #0e386a;
                        font-weight: bold;
                        text-align: center;
                        margin: 20px 0;
                        padding: 15px;
                        background-color: #f5f7fa;
                        border-radius: 6px;
                        border: 1px dashed #0e386a;
                    }
                    .expiry-notice {
                        color: #722f37;
                        font-weight: 600;
                    }
                    .logo-text {
                        font-size: 24px;
                        font-weight: 600;
                        margin-bottom: 10px;
                    }
                    .user-name {
                        font-weight: 600;
                        color: #0e386a;
                    }
                    .footer {
                        margin-top: 30px;
                        padding-top: 20px;
                        border-top: 1px solid #e1e5eb;
                        font-size: 12px;
                        color: #6c757d;
                        text-align: center;
                    }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='email-header'>
                        <div class='logo-text'>Van Tastic</div>
                        <div>Account Verification</div>
                    </div>
                    
                    <div class='email-content'>
                        <h1>Complete Your Registration</h1>
                        
                        <p class='welcome-text'>Hello <span class='user-name'>$full_name</span>,</p>
                        
                        <p>Thank you for registering with Van Tastic! To complete your account setup, please use the following OTP code:</p>
                        
                        <div class='otp-code'>$otp</div>
                        
                        <p class='expiry-notice'>This OTP will expire in 10 minutes.</p>
                        
                        <p>If you didn't create an account with Van Tastic, please ignore this email or contact our support team if you have any concerns.</p>
                        
                        <div class='footer'>
                            <p>© ".date('Y')." Van Tastic. All rights reserved.</p>
                            <p>This is an automated message - please do not reply directly to this email.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->AltBody = "Hello $full_name,\n\n"
                ."Thank you for registering with Van Tastic! To complete your account setup, please use the following OTP code:\n\n"
                ."OTP: $otp\n\n"
                ."This OTP will expire in 10 minutes.\n\n"
                ."If you didn't create an account with Van Tastic, please ignore this email.\n\n"
                ."© ".date('Y')." Van Tastic. All rights reserved.";
            $mail->send();
            
            $response['success'] = true;
            $response['message'] = 'Registration successful! Please check your email for the 6-digit verification code.';
        } catch (Exception $e) {
            $response['message'] = 'Registration successful but OTP email could not be sent. Please contact support.';
        }
    } else {
        $response['message'] = 'Error: Registration failed. Please try again.';
    }
} else {
    $response['message'] = 'Error: Invalid request method';
}

echo json_encode($response);