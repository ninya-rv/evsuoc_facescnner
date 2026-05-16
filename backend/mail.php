<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendActivationEmail($toEmail, $name)
{
    $mail = new PHPMailer(true);

    try {
        // ======================
        // SMTP CONFIG
        // ======================
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com';
        $mail->SMTPAuth = true;

        // 🔥 IMPORTANT: environment variables
        $mail->Username = getenv('SMTP_EMAIL');      // Brevo login email
        $mail->Password = getenv('SMTP_PASSWORD');   // SMTP KEY (NOT API KEY)

        // ======================
        // FIX: Better encryption handling
        // ======================
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // ======================
        // OPTIONAL DEBUG (ENABLE DURING TESTING)
        // ======================
        $mail->SMTPDebug = 2; // change to 0 in production
        $mail->Debugoutput = 'error_log';

        // ======================
        // SENDER (must be verified in Brevo)
        // ======================
        $mail->setFrom(getenv('SMTP_EMAIL'), 'EVSU BSIT System');

        $mail->addAddress($toEmail, $name);

        // ======================
        // EMAIL CONTENT
        // ======================
        $mail->isHTML(true);
        $mail->Subject = "Account Activated - EVSU BSIT System";

        $mail->Body = "
        <div style='font-family:Arial;padding:20px'>
            <h2>EVSU System</h2>
            <p>Hello <b>{$name}</b>, your account is ACTIVATED.</p>
        </div>";

        // ======================
        // SEND
        // ======================
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $mail->ErrorInfo);
        error_log("EXCEPTION: " . $e->getMessage());
        return false;
    }
}