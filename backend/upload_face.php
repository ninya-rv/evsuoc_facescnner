<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {

    echo json_encode([
        "success" => false,
        "msg" => "No data received"
    ]);

    exit;
}

$student_id = pg_escape_string($conn, trim($data['student_id']));
$first_name = pg_escape_string($conn, trim($data['first_name']));
$last_name = pg_escape_string($conn, trim($data['last_name']));
$email = pg_escape_string($conn, trim($data['email']));
$password = pg_escape_string($conn, trim($data['password']));
$year = pg_escape_string($conn, trim($data['year']));
$section = pg_escape_string($conn, trim($data['section']));

$new_descriptor = $data['descriptor'];

$status = "inactive";

if (!preg_match('/^[a-zA-Z0-9._%+-]+@evsu\.edu\.ph$/', $email)) {

    echo json_encode([
        "success" => false,
        "msg" => "Only @evsu.edu.ph emails are allowed."
    ]);

    exit;
}

if (
    strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[\W_]/', $password)
) {

    echo json_encode([
        "success" => false,
        "msg" => "Weak password."
    ]);

    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$checkQuery = "
    SELECT id
    FROM students
    WHERE student_id = '$student_id'
";

$checkResult = pg_query($conn, $checkQuery);

if (!$checkResult) {

    echo json_encode([
        "success" => false,
        "msg" => pg_last_error($conn)
    ]);

    exit;
}

if (pg_num_rows($checkResult) > 0) {

    echo json_encode([
        "success" => false,
        "type" => "duplicate_id"
    ]);

    exit;
}

function faceDistance($a, $b) {

    $sum = 0;

    for ($i = 0; $i < count($a); $i++) {

        $sum += pow($a[$i] - $b[$i], 2);
    }

    return sqrt($sum);
}

$result = pg_query($conn, "
    SELECT face_descriptor
    FROM students
");

if (!$result) {

    echo json_encode([
        "success" => false,
        "msg" => pg_last_error($conn)
    ]);

    exit;
}

while ($row = pg_fetch_assoc($result)) {

    $stored_descriptor = json_decode(
        $row['face_descriptor'],
        true
    );

    if (!$stored_descriptor) {
        continue;
    }

    $distance = faceDistance(
        $new_descriptor,
        $stored_descriptor
    );

    if ($distance < 0.5) {

        echo json_encode([
            "success" => false,
            "type" => "duplicate_face"
        ]);

        exit;
    }
}

$descriptor = pg_escape_string(
    $conn,
    json_encode($new_descriptor)
);

$insertQuery = "
    INSERT INTO students (
        student_id,
        first_name,
        last_name,
        email,
        password,
        year,
        section,
        face_descriptor,
        status
    )
    VALUES (
        '$student_id',
        '$first_name',
        '$last_name',
        '$email',
        '$hashedPassword',
        '$year',
        '$section',
        '$descriptor',
        '$status'
    )
";

$insertResult = pg_query($conn, $insertQuery);

if ($insertResult) {

    echo json_encode([
        "success" => true,
        "msg" => "Student registered successfully"
    ]);

} else {

    echo json_encode([
        "success" => false,
        "msg" => "Database insert failed",
        "error" => pg_last_error($conn)
    ]);
}
?>