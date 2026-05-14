<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['email']) || !isset($data['password'])) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid request."
        ]);
        exit;
    }

    $email = pg_escape_string($conn, $data['email']);
    $password = $data['password'];

    $sql = "SELECT * FROM users WHERE email='$email' LIMIT 1";

    $result = pg_query($conn, $sql);

    if (!$result) {
        echo json_encode([
            "success" => false,
            "message" => "Database query failed."
        ]);
        exit;
    }

    if (pg_num_rows($result) === 1) {

        $user = pg_fetch_assoc($result);

        if ($user['password'] === $password) {

            if ($user['status'] !== "active") {
                echo json_encode([
                    "success" => false,
                    "message" => "Account is inactive."
                ]);
                exit;
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            echo json_encode([
                "success" => true,
                "role" => $user['role']
            ]);
            exit;

        } else {

            echo json_encode([
                "success" => false,
                "message" => "Wrong password."
            ]);
            exit;
        }

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Email not found."
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign In</title>

    <style>
        :root{
            --purple-1: #6c4bf0;
            --purple-2: #8a6ffb;
            --bg: #efe9ff;
        }

        *{
            box-sizing:border-box;
        }

        body{
            margin:0;
            font-family:Inter, system-ui, -apple-system, 'Segoe UI', Roboto, Arial;
            background:#f8f9fa;
            display:flex;
            align-items:center;
            justify-content:center;
            height:100vh;
            position:relative;
            overflow:hidden;
        }

        body::before{
            content:'';
            position:fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background-image:url('../css/evsu_background.png');
            background-size:cover;
            background-position:center;
            background-attachment:fixed;
            filter:blur(4px);
            z-index:-1;
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
            box-shadow:0 15px 30px rgba(40,20,100,0.08);
        }

        .left,
        .right{
            flex:1;
            min-height:480px;
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
            background:linear-gradient(135deg,#6B0000,#8B0000);
            color:#fff;
            padding:40px;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            position:relative;
        }

        .portal-title{
            font-size:44px;
            font-weight:900;
            color:#fff;
            margin:0 0 32px 0;
            letter-spacing:-1px;
            white-space:nowrap;
        }

        form{
            max-width:400px;
            width:100%;
            padding:0 16px;
            display:flex;
            flex-direction:column;
            align-items:center;
            margin-top:32px;
        }

        .field{
            margin-bottom:24px;
            width:100%;
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
            color:#ffdede;
        }

        input:focus{
            outline:none;
            border-color:#ffdede;
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

        button:hover{
            background:#fff;
        }

        .msg{
            margin-top:8px;
            font-size:13px;
            padding:10px;
            border-radius:4px;
            display:none;
            width:100%;
        }

        .msg.show{
            display:block;
        }

        .msg.error{
            background:#fed7d7;
            color:#c53030;
        }

        .msg.success{
            background:#c6f6d5;
            color:#22543d;
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
            color:#ffdede;
            text-decoration:none;
        }

        .footer{
            font-size:12px;
            color:#eee;
            line-height:1.7;
            padding-top:24px;
            border-top:1px solid #e0e0e0;
        }

        .visual-card{
            width:240px;
            height:240px;
            display:flex;
            align-items:center;
            justify-content:center;
            flex-direction:column;
            background:rgba(255,255,255,0.1);
            border-radius:12px;
            border:2px solid rgba(255,255,255,0.2);
        }

        @media (max-width:860px){
            .card{
                flex-direction:column;
            }
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

            <h1 class="portal-title">
                Attendance Portal
            </h1>

            <form id="signInForm">

                <div class="field">
                    <input 
                        id="email"
                        type="email"
                        placeholder="instructor@evsu.edu.ph"
                        required
                    />
                </div>

                <div class="field">
                    <input 
                        id="password"
                        type="password"
                        placeholder="••••••••"
                        required
                    />
                </div>

                <button type="submit">
                    Login
                </button>

                <div id="message" class="msg"></div>

                <div class="links">
                    <a href="forgot.html">Forgot Password ?</a>
                    <a href="sign_up.php">New ? Register</a>
                </div>

            </form>

            <div class="footer">
                By using this service, you understood and agree to the
                EVSU Online Services Terms of Use and Privacy Statement
            </div>

        </div>

    </div>

</div>

<script>
const form = document.getElementById('signInForm');
const msg = document.getElementById('message');

form.addEventListener('submit', async (e) => {

    e.preventDefault();

    msg.classList.remove('show', 'error', 'success');
    msg.textContent = '';

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (!email || !password) {

        msg.classList.add('show', 'error');
        msg.textContent = '✗ Please fill in all fields.';
        return;
    }

    try {

        const res = await fetch(window.location.href, {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json'
            },

            body: JSON.stringify({
                email,
                password
            })
        });

        const data = await res.json();

        if (data.success) {

            msg.classList.add('show', 'success');
            msg.textContent = '✓ Signed in successfully. Redirecting...';

            setTimeout(() => {

                if (data.role === 'admin') {

                    window.location.href = '/frontend/admin/dashboard.php';

                } else if (data.role === 'instructor') {

                    window.location.href = '/frontend/instructor/dashboard.php';

                } else {

                    window.location.href = '/frontend/user/dashboard.php';
                }

            }, 700);

        } else {

            msg.classList.add('show', 'error');
            msg.textContent = '✗ ' + data.message;
        }

    } catch (err) {

        msg.classList.add('show', 'error');
        msg.textContent = '✗ Network or server error.';
    }
});
</script>

</body>
</html>