<?php

require __DIR__ . '/../vendor/autoload.php';

function sendActivationEmail($toEmail, $name)
{
    try {

        $resend = Resend::client(
            getenv('RESEND_API_KEY')
        );

        $safeName = htmlspecialchars($name);

        $resend->emails->send([

            'from' => 'onboarding@resend.dev',

            'to' => [$toEmail],

            'subject' =>
                'Account Activated - EVSU BSIT System',

            'html' => "
            <div style='font-family:Arial;padding:20px;background:#f4f4f4'>

                <div style='max-width:600px;margin:auto;background:white;padding:20px;border-radius:10px'>

                    <h2 style='color:#800000'>
                        EVSU Face Recognition System
                    </h2>

                    <p>
                        Hello <b>{$safeName}</b>,
                    </p>

                    <p>
                        Your account has been
                        <b style='color:green'>ACTIVATED</b>.
                    </p>

                </div>

            </div>"
        ]);

        return true;

    } catch (Exception $e) {

        die(
            "MAILER ERROR: " .
            $e->getMessage()
        );
    }
}
?>