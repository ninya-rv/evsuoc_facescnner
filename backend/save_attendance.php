<?php
session_start();
include "db.php";

header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$instructor_id = $_SESSION['user_id'];

$instructorQuery = "SELECT name FROM users WHERE id = '$instructor_id' AND role = 'instructor' LIMIT 1";
$instructorResult = mysqli_query($conn, $instructorQuery);

if ($instructorResult && mysqli_num_rows($instructorResult) > 0) {
    $instructorData = mysqli_fetch_assoc($instructorResult);
    $instructor_name = $instructorData['name'];
} else {
    echo json_encode([
        "success" => false,
        "message" => "Instructor not found."
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "No data received."
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

$mode = isset($data['mode']) ? trim($data['mode']) : 'time_in';

$date = date("Y-m-d");
$currentTime = date("H:i:s");

if (empty($student_id) || empty($name) || empty($subject) || empty($year_level) || empty($section)) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required data."
    ]);
    exit;
}

if (empty($start_time) || empty($end_time)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid class schedule data."
    ]);
    exit;
}

$currentTimestamp = strtotime($currentTime);
$startTimestamp = strtotime($start_time);
$endTimestamp = strtotime($end_time);

if ($currentTimestamp === false || $startTimestamp === false || $endTimestamp === false) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid class time format."
    ]);
    exit;
}

if ($currentTimestamp < $startTimestamp) {
    echo json_encode([
        "success" => false,
        "message" => "Class has not started yet. Please scan during the scheduled time."
    ]);
    exit;
}

if ($currentTimestamp > $endTimestamp) {
    echo json_encode([
        "success" => false,
        "message" => "Class has already ended. You cannot record attendance now."
    ]);
    exit;
}

$studentCheck = "SELECT * FROM students 
                 WHERE student_id='$student_id'
                 AND year='$year_level'
                 AND section='$section'";

$studentCheckResult = mysqli_query($conn, $studentCheck);

if (!$studentCheckResult || mysqli_num_rows($studentCheckResult) == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Student not assigned to this class."
    ]);
    exit;
}

$checkSql = "SELECT * FROM attendance 
             WHERE student_id='$student_id'
             AND date='$date'
             AND subject='$subject'
             AND year_level='$year_level'
             AND section='$section'
             AND instructor_name='$instructor_name'";

$checkResult = mysqli_query($conn, $checkSql);

if ($checkResult && mysqli_num_rows($checkResult) > 0) {

    $existing = mysqli_fetch_assoc($checkResult);

    if ($mode === "time_out" || empty($existing['time_out'])) {

        if (empty($existing['time_out'])) {

            $updateSql = "UPDATE attendance 
                          SET time_out='$currentTime'
                          WHERE id='" . $existing['id'] . "'";

            if (mysqli_query($conn, $updateSql)) {
                echo json_encode([
                    "success" => true,
                    "message" => "Time Out saved successfully."
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Failed to save Time Out.",
                    "error" => mysqli_error($conn)
                ]);
            }

        } else {
            echo json_encode([
                "success" => false,
                "message" => "Already timed out."
            ]);
        }

        exit;
    }

    if ($mode === "time_in") {
        echo json_encode([
            "success" => false,
            "message" => "Already timed in."
        ]);
        exit;
    }
}

if ($mode === "time_in") {

    $status = "Present";

    $lateThreshold = strtotime($start_time) + (30 * 60);

    if (strtotime($currentTime) > $lateThreshold) {
        $status = "Late";
    }

    $insertSql = "INSERT INTO attendance 
    (student_id, name, email, instructor_name, subject, year_level, section, date, time_in, status)
    VALUES
    ('$student_id', '$name', '$email', '$instructor_name', '$subject', '$year_level', '$section', '$date', '$currentTime', '$status')";

    if (mysqli_query($conn, $insertSql)) {
        echo json_encode([
            "success" => true,
            "message" => "Time In saved ($status)"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to save Time In.",
            "error" => mysqli_error($conn)
        ]);
    }

    exit;
}

echo json_encode([
    "success" => false,
    "message" => "Invalid operation."
]);
?>