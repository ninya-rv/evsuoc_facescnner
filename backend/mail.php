<?php

function sendActivationEmail($toEmail, $name)
{
    $apiKey = getenv('BREVO_API_KEY');

    $data = [
        "sender" => [
            "name" => "EVSU BSIT System",
            "email" => "rhenahmaycabudlay@gmail.com"
        ],
        "to" => [
            [
                "email" => $toEmail,
                "name" => $name
            ]
        ],
        "subject" => "Account Activated",
        "htmlContent" => "
            <div style='font-family:Arial;padding:20px;background:#f4f4f4'>
                <div style='max-width:600px;margin:auto;background:white;padding:20px;border-radius:10px'>

                    <h2 style='color:#800000'>EVSU Face Recognition System</h2>

                    <p>Hello <b>{$name}</b>,</p>

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
            </div>
        "
    ];

    $ch = curl_init("https://api.brevo.com/v3/smtp/email");

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "content-type: application/json",
        "api-key: $apiKey"
    ]);

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpCode == 201) {
        return true;
    } else {
        error_log("EMAIL ERROR: " . $response);
        return false;
    }
}