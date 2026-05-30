<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$student_id = trim($_POST['student_id'] ?? '');
$email = trim($_POST['email'] ?? '');
$year = trim($_POST['year'] ?? '');
$section = trim($_POST['section'] ?? '');

if ($student_id === '' || $email === '' || $year === '' || $section === '') {
    die("All fields are required.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email address.");
}

$student_id = pg_escape_string($conn, $student_id);
$email = pg_escape_string($conn, $email);
$year = pg_escape_string($conn, $year);
$section = pg_escape_string($conn, $section);

$checkEmail = pg_query($conn, "SELECT student_id FROM students WHERE email='$email' AND student_id <> '$student_id'");
if ($checkEmail && pg_num_rows($checkEmail) > 0) {
    die("Email is already taken by another student.");
}

// Only admins may update student records.

$sql = "
    UPDATE students
    SET email='$email', year='$year', section='$section'
    WHERE student_id='$student_id'
";

$result = pg_query($conn, $sql);

if (!$result) {
    die("Failed to update student: " . pg_last_error($conn));
}

header("Location: ../frontend/admin/dashboard.php");
exit;
