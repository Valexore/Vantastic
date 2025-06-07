<?
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($email, $full_name, $verification_token) {
    require '/PHPMailer/src/Exception.php';
    require '/PHPMailer/src/PHPMailer.php';
    require '/PHPMailer/src/SMTP.php';

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
        $mail->setFrom('van.tastic.vt0@gmail.com', 'Your Website Name');
        $mail->addAddress($email, $full_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email Address';
        
        $verification_link = "https://yourwebsite.com/verify.php?token=$verification_token";
        $mail->Body    = "
            <h2>Hello $full_name,</h2>
            <p>Please click the following link to verify your email address:</p>
            <p><a href='$verification_link'>Verify Email</a></p>
            <p>This link will expire in 24 hours.</p>
        ";
        $mail->AltBody = "Hello $full_name,\n\nPlease click the following link to verify your email address:\n\n$verification_link\n\nThis link will expire in 24 hours.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

//                               ONLY A TEMPLATE FOR GMAIL
//             NON - Useable Code



?>


