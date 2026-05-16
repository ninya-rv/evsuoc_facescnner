<?php

require __DIR__ . '/../vendor/autoload.php';

use Resend;

function sendActivationEmail($toEmail, $name)
{
    try {

        $resend = Resend::client(
            getenv('re_d9B5YisN_Er8TiJXnV52w1xSeBfnCSF41')
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
                        Your student profile has been
                        <b style='color:green'>ACTIVATED</b>
                        by the administrator.
                    </p>

                    <p>
                        You may now use your face for
                        attendance verification.
                    </p>

                    <p>
                        <b>Instruction:</b>
                        Please proceed to your instructor
                        and scan your face for attendance recording.
                    </p>

                    <div style='margin-top:15px;padding:10px;background:#f8f8f8;border-left:5px solid #800000'>

                        <p style='margin:0'>
                            <b>Status:</b> Active
                        </p>

                    </div>

                    <br>

                    <p style='font-size:12px;color:gray'>
                        This is an automated message from
                        EVSU Face Recognition Attendance System.
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