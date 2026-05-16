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
            <div style='font-family:Arial;padding:20px'>
                <h2>Account Activated</h2>
                <p>Hello <b>{$name}</b>, your account has been ACTIVATED.</p>
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