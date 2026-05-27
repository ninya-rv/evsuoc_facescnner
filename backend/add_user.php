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

/* =========================
   VALIDATION RULES
========================= */

// 1. EVSU email only
if (!preg_match("/^[a-zA-Z0-9._%+-]+@evsu\.edu\.ph$/", $email)) {
    die("Only @evsu.edu.ph emails are allowed.");
}

// 2. Password rules: 8+ chars, 1 uppercase, 1 symbol
if (strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[\W_]/', $password)) {

    die("Password must be at least 8 characters long, contain 1 uppercase letter and 1 symbol.");
}

// 3. Combine full name
$name = $first_name . " " . $last_name;

/* =========================
   CHECK EMAIL EXISTENCE
========================= */

$check = "SELECT * FROM users WHERE email='$email'";
$result = pg_query($conn, $check);

if (!$result) {
    die("Database query failed.");
}

if (pg_num_rows($result) > 0) {
    die("Email already exists.");
}

/* =========================
   INSERT USER
========================= */

$sql = "
    INSERT INTO users (
        first_name,
        last_name,
        email,
        password,
        role,
        status
    )
    VALUES (
        '$first_name',
        '$last_name',
        '$email',
        '$password',
        '$role',
        'inactive'
    )
";

$insert = pg_query($conn, $sql);

if ($insert) {
    header("Location: ../../frontend/admin/users.php?success=1");
    exit;
} else {
    die("Failed to add user.");
}
?>