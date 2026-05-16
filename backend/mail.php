<?php

require __DIR__ . '/../vendor/autoload.php';

use Resend\Resend;

function sendActivationEmail($toEmail, $name)
{
    try {

        // CREATE CLIENT PROPERLY (IMPORTANT FIX)
        $resend = Resend::client(getenv('RESEND_API_KEY'));

        $safeName = htmlspecialchars($name);

        $resend->emails->send([
            'from' => 'onboarding@resend.dev',
            'to' => [$toEmail],
            'subject' => 'Account Activated - EVSU BSIT System',
            'html' => "
                <div style='font-family:Arial;padding:20px'>
                    <h2>EVSU Face Recognition System</h2>
                    <p>Hello <b>{$safeName}</b>, your account is ACTIVATED.</p>
                </div>
            "
        ]);

        return true;

    } catch (Exception $e) {

        error_log("MAIL ERROR: " . $e->getMessage());
        return false;
    }
}