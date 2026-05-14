<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

$name = pg_escape_string($conn, $_POST['name']);
$email = pg_escape_string($conn, $_POST['email']);
$password = pg_escape_string($conn, $_POST['password']);

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
        name,
        email,
        password,
        role,
        status
    )
    VALUES (
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