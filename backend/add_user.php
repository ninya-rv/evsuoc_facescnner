<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';

$role = "instructor";

/* EMAIL VALIDATION */
if (!preg_match("/^[a-zA-Z0-9._%+-]+@evsu\.edu\.ph$/", $email)) {
    die("Only @evsu.edu.ph emails are allowed.");
}

/* PASSWORD VALIDATION */
if (
    strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[\W_]/', $password)
) {
    die("Password must be at least 8 characters long, contain 1 uppercase letter and 1 symbol.");
}

/* CHECK EMAIL */
$checkQuery = pg_query_params(
    $conn,
    "SELECT id FROM users WHERE email = $1",
    [$email]
);

if (pg_num_rows($checkQuery) > 0) {
    die("Email already exists.");
}

/* DEFAULT PHOTO */
$photoPath = "";

/* PHOTO UPLOAD */
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {

    $allowed = ['jpg', 'jpeg', 'png'];

    $fileName = $_FILES['photo']['name'];
    $fileTmp  = $_FILES['photo']['tmp_name'];

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        die("Only JPG, JPEG, and PNG files are allowed.");
    }

    /*
    REAL FOLDER:
    frontend/instructor/uploads/
    */

    $uploadDir = dirname(__DIR__) . "/frontend/instructor/uploads/";

    /* CREATE FOLDER */
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    /* SAFE FILE NAME */
    $newFileName = time() . "_" . preg_replace(
        "/[^a-zA-Z0-9.\-_]/",
        "",
        basename($fileName)
    );

    /* FULL SERVER PATH */
    $uploadPath = $uploadDir . $newFileName;

    /* MOVE FILE */
    if (move_uploaded_file($fileTmp, $uploadPath)) {

        /*
        SAVE PATH FOR DATABASE
        */

        $photoPath = "/frontend/instructor/uploads/" . $newFileName;

    } else {

        die("Failed to upload photo.");
    }
}

/* INSERT USER */
$insertQuery = pg_query_params(
    $conn,
    "INSERT INTO users (
        first_name,
        last_name,
        email,
        password,
        role,
        status,
        photo
    )
    VALUES (
        $1, $2, $3, $4, $5, $6, $7
    )",
    [
        $first_name,
        $last_name,
        $email,
        $password,
        $role,
        'inactive',
        $photoPath
    ]
);

if ($insertQuery) {

    header("Location: ../../frontend/admin/users.php?success=1");
    exit;

} else {

    die("Failed to add user.");
}
?>