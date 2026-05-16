<?php

require __DIR__ . '/../vendor/autoload.php';

use Resend\Client;

function sendActivationEmail($toEmail, $name)
{
    try {

        // API KEY FROM RAILWAY
        $client = new Client(getenv('RESEND_API_KEY'));

        $safeName = htmlspecialchars($name);

        $client->emails->send([
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