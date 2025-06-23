<?php
header('Content-Type: application/json');
include 'config.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Check if user exists and is not verified
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_verified = 0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'Email not found or already verified.';
        echo json_encode($response);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Generate new OTP and expiry
    $new_otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $new_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Update user record
    $update_stmt = $conn->prepare("UPDATE users SET verification_token = ?, verification_token_expiry = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $new_otp, $new_expiry, $user['id']);
    
    if ($update_stmt->execute()) {
        // Send email with new OTP
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
            $mail->addAddress($email, $user['full_name']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your New Verification OTP';
            
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>New OTP | Van Tastic</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .otp-code { font-size: 24px; font-weight: bold; letter-spacing: 3px; color: #0e386a; }
                    .footer { margin-top: 30px; font-size: 12px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Your New OTP</h2>
                    <p>Here's your new verification code:</p>
                    <div class='otp-code'>$new_otp</div>
                    <p>This code will expire in 10 minutes.</p>
                    <div class='footer'>
                        <p>If you didn't request this, please ignore this email.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->AltBody = "Your new OTP is: $new_otp\nThis code will expire in 10 minutes.";
            
            $mail->send();
            
            $response['success'] = true;
            $response['message'] = 'New OTP sent to your email.';
        } catch (Exception $e) {
            $response['message'] = 'Failed to send OTP. Please try again.';
        }
    } else {
        $response['message'] = 'Failed to generate new OTP. Please try again.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
?>















