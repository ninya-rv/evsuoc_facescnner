<?php
include "backend/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json');

    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid request."
        ]);
        exit;
    }

    $first_name = pg_escape_string($conn, trim($data['first_name']));
    $last_name  = pg_escape_string($conn, trim($data['last_name']));
    $email      = pg_escape_string($conn, trim($data['email']));
    $password   = $data['password'];

    $role = 'instructor';

    // =========================
    // EMAIL VALIDATION (EVSU ONLY)
    // =========================
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@evsu\.edu\.ph$/", $email)) {
        echo json_encode([
            "success" => false,
            "message" => "Only @evsu.edu.ph emails are allowed."
        ]);
        exit;
    }

    // =========================
    // PASSWORD VALIDATION
    // =========================
    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[\W_]/', $password)
    ) {
        echo json_encode([
            "success" => false,
            "message" => "Password must be 8+ chars, 1 uppercase, 1 symbol."
        ]);
        exit;
    }

    // =========================
    // CHECK EMAIL EXISTS
    // =========================
    $check = "SELECT id FROM users WHERE email='$email' LIMIT 1";
    $result = pg_query($conn, $check);

    if (!$result) {
        echo json_encode([
            "success" => false,
            "message" => "Database error."
        ]);
        exit;
    }

    if (pg_num_rows($result) > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Email already exists."
        ]);
        exit;
    }

    // =========================
    // INSERT USER (SUPABASE)
    // =========================
    $sql = "
        INSERT INTO users (first_name, last_name, email, password, role, status)
        VALUES ('$first_name', '$last_name', '$email', '$password', '$role', 'active')
    ";

    $insert = pg_query($conn, $sql);

    if ($insert) {
        echo json_encode([
            "success" => true,
            "message" => "Account created. Waiting for admin approval."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Registration failed."
        ]);
    }

    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up</title>

    <style>
        :root{
            --purple-1: #6c4bf0;
            --purple-2: #8a6ffb;
            --bg: #efe9ff;
        }

        *{
            box-sizing:border-box
        }

        body{
            margin:0;
            font-family:Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
            background:#f8f9fa;
            display:flex;
            align-items:center;
            justify-content:center;
            height:100vh;
            position:relative;
            overflow:hidden
        }

        body::before{
            content:'';
            position:fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background-image:url('/css/evsu_background.png');
            background-size:cover;
            background-position:center;
            background-attachment:fixed;
            filter:blur(4px);
            z-index:-1
        }

        .wrap{
            width:900px;
            max-width:95%;
            padding:20px 0;
        }

        .card{
            display:flex;
            background:#fff;
            border-radius:18px;
            overflow:hidden;
            box-shadow:0 15px 30px rgba(40,20,100,0.08)
        }

        .left, .right{
            flex:1;
            min-height:480px
        }

        @media (max-width:860px){
            .card{
                flex-direction:column
            }

            .left,.right{
                min-height:auto
            }
        }

        .left{
            padding:60px 48px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:center;
            background:#fff;
            flex:0 0 40%;
            position:relative;
        }

        .right{
            flex:1;
        }

        .portal-title{
            font-size:44px;
            font-weight:900;
            color:#d32f2f;
            margin:0 0 32px 0;
            letter-spacing:-1px;
            white-space:nowrap
        }

        form{
            max-width:400px;
            width:100%;
            padding:0 16px;
            display:flex;
            flex-direction:column;
            align-items:center;
            margin-top:32px
        }

        .field{
            margin-bottom:24px;
            width:100%
        }

        input{
            width:100%;
            padding:16px 18px;
            border-radius:6px;
            border:2px solid #8B0000;
            background:rgba(255,255,255,0.1);
            font-size:16px;
            color:#fff;
            font-weight:500;
        }

        input::placeholder{
            color:#ffdede
        }

        button{
            margin:10px auto 0 auto;
            background:#ffdede;
            color:#8B0000;
            padding:14px 16px;
            border-radius:6px;
            border:0;
            width:50%;
            font-size:14px;
            font-weight:600;
            cursor:pointer;
        }

        .msg{
            margin-top:8px;
            font-size:13px;
            padding:10px;
            border-radius:4px;
            display:none
        }

        .msg.show{
            display:block
        }

        .msg.error{
            background:#fed7d7;
            color:#c53030
        }

        .msg.success{
            background:#c6f6d5;
            color:#22543d
        }

        .links{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin:16px 0 24px 0;
            font-size:14px;
            width:100%;
        }

        .links a{
            color:#d32f2f;
            text-decoration:none;
            font-weight:500
        }

        .footer{
            font-size:12px;
            color:#666;
            line-height:1.7;
            padding-top:24px;
            border-top:1px solid #e0e0e0
        }

        .right{
            background:linear-gradient(135deg,#6B0000,#8B0000);
            color:#fff;
            padding:40px;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            position:relative
        }

        .visual-card{
            width:240px;
            height:240px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:rgba(255,255,255,0.1);
            border-radius:12px;
            border:2px solid rgba(255,255,255,0.2);
        }
    </style>
</head>

<body>

<div class="wrap">

    <div class="card">

        <div class="left">

            <div class="visual-card">

                <img 
                    src="/css/EVSU_Official_Logo.png"
                    alt="EVSU logo"
                    style="max-width:180px;max-height:180px;object-fit:contain;"
                />

            </div>

        </div>

        <div class="right">

            <h1 class="portal-title" style="color:#fff;">
                Create Account
            </h1>

            <form id="signUpForm">

                <div class="field">
                    <input id="first_name" type="text" placeholder="First Name" required />
                </div>

                <div class="field">
                    <input id="last_name" type="text" placeholder="Last Name" required />
                </div>

                <div class="field">
                    <input id="email" type="email" placeholder="@evsu.edu.ph" required />
                </div>

                <div class="field">
                    <input id="password" type="password" placeholder="Password" required />
                </div>

                <div class="field">
                    <input id="confirmPassword" type="password" placeholder="Confirm Password" required />
                </div>

                <button type="submit">Register</button>

                <div id="message" class="msg"></div>

                <div class="links">
                    <a href="index.php" style="color:#ffdede">
                        Already have an account? Sign In
                    </a>
                </div>

            </form>

            <div class="footer" style="color:#eee">
                By using this service, you understood and agree to the
                <a href="#terms" style="color:#ffdede">
                    EVSU Online Services Terms of Use and Privacy Statement
                </a>
            </div>

        </div>

    </div>

</div>

<script>

const form = document.getElementById('signUpForm');
const msg = document.getElementById('message');

form.addEventListener('submit', async (e) => {

    e.preventDefault();

    msg.classList.remove('show','error','success');

    const firstName = document.getElementById('first_name').value.trim();
    const lastName = document.getElementById('last_name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if(!firstName || !lastName || !email || !password || !confirmPassword){

        msg.classList.add('show','error');
        msg.textContent = "Please fill all fields.";
        return;
    }

    if(password !== confirmPassword){

        msg.classList.add('show','error');
        msg.textContent = "Passwords do not match.";
        return;
    }

    try {

        const res = await fetch(window.location.href, {

            method:'POST',

            headers:{
                'Content-Type':'application/json'
            },

            body:JSON.stringify({
                first_name: firstName,
                last_name: lastName,
                email: email,
                password: password,
                role: 'instructor'
            })
        });

        const data = await res.json();

        if(data.success){

            msg.classList.add('show','success');
            msg.textContent = "Account created successfully!";

            setTimeout(() => {

                window.location.href = "index.php";

            }, 1000);

        } else {

            msg.classList.add('show','error');
            msg.textContent = data.message;
        }

    } catch(err) {

        msg.classList.add('show','error');
        msg.textContent = "Server error.";
    }

});

</script>

</body>
</html>