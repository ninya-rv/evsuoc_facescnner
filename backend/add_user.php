<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

$name = mysqli_real_escape_string($conn, $_POST['name']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$password = mysqli_real_escape_string($conn, $_POST['password']);

/* AUTO ROLE */
$role = "instructor";

/* HASH PASSWORD */
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$check = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $check);

if (mysqli_num_rows($result) > 0) {
    die("Email already exists.");
}

$sql = "INSERT INTO users(name, email, password, role, status)
        VALUES('$name', '$email', '$hashedPassword', '$role', 'active')";

if (mysqli_query($conn, $sql)) {
    header("Location: ../../frontend/admin/users.php?success=1");
    exit;
} else {
    die("Failed to add user.");
}
?>