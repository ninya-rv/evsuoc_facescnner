<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendActivationEmail($toEmail, $name)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';

        $mail->SMTPAuth = true;

        $mail->Username = 'maxp16666@gmail.com';

        $mail->Password = 'bscptepcajdjjmmq';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;

        $mail->SMTPDebug = 2;

        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP DEBUG: $str");
        };

        $mail->setFrom(
            'maxp16666@gmail.com',
            'EVSU BSIT System'
        );

        $mail->addAddress($toEmail, $name);

        $mail->isHTML(true);

        $mail->Subject = "Test Email";

        $mail->Body = "Test message from Railway.";

        $mail->send();

        error_log("EMAIL SENT SUCCESSFULLY");

        return true;

    } catch (Exception $e) {

        error_log("MAIL ERROR: " . $mail->ErrorInfo);

        return false;
    }
}