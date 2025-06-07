<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token in database (plain token for verification)
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expiry, $email);
        
        if ($stmt->execute()) {
            // Create reset link
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token . "&email=" . urlencode($email);

            // Send email using PHPMailer
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
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Password Reset Request</title>
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
                        }
                        .email-header img {
                            max-width: 180px;
                        }
                        .email-content {
                            padding: 30px;
                        }
                        h1 {
                            color: #722f37;
                            margin-top: 0;
                            font-size: 24px;
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
                        }
                        .button:hover {
                            background-color: #4a90e2;
                        }
                        .reset-link {
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
                            color: white;
                            font-size: 24px;
                            font-weight: 600;
                            margin-top: 10px;
                            display: block;
                        }
                    </style>
                </head>
                <body>
                    <div class='email-container'>
                        <div class='email-header'>
                            <div class='logo-text'>Van Tastic</div>
                        </div>
                        
                        <div class='email-content'>
                            <h1>Password Reset Request</h1>
                            <p>Hello,</p>
                            <p>We received a request to reset your password for your Van Tastic account. Click the button below to proceed with resetting your password:</p>
                            
                            <p style='text-align: center;'>
                                <a href='$resetLink' class='button'>Reset My Password</a>
                            </p>
                            
                            <p>If the button above doesn't work, copy and paste this link into your browser:</p>
                            <div class='reset-link'>$resetLink</div>
                            
                            <p class='expiry-notice'>This link will expire in 1 hour for security reasons.</p>
                            
                            <p>If you didn't request this password reset, please ignore this email or contact our support team if you have any concerns.</p>
                            
                            <div class='footer'>
                                <p>© ".date('Y')." Van Tastic. All rights reserved.</p>
                                <p>This is an automated message, please do not reply directly to this email.</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $mail->AltBody = "Password Reset Request\n\n"
                    ."Hello,\n\n"
                    ."We received a request to reset your password for your Van Tastic account. Please visit the following link to reset your password:\n\n"
                    .$resetLink."\n\n"
                    ."This link will expire in 1 hour for security reasons.\n\n"
                    ."If you didn't request this password reset, please ignore this email or contact our support team if you have any concerns.\n\n"
                    ."© ".date('Y')." Van Tastic. All rights reserved.";

                    
                $mail->send();
                
                $_SESSION['reset_success'] = "If that email exists, we've sent a reset link.";
                header("Location: forgot-password.php?sent=1");
                exit();
            } catch (Exception $e) {
                $error = "Failed to send email. Please try again later.";
            }
        } else {
            $error = "Error generating reset token. Please try again.";
        }
    } else {
        // Don't reveal if email exists for security
        $_SESSION['reset_success'] = "If that email exists, we've sent a reset link.";
        header("Location: forgot-password.php?sent=1");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Van Terminal System</title>
    <link rel="icon" href="img/knorr.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --wine: #722f37;
            --teal: #0e386a;
            --yilo: #fba002;
            --white: #ffffff;
            --soft-blue: #4a90e2;
            --error-color:rgb(209, 41, 19);
            

            --poppins: 'Poppins', sans-serif;
            --lato: 'Lato', sans-serif;
        }

        body {
            font-family: var(--poppins);
            background-color: #f5f7ff;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-color);
        }

        .password-reset-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }

        .password-reset-header {
            background: linear-gradient(135deg, var(--teal), #6a5acd);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .password-reset-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .password-reset-header i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }

        .password-reset-content {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            width: 93%;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--teal);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--teal);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
        }

        .btn {
            display: inline-block;
            background: var(--teal);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background:rgb(58, 224, 182);
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--teal);
            border-left: 4px solid var(--teal);
        }

        .alert-error {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }

        .reset-link {
            word-break: break-all;
            background: rgba(0, 0, 0, 0.05);
            padding: 10px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 14px;
        }

        .footer-links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .footer-links a {
            color: var(--teal);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        .password-container {
            position: relative;
        }

        .password-container input {
            padding-right: 40px;
        }

        .show-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--teal);
        }

        .brand-logo {
            position: absolute;
            top: 20px;
            left: 20px;
            height: 40px;
        }
    </style>
</head>

<body>
    <div class="password-reset-container">
        <div class="password-reset-header">
            <i class="fas fa-key"></i>
            <h2>Forgot Your Password?</h2>
        </div>

        <div class="password-reset-content">
            <?php if (isset($_GET['sent'])): ?>
                <div class="alert alert-success">
                    <p><strong>Check your email for a password reset link!</strong></p>
                    <p>We've sent an email with instructions to reset your password. Please check your inbox.</p>
                    <p>If you don't see the email, check your spam folder.</p>
                </div>

                <div class="footer-links">
                    <p><a href="index.php"><i class="fas fa-arrow-left"></i> Back to login</a></p>
                </div>
            <?php else: ?>
                <p>Enter your email address and we'll send you a link to reset your password.</p>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>

                    <button type="submit" class="btn">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </button>
                </form>

                <div class="footer-links">
                    <p>Remember your password? <a href="index.php">Sign in</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
        <script src="disableclick.js"></script>

</html>