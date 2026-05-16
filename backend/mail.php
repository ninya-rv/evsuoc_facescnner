<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendActivationEmail($toEmail, $name)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com';
        $mail->SMTPAuth = true;

        // ✅ IMPORTANT FIX HERE
        $mail->Username = getenv('SMTP_EMAIL');     // MUST be Brevo account email
        $mail->Password = getenv('SMTP_PASSWORD');  // SMTP KEY

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender MUST match Brevo verified sender
        $mail->setFrom(getenv('SMTP_EMAIL'), 'EVSU BSIT System');

        $mail->addAddress($toEmail, $name);

        $mail->isHTML(true);
        $mail->Subject = "Account Activated - EVSU BSIT System";

        $mail->Body = "
        <div style='font-family:Arial;padding:20px'>
            <h2>EVSU System</h2>
            <p>Hello <b>$name</b>, your account is ACTIVATED.</p>
        </div>";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $mail->ErrorInfo);
        return false;
    }
}