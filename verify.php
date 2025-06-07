<?php
header('Content-Type: text/html; charset=UTF-8');
include 'config.php';

$message = '';
$is_success = false;

if (isset($_GET['token'])) {
    $verification_token = $_GET['token'];
    
    // Prepare statement to find user with this token
    $stmt = $conn->prepare("SELECT id, verification_token_expiry FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $verification_token);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        $message = 'Invalid or expired verification link.';
    } else {
        $stmt->bind_result($user_id, $expiry_time);
        $stmt->fetch();
        
        // Check if token is expired
        if (strtotime($expiry_time) < time()) {
            $message = 'Verification link has expired. Please request a new one.';
            
            // Optionally delete the expired token
            $conn->query("UPDATE users SET verification_token = NULL, verification_token_expiry = NULL WHERE id = $user_id");
        } else {
            // Mark user as verified and clear token
            $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, verification_token_expiry = NULL WHERE id = ?");
            $update_stmt->bind_param("i", $user_id);
            
            if ($update_stmt->execute()) {
                $is_success = true;
                $message = 'Email verified successfully! You can now log in.';
            } else {
                $message = 'Verification failed. Please try again.';
            }
        }
    }
} else {
    $message = 'No verification token provided.';
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
        
        <h1><?php echo $is_success ? 'Verification Successful!' : 'Verification Failed'; ?></h1>
        
        <p><?php echo $message; ?></p>
        
        <?php if ($is_success): ?>
            <a href="login.php" class="btn">Go to Login</a>
        <?php else: ?>
            <a href="register.php" class="btn">Try Again</a>
        <?php endif; ?>
    </div>
</body>
</html>