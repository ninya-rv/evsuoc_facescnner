<?php
session_start();
include "db.php";

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode([
        "success" => false,
        "type" => "auth",
        "message" => "Unauthorized"
    ]);
    exit;
}

$instructor_id = $_SESSION['user_id'];

/* =========================
   GET INSTRUCTOR
========================= */
$instructorQuery = "SELECT name FROM users WHERE id='$instructor_id' AND role='instructor' LIMIT 1";
$instructorResult = mysqli_query($conn, $instructorQuery);

if (!$instructorResult || mysqli_num_rows($instructorResult) == 0) {
    echo json_encode([
        "success" => false,
        "type" => "instructor",
        "message" => "Instructor not found"
    ]);
    exit;
}

$instructor_name = mysqli_fetch_assoc($instructorResult)['name'];

/* =========================
   INPUT DATA
========================= */
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "type" => "input",
        "message" => "No data received"
    ]);
    exit;
}

$student_id  = mysqli_real_escape_string($conn, $data['student_id'] ?? '');
$name        = mysqli_real_escape_string($conn, $data['name'] ?? '');
$email       = mysqli_real_escape_string($conn, $data['email'] ?? '');
$subject     = mysqli_real_escape_string($conn, $data['subject'] ?? '');
$year_level  = mysqli_real_escape_string($conn, $data['year_level'] ?? '');
$section     = mysqli_real_escape_string($conn, $data['section'] ?? '');
$start_time  = mysqli_real_escape_string($conn, $data['start_time'] ?? '');
$end_time    = mysqli_real_escape_string($conn, $data['end_time'] ?? '');
$mode        = $data['mode'] ?? 'time_in';

$date = date("Y-m-d");
$currentTime = date("H:i:s");

if (!$student_id || !$name || !$subject) {
    echo json_encode([
        "success" => false,
        "type" => "missing",
        "message" => "Missing required data"
    ]);
    exit;
}
if (strtotime($currentTime) < strtotime($start_time)) {
    echo json_encode([
        "success" => false,
        "type" => "time",
        "message" => "Class has not started yet"
    ]);
    exit;
}

if (strtotime($currentTime) > strtotime($end_time)) {
    echo json_encode([
        "success" => false,
        "type" => "time",
        "message" => "Class has ended"
    ]);
    exit;
}

$studentCheck = "SELECT * FROM students 
WHERE student_id='$student_id' 
AND year='$year_level' 
AND section='$section' 
LIMIT 1";

$result = mysqli_query($conn, $studentCheck);

if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode([
        "success" => false,
        "type" => "not_assigned",
        "message" => "Student not assigned to this class"
    ]);
    exit;
}

$student = mysqli_fetch_assoc($result);

if ($student['status'] === 'inactive') {
    echo json_encode([
        "success" => false,
        "type" => "inactive",
        "message" => "Student is inactive"
    ]);
    exit;
}

$assignCheck = "SELECT * FROM instructor_assignment 
WHERE instructor_name='$instructor_name'
AND subject='$subject'
AND year_level='$year_level'
AND section='$section'
LIMIT 1";

$assignResult = mysqli_query($conn, $assignCheck);

if (!$assignResult || mysqli_num_rows($assignResult) == 0) {
    echo json_encode([
        "success" => false,
        "type" => "not_assigned",
        "message" => "Student does not belong to this subject/class"
    ]);
    exit;
}
$check = "SELECT * FROM attendance 
WHERE student_id='$student_id'
AND date='$date'
AND subject='$subject'
AND year_level='$year_level'
AND section='$section'
AND instructor_name='$instructor_name'";

$res = mysqli_query($conn, $check);

if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);

    if ($mode === "time_out") {
        if (empty($row['time_out'])) {
            mysqli_query($conn, "UPDATE attendance SET time_out='$currentTime' WHERE id='{$row['id']}'");

            echo json_encode([
                "success" => true,
                "type" => "success",
                "message" => "Time Out recorded"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "type" => "duplicate",
                "message" => "Already timed out"
            ]);
        }
        exit;
    }

    echo json_encode([
        "success" => false,
        "type" => "duplicate",
        "message" => "Already timed in"
    ]);
    exit;
}
$status = "Present";

if (strtotime($currentTime) > strtotime($start_time) + 1800) {
    $status = "Late";
}

mysqli_query($conn, "INSERT INTO attendance 
(student_id,name,email,instructor_name,subject,year_level,section,date,time_in,status)
VALUES
('$student_id','$name','$email','$instructor_name','$subject','$year_level','$section','$date','$currentTime','$status')");

echo json_encode([
    "success" => true,
    "type" => "success",
    "message" => "Time In recorded ($status)"
]);
exit;
?>