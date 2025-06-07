<?php
session_start();
include 'config.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$error = '';
$success = '';

// Verify token and email
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT id, reset_token_expiry FROM users WHERE reset_token = ? AND email = ?");
    $stmt->bind_param("ss", $token, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        $error = "Invalid or expired password reset link.";
    } elseif (strtotime($user['reset_token_expiry']) < time()) {
        $error = "This password reset link has expired. Please request a new one.";
    }
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = "Both password fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords don't match!";
    } elseif (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        $error = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
    } else {
        // Update password and clear reset token
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $null = null;
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = ?, reset_token_expiry = ? WHERE reset_token = ? AND email = ?");
        $stmt->bind_param("sssss", $hashedPassword, $null, $null, $token, $email);
        
        if ($stmt->execute()) {
            $success = "Password updated successfully! You can now <a href='index.php'>login</a> with your new password.";
        } else {
            $error = "Error updating password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | Van Terminal System</title>
  <link rel="icon" href="img/knorr.png" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    :root {
      --wine: #722f37;
      --teal: #0e386a;
      --yilo: #fba002;
      --white: #ffffff;
      --soft-blue: #4a90e2;
      --error-color: rgb(209, 41, 19);
      --success-color: #28a745;
      --poppins: 'Poppins', sans-serif;
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
    }

    .password-reset-container {
      width: 100%;
      max-width: 500px;
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .password-reset-header {
      background: linear-gradient(135deg, var(--teal), #6a5acd);
      color: white;
      padding: 30px;
      text-align: center;
    }

    .password-reset-header i {
      font-size: 48px;
      margin-bottom: 15px;
      display: block;
    }

    .password-reset-header h2 {
      margin: 0;
      font-size: 24px;
      font-weight: 600;
    }

    .password-reset-content {
      padding: 30px;
    }

    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 8px;
      font-size: 14px;
    }

    .alert-error {
      background-color: rgba(220, 53, 69, 0.1);
      color: var(--error-color);
      border-left: 4px solid var(--error-color);
    }

    .alert-success {
      background-color: rgba(40, 167, 69, 0.1);
      color: var(--success-color);
      border-left: 4px solid var(--success-color);
    }

    .form-group {
      margin-bottom: 20px;
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
      background: #0a2a4a;
      transform: translateY(-2px);
    }

    .btn:active {
      transform: translateY(0);
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

    .password-strength {
      margin-top: 5px;
      font-size: 12px;
      color: #6c757d;
    }

    .password-strength.weak {
      color: var(--error-color);
    }

    .password-strength.medium {
      color: #ffc107;
    }

    .password-strength.strong {
      color: var(--success-color);
    }
  </style>
</head>

<body>
  <div class="password-reset-container">
    <div class="password-reset-header">
      <i class="fas fa-key"></i>
      <h2>Reset Your Password</h2>
    </div>

    <div class="password-reset-content">
      <?php if ($error): ?>
        <div class="alert alert-error">
          <?= htmlspecialchars($error) ?>
          <?php if (strpos($error, 'Invalid or expired') !== false || strpos($error, 'expired') !== false): ?>
            <p style="margin-top: 10px;"><a href="forgot-password.php" style="color: var(--teal); font-weight: 600;">Request new reset link</a></p>
          <?php endif; ?>
        </div>
      <?php elseif ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php elseif (!empty($token) && empty($error)): ?>
        <form method="post" id="resetForm">
          <div class="form-group">
            <label for="password">New Password</label>
            <div class="password-container">
              <input type="password" id="password" name="password" class="form-control" 
                     placeholder="At least 8 characters" required minlength="8">
              <span class="show-password" onclick="togglePassword('password')">
                <i class="fas fa-eye"></i>
              </span>
            </div>
            <div id="password-strength" class="password-strength"></div>
          </div>
          
          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="password-container">
              <input type="password" id="confirm_password" name="confirm_password" 
                     class="form-control" placeholder="Confirm your password" required minlength="8">
              <span class="show-password" onclick="togglePassword('confirm_password')">
                <i class="fas fa-eye"></i>
              </span>
            </div>
            <div id="password-match" style="font-size: 12px; margin-top: 5px;"></div>
          </div>
          
          <button type="submit" class="btn" id="submitBtn">Reset Password</button>
        </form>
      <?php else: ?>
        <div class="alert alert-error">
          Invalid password reset request. Please use the link from your email.
        </div>
        <div class="footer-links">
          <p><a href="forgot-password.php">Request new reset link</a></p>
        </div>
      <?php endif; ?>

      <div class="footer-links">
        <p><a href="index.php"><i class="fas fa-arrow-left"></i> Back to login</a></p>
      </div>
    </div>
  </div>

  <script>
    function togglePassword(inputId) {
      const input = document.getElementById(inputId);
      const icon = input.nextElementSibling.querySelector('i');
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }

    // Password strength indicator
    document.getElementById('password').addEventListener('input', function() {
      const password = this.value;
      const strengthIndicator = document.getElementById('password-strength');
      
      if (password.length === 0) {
        strengthIndicator.textContent = '';
        strengthIndicator.className = 'password-strength';
        return;
      }
      
      if (password.length < 8) {
        strengthIndicator.textContent = 'Weak (too short)';
        strengthIndicator.className = 'password-strength weak';
        return;
      }
      
      // Check for character diversity
      const hasUpper = /[A-Z]/.test(password);
      const hasLower = /[a-z]/.test(password);
      const hasNumber = /[0-9]/.test(password);
      const hasSpecial = /[^A-Za-z0-9]/.test(password);
      
      let strength = 0;
      if (hasUpper) strength++;
      if (hasLower) strength++;
      if (hasNumber) strength++;
      if (hasSpecial) strength++;
      
      if (strength < 2) {
        strengthIndicator.textContent = 'Weak';
        strengthIndicator.className = 'password-strength weak';
      } else if (strength < 4) {
        strengthIndicator.textContent = 'Medium';
        strengthIndicator.className = 'password-strength medium';
      } else {
        strengthIndicator.textContent = 'Strong';
        strengthIndicator.className = 'password-strength strong';
      }
    });

    // Password match indicator
    document.getElementById('confirm_password').addEventListener('input', function() {
      const password = document.getElementById('password').value;
      const confirmPassword = this.value;
      const matchIndicator = document.getElementById('password-match');
      
      if (confirmPassword.length === 0) {
        matchIndicator.textContent = '';
        return;
      }
      
      if (password === confirmPassword) {
        matchIndicator.textContent = 'Passwords match!';
        matchIndicator.style.color = 'var(--success-color)';
      } else {
        matchIndicator.textContent = 'Passwords do not match';
        matchIndicator.style.color = 'var(--error-color)';
      }
    });
  </script>
          <script src="disableclick.js"></script>

</body>
</html>