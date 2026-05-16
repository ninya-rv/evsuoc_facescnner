<?php

require __DIR__ . '/../vendor/autoload.php';

use Resend\Resend;

function sendActivationEmail($toEmail, $name)
{
    try {

        // GET API KEY FROM RAILWAY
        $resend = Resend::client(getenv('RESEND_API_KEY'));

        $safeName = htmlspecialchars($name);

        $resend->emails->send([
            'from' => 'onboarding@resend.dev',
            'to' => [$toEmail],
            'subject' => 'Account Activated - EVSU BSIT System',
            'html' => "
                <div style='font-family:Arial;padding:20px;background:#f4f4f4'>
                    <div style='max-width:600px;margin:auto;background:white;padding:20px;border-radius:10px'>
                        <h2 style='color:#800000'>EVSU Face Recognition System</h2>

                        <p>Hello <b>{$safeName}</b>,</p>

                        <p>Your student account has been <b style='color:green'>ACTIVATED</b>.</p>

                        <p>You may now use your face for attendance.</p>

                        <p style='font-size:12px;color:gray'>
                            EVSU Face Recognition Attendance System
                        </p>
                    </div>
                </div>
            "
        ]);

        return true;

    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $e->getMessage());
        return false;
    }
}