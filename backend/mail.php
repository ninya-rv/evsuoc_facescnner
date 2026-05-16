<?php

require __DIR__ . '/../vendor/autoload.php';

use Resend\Resend;

function sendActivationEmail($toEmail, $name)
{
    try {

        $apiKey = getenv('RESEND_API_KEY');

        if (!$apiKey) {
            throw new Exception("Missing RESEND_API_KEY");
        }

        $resend = Resend::client($apiKey);

        $resend->emails->send([
            'from' => 'EVSU System <onboarding@resend.dev>',
            'to' => [$toEmail],
            'subject' => 'Account Activated - EVSU BSIT System',
            'html' => "<p>Hello " . htmlspecialchars($name) . ", your account is ACTIVE.</p>"
        ]);

        return true;

    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $e->getMessage());
        return false;
    }
}