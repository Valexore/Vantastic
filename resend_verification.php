<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
include 'config.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['email'])) {
        $response['message'] = 'Email address is required';
        echo json_encode($response);
        exit;
    }

    $email = trim($_POST['email']);

    // Check if user exists and isn't already verified
    $stmt = $conn->prepare("SELECT id, full_name, verification_token FROM users WHERE email = ? AND is_verified = 0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $response['message'] = 'No unverified account found with this email or email is already verified.';
    } else {
        $stmt->bind_result($user_id, $full_name, $old_token);
        $stmt->fetch();

        // Generate new token and expiry
        $new_token = bin2hex(random_bytes(32));
        $new_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Update the database with new token
        $update_stmt = $conn->prepare("UPDATE users SET verification_token = ?, verification_token_expiry = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $new_token, $new_expiry, $user_id);

        if ($update_stmt->execute()) {
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
                $mail->Subject = 'Verify Your Email Address';

                $verification_link = "https://yourwebsite.com/verify.php?token=$new_token";
                $mail->Body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Email Verification | Van Tastic</title>
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
        .button {
            display: inline-block;
            background-color: #0e386a;
            color: white !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #4a90e2;
        }
        .verification-link {
            word-break: break-all;
            background-color: #f5f7fa;
            padding: 12px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 14px;
            color: #6c757d;
            border: 1px solid #e1e5eb;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e5eb;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
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
            
            <p>Thank you for registering with Van Tastic! To complete your account setup, please verify your email address by clicking the button below:</p>
            
            <p style='text-align: center;'>
                <a href='$verification_link' class='button'>Verify My Email</a>
            </p>
            
            <p>If the button doesn't work, copy and paste this link into your browser:</p>
            <div class='verification-link'>$verification_link</div>
            
            <p class='expiry-notice'>This verification link will expire in 24 hours.</p>
            
            <p>If you didn't create an account with Van Tastic, please ignore this email or contact our support team if you have any concerns.</p>
            
            <div class='footer'>
                <p>© " . date('Y') . " Van Tastic. All rights reserved.</p>
                <p>This is an automated message - please do not reply directly to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
";

                $mail->AltBody = "Hello $full_name,\n\n"
                    . "Thank you for registering with Van Tastic! To complete your account setup, please verify your email address by visiting this link:\n\n"
                    . "$verification_link\n\n"
                    . "This verification link will expire in 24 hours.\n\n"
                    . "If you didn't create an account with Van Tastic, please ignore this email or contact our support team if you have any concerns.\n\n"
                    . "© " . date('Y') . " Van Tastic. All rights reserved.";
                $mail->send();

                $response['success'] = true;
                $response['message'] = 'Verification email resent. Please check your inbox.';
            } catch (Exception $e) {
                $response['message'] = 'Failed to send verification email. Error: ' . $mail->ErrorInfo;
                // Log the error for debugging
                file_put_contents('mail_errors.log', $mail->ErrorInfo . "\n", FILE_APPEND);
            }
        } else {
            $response['message'] = 'Failed to update verification details. Please try again.';
        }
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
