<?php
header('Content-Type: text/html; charset=UTF-8');
include 'config.php';

$message = '';
$is_success = false;
$show_otp_form = false;
$email = '';

// Handle OTP verification form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    if (empty($_POST['email']) || empty($_POST['otp'])) {
        $message = 'Both email and OTP are required.';
    } else {
        $email = trim($_POST['email']);
        $otp = trim($_POST['otp']);

        // Check if user exists with this email and OTP
        $stmt = $conn->prepare("SELECT id, verification_token, verification_token_expiry FROM users WHERE email = ? AND is_verified = 0");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $message = 'Email not found or account already verified.';
        } else {
            $user = $result->fetch_assoc();
            
            // Check if OTP matches and is not expired
            if ($user['verification_token'] !== $otp) {
                $message = 'Invalid OTP code.';
            } elseif (strtotime($user['verification_token_expiry']) < time()) {
                $message = 'OTP has expired. Please request a new one.';
                
                // Clear expired OTP
                $conn->query("UPDATE users SET verification_token = NULL, verification_token_expiry = NULL WHERE id = {$user['id']}");
            } else {
                // Verify the user
                $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, verification_token_expiry = NULL WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                
                if ($update_stmt->execute()) {
                    $is_success = true;
                    $message = 'Email verified successfully! You can now log in.';
                } else {
                    $message = 'Verification failed. Please try again.';
                }
            }
        }
    }
} 
// Handle case when user comes from registration (show OTP form)
elseif (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    $show_otp_form = true;
    $message = 'Please enter the 6-digit OTP sent to your email.';
}
// Handle case when someone accesses verify.php directly
else {
    $message = 'Please complete the registration process first.';
    $show_otp_form = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --teal: #0e386a;
            --white: #ffffff;
            --soft-blue: #4a90e2;
            --glass-blur: 8px;
            --poppins: 'Poppins', sans-serif;
            --lato: 'Lato', sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--lato);
            background: linear-gradient(135deg, var(--teal), var(--soft-blue));
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .verification-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .verification-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .verification-icon svg {
            width: 40px;
            height: 40px;
        }
        
        h1 {
            font-family: var(--poppins);
            color: var(--white);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        p {
            color: var(--white);
            margin-bottom: 30px;
            font-size: 18px;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--white);
            color: var(--teal);
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-family: var(--poppins);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .success {
            color: #4ade80;
        }
        
        .error {
            color: #f87171;
        }
        
        /* OTP Form Styles */
        .otp-form {
            display: <?php echo $show_otp_form && !$is_success ? 'block' : 'none'; ?>;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        label {
            display: block;
            color: var(--white);
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        input[type="email"],
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--white);
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input[type="email"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: var(--white);
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .otp-input {
            letter-spacing: 5px;
            font-size: 24px;
            text-align: center;
            font-weight: bold;
        }
        
        .resend-link {
            color: var(--white);
            text-decoration: underline;
            cursor: pointer;
            display: inline-block;
            margin-top: 15px;
        }
        
        .resend-link:hover {
            color: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-icon">
            <?php if ($is_success): ?>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" stroke="#4ade80" />
                </svg>
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="#f87171" />
                </svg>
            <?php endif; ?>
        </div>
        
        <h1><?php echo $is_success ? 'Verification Successful!' : ($show_otp_form ? 'Verify Your Email' : 'Verification Failed'); ?></h1>
        
        <p><?php echo $message; ?></p>
        
        <!-- OTP Verification Form -->
        <form method="POST" action="verify.php" class="otp-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required readonly>
            </div>
            <div class="form-group">
                <label for="otp">6-digit OTP</label>
                <input type="text" id="otp" name="otp" class="otp-input" maxlength="6" pattern="\d{6}" title="Please enter exactly 6 digits" required>
            </div>
            <button type="submit" name="verify_otp" class="btn">Verify OTP</button>
            <a href="resend_otp.php?email=<?php echo urlencode($email); ?>" class="resend-link">Resend OTP</a>
        </form>
        
        <?php if ($is_success): ?>
            <a href="login.php" class="btn">Go to Login</a>
        <?php elseif (!$show_otp_form): ?>
            <a href="register.php" class="btn">Try Again</a>
        <?php endif; ?>
    </div>
</body>
</html>