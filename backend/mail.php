<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendActivationEmail($toEmail, $name)
{
    $mail = new PHPMailer(true);

    try {

        // ======================
        // SMTP CONFIG (BREVO)
        // ======================
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_EMAIL');   // Brevo login email
        $mail->Password = getenv('SMTP_PASSWORD'); // Brevo SMTP KEY

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('SMTP_PORT') ?: 587;

        // OPTIONAL DEBUG (REMOVE AFTER TEST)
        // $mail->SMTPDebug = 2;

        // ======================
        // SENDER
        // ======================
        $mail->setFrom(getenv('SMTP_EMAIL'), 'EVSU BSIT System');

        // RECEIVER
        $mail->addAddress($toEmail, $name);

        // ======================
        // CONTENT
        // ======================
        $mail->isHTML(true);
        $mail->Subject = "Account Activated - EVSU BSIT System";

        $mail->Body = "
        <div style='font-family:Arial;padding:20px;background:#f4f4f4'>
            <div style='max-width:600px;margin:auto;background:white;padding:20px;border-radius:10px'>

                <h2 style='color:#800000'>EVSU Face Recognition System</h2>

                <p>Hello <b>$name</b>,</p>

                <p>Your student account is now <b style='color:green'>ACTIVATED</b>.</p>

                <p>You can now use the system for attendance.</p>

                <p><b>Status:</b> Active</p>

                <br>
                <p style='font-size:12px;color:gray'>
                    Automated message from EVSU System.
                </p>

            </div>
        </div>";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $mail->ErrorInfo);
        return false;
    }
}