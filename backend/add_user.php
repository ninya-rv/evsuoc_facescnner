<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$first_name || !$last_name || !$email || !$password) {
    die("Please fill all fields.");
}

if (!preg_match('/^[^@\s]+@evsu\.edu\.ph$/i', $email)) {
    die("Only @evsu.edu.ph email addresses are accepted.");
}

if (strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[^a-zA-Z0-9]/', $password)) {
    die("Password must be at least 8 characters and include an uppercase letter plus a symbol.");
}

$first_name = pg_escape_string($conn, $first_name);
$last_name = pg_escape_string($conn, $last_name);
$email = pg_escape_string($conn, $email);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$name = pg_escape_string($conn, trim("$first_name $last_name"));

$role = "instructor";

$check = "
    SELECT *
    FROM users
    WHERE email='$email'
";

$result = pg_query($conn, $check);

if (!$result) {
    die("Database query failed.");
}

if (pg_num_rows($result) > 0) {
    die("Email already exists.");
}

$sql = "
    INSERT INTO users (
        first_name,
        last_name,
        name,
        email,
        password,
        role,
        status
    )
    VALUES (
        '$first_name',
        '$last_name',
        '$name',
        '$email',
        '$password',
        '$role',
        'active'
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