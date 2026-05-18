<?php
session_start();
include "db.php";

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode([
        "success" => false,
        "type" => "auth",
        "message" => "Unauthorized"
    ]);
    exit;
}

$instructor_id = $_SESSION['user_id'];

$instructorQuery = "
    SELECT name 
    FROM users 
    WHERE id='$instructor_id' 
    AND role='instructor' 
    LIMIT 1
";

$instructorResult = pg_query($conn, $instructorQuery);

if (!$instructorResult || pg_num_rows($instructorResult) == 0) {

    echo json_encode([
        "success" => false,
        "type" => "instructor",
        "message" => "Instructor not found"
    ]);

    exit;
}

$instructor_name = pg_fetch_assoc($instructorResult)['name'];

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {

    echo json_encode([
        "success" => false,
        "type" => "input",
        "message" => "No data received"
    ]);

    exit;
}

$student_id = pg_escape_string($conn, $data['student_id'] ?? '');
$name = pg_escape_string($conn, $data['name'] ?? '');
$email = pg_escape_string($conn, $data['email'] ?? '');
$subject = pg_escape_string($conn, $data['subject'] ?? '');
$year_level = pg_escape_string($conn, $data['year_level'] ?? '');
$section = pg_escape_string($conn, $data['section'] ?? '');
$day = pg_escape_string($conn, $data['day'] ?? '');
$start_time = pg_escape_string($conn, $data['start_time'] ?? '');
$end_time = pg_escape_string($conn, $data['end_time'] ?? '');
$mode = $data['mode'] ?? 'time_in';

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

$studentCheck = "
    SELECT * 
    FROM students 
    WHERE student_id='$student_id'
    AND year='$year_level'
    AND section='$section'
    LIMIT 1
";

$result = pg_query($conn, $studentCheck);

if (!$result || pg_num_rows($result) == 0) {

    echo json_encode([
        "success" => false,
        "type" => "not_assigned",
        "message" => "Student not assigned to this class"
    ]);

    exit;
}

$student = pg_fetch_assoc($result);

if ($student['status'] === 'inactive') {

    echo json_encode([
        "success" => false,
        "type" => "inactive",
        "message" => "Student is inactive"
    ]);

    exit;
}

$assignCheck = "
    SELECT * 
    FROM instructor_assignment
    WHERE LOWER(TRIM(instructor_name)) = LOWER(TRIM('$instructor_name'))
    AND LOWER(TRIM(subject)) = LOWER(TRIM('$subject'))
    AND LOWER(TRIM(year_level)) = LOWER(TRIM('$year_level'))
    AND LOWER(TRIM(section)) = LOWER(TRIM('$section'))
    AND LOWER(TRIM(day)) = LOWER(TRIM('$day'))
    LIMIT 1
";

$assignResult = pg_query($conn, $assignCheck);

if (!$assignResult || pg_num_rows($assignResult) == 0) {

    echo json_encode([
        "success" => false,
        "type" => "not_assigned",
        "message" => "Student does not belong to this subject/class"
    ]);

    exit;
}

$check = "
    SELECT * 
    FROM attendance
    WHERE student_id='$student_id'
    AND date='$date'
    AND subject='$subject'
    AND year_level='$year_level'
    AND section='$section'
    AND instructor_name='$instructor_name'
";

$res = pg_query($conn, $check);

if ($res && pg_num_rows($res) > 0) {

    $row = pg_fetch_assoc($res);

    if ($mode === "time_out") {

        if (empty($row['time_out'])) {

            pg_query(
                $conn,
                "UPDATE attendance 
                 SET time_out='$currentTime' 
                 WHERE id='{$row['id']}'"
            );

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

$insertQuery = "
    INSERT INTO attendance
    (
        student_id,
        name,
        email,
        instructor_name,
        subject,
        year_level,
        section,
        day,
        date,
        time_in,
        status
    )
    VALUES
    (
        '$student_id',
        '$name',
        '$email',
        '$instructor_name',
        '$subject',
        '$year_level',
        '$section',
        '$day',
        '$date',
        '$currentTime',
        '$status'
    )
";

pg_query($conn, $insertQuery);

echo json_encode([
    "success" => true,
    "type" => "success",
    "message" => "Time In recorded ($status)"
]);

exit;
?>