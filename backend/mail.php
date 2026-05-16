<?php

require __DIR__ . '/../vendor/autoload.php';

use Resend\Client;

function sendActivationEmail($toEmail, $name)
{
    try {

        $apiKey = getenv('RESEND_API_KEY');

        if (!$apiKey) {
            throw new Exception("Missing RESEND_API_KEY in Railway");
        }

        // CORRECT for v1.3
        $resend = new Client($apiKey);

        $resend->emails->send([
            'from' => 'EVSU System <onboarding@resend.dev>',
            'to' => [$toEmail],
            'subject' => 'Account Activated - EVSU BSIT System',
            'html' => "
                <div style='font-family:Arial'>
                    <h3>EVSU System</h3>
                    <p>Hello " . htmlspecialchars($name) . "</p>
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