<?php

require __DIR__ . '/../vendor/autoload.php';

use Resend\Resend;

function sendActivationEmail($toEmail, $name)
{
    try {

        $apiKey = getenv('RESEND_API_KEY');

        if (!$apiKey) {
            error_log("Missing RESEND_API_KEY in environment");
            return false;
        }

        // Correct Resend v1.3 usage
        $resend = Resend::client($apiKey);

        $resend->emails->send([
            'from' => 'EVSU System <onboarding@resend.dev>',
            'to' => [$toEmail],
            'subject' => 'Account Activated - EVSU BSIT System',
            'html' => "
                <div style='font-family:Arial;padding:20px'>
                    <h2>EVSU System</h2>
                    <p>Hello " . htmlspecialchars($name) . ",</p>
                    <p>Your account is now <b style='color:green'>ACTIVE</b>.</p>
                </div>
            "
        ]);

        return true;

    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $e->getMessage());
        return false;
    }
}