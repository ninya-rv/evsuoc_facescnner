<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendActivationEmail($toEmail, $name) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // ✅ FIX: use ENV instead of hardcoded credentials
        $mail->Username = getenv('SMTP_EMAIL');
        $mail->Password = getenv('SMTP_PASSWORD');

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Sender (must match Gmail SMTP account)
        $mail->setFrom(getenv('SMTP_EMAIL'), 'EVSU BSIT System');

        $mail->addAddress($toEmail, $name);

        $mail->isHTML(true);
        $mail->Subject = "Account Activated - EVSU BSIT System";

        $mail->Body = "
        <div style='font-family:Arial;padding:20px;background:#f4f4f4'>
            <div style='max-width:600px;margin:auto;background:white;padding:20px;border-radius:10px'>

                <h2 style='color:#800000'>EVSU Face Recognition System</h2>

                <p>Hello <b>$name</b>,</p>

                <p>Your student profile has been <b style='color:green'>ACTIVATED</b>.</p>

                <p>You may now use the system for attendance.</p>

                <div style='margin-top:15px;padding:10px;background:#f8f8f8;border-left:5px solid #800000'>
                    <p style='margin:0'><b>Status:</b> Active</p>
                </div>

                <br>
                <p style='font-size:12px;color:gray'>
                    This is an automated message from EVSU System.
                </p>

            </div>
        </div>";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}