<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendActivationEmail($toEmail, $name) {

    $mail = new PHPMailer(true);

    try {
        // SMTP CONFIG
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // ⚠️ Gmail account
        $mail->Username = 'aprilsheen.pinar@evsu.edu.ph';
        $mail->Password = 'wssp qafe fxlb xczw';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // SENDER
        $mail->setFrom('evsuoccadmin@gmail.com', 'EVSU BSIT System');
        $mail->addAddress($toEmail, $name);

        $mail->isHTML(true);
        $mail->Subject = "Account Activated - EVSU BSIT System";
        $mail->Body = "
        <div style='font-family:Arial;padding:20px;background:#f4f4f4'>
            <div style='max-width:600px;margin:auto;background:white;padding:20px;border-radius:10px'>

                <h2 style='color:#800000'>EVSU Face Recognition System</h2>

                <p>Hello <b>$name</b>,</p>

                <p>Your student profile has been <b style='color:green'>ACTIVATED</b> by the administrator.</p>

                <p>You may now use your face for attendance verification.</p>

                <p><b>Instruction:</b> Please proceed to your instructor and scan your face for attendance recording.</p>

                <div style='margin-top:15px;padding:10px;background:#f8f8f8;border-left:5px solid #800000'>
                    <p style='margin:0'><b>Status:</b> Active</p>
                </div>

                <br>

                <p style='font-size:12px;color:gray'>
                    This is an automated message from EVSU Face Recognition Attendance System.
                </p>

            </div>
        </div>";

        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}