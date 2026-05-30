<?php
include "../../backend/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../index.php");
    exit;
}

$student_id = $_SESSION['student_id'];

$message = "";

if (isset($_POST['change_password'])) {

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $studentQuery = "
        SELECT password
        FROM students
        WHERE student_id = '$student_id'
    ";

    $studentResult = pg_query($conn, $studentQuery);

    $student = pg_fetch_assoc($studentResult);

    if ($current_password != $student['password']) {

        $message = "Current password is incorrect.";

    } elseif ($new_password != $confirm_password) {

        $message = "Passwords do not match.";

    } else {

        $updateQuery = "
            UPDATE students
            SET password = '$new_password'
            WHERE student_id = '$student_id'
        ";

        if (pg_query($conn, $updateQuery)) {

            $message = "Password changed successfully.";

        } else {

            $message = "Failed to change password.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Change Password</title>

    <style>

        body{
            font-family:Arial;
            background:#f4f4f4;
            padding:40px;
        }

        .container{
            width:400px;
            background:#fff;
            margin:auto;
            padding:30px;
            border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,0.1);
        }

        input{
            width:100%;
            padding:12px;
            margin-bottom:15px;
            border:1px solid #ccc;
            border-radius:5px;
        }

        button{
            background:#800000;
            color:#fff;
            border:none;
            padding:12px 20px;
            border-radius:5px;
            cursor:pointer;
        }

        .message{
            margin-bottom:15px;
            color:#800000;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>Change Password</h2>

    <?php if($message != ""): ?>

        <div class="message">
            <?php echo $message; ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <input
            type="password"
            name="current_password"
            placeholder="Current Password"
            required
        >

        <input
            type="password"
            name="new_password"
            placeholder="New Password"
            required
        >

        <input
            type="password"
            name="confirm_password"
            placeholder="Confirm Password"
            required
        >

        <button
            type="submit"
            name="change_password"
        >
            Change Password
        </button>

    </form>

</div>

</body>
</html>