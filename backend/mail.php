<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendActivationEmail($toEmail, $name)
{
    $mail = new PHPMailer(true);

    try {

        // ======================
        // GET ENV VARIABLES
        // ======================
        $smtpEmail = getenv('SMTP_EMAIL');
        $smtpPassword = getenv('SMTP_PASSWORD');

        // ❗ VALIDATION (PREVENT "Invalid From")
        if (!$smtpEmail || !$smtpPassword) {
            throw new Exception("SMTP ENV variables not set in Railway");
        }

        // ======================
        // SMTP CONFIG
        // ======================
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpEmail;
        $mail->Password = $smtpPassword;

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // ❌ TURN OFF DEBUG IN PRODUCTION (VERY IMPORTANT)
        $mail->SMTPDebug = 0;

        // ======================
        // SENDER / RECEIVER
        // ======================
        $mail->setFrom($smtpEmail, 'EVSU BSIT System');
        $mail->addAddress($toEmail, $name);

        // ======================
        // EMAIL CONTENT
        // ======================
        $mail->isHTML(true);
        $mail->Subject = "Account Activated - EVSU BSIT System";

        $mail->Body = "
        <div style='font-family:Arial;padding:20px;background:#f4f4f4'>
            <div style='max-width:600px;margin:auto;background:white;padding:20px;border-radius:10px'>

                <h2 style='color:#800000'>EVSU Face Recognition System</h2>

                <p>Hello <b>$name</b>,</p>

                <p>Your student profile has been <b style='color:green'>ACTIVATED</b>.</p>

                <p>You may now use the system for attendance verification.</p>

                <div style='margin-top:15px;padding:10px;background:#f8f8f8;border-left:5px solid #800000'>
                    <p style='margin:0'><b>Status:</b> Active</p>
                </div>

                <br>
                <p style='font-size:12px;color:gray'>
                    This is an automated message from EVSU System.
                </p>

            </div>
        </div>";

        // ======================
        // SEND EMAIL
        // ======================
        $mail->send();
        return true;

    } catch (Exception $e) {

        // LOG ERROR ONLY (DO NOT ECHO IN RAILWAY)
        error_log("EMAIL ERROR: " . $mail->ErrorInfo);

        return false;
    }
}