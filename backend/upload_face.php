<?php

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

$student_id = pg_escape_string($conn, $data['student_id']);
$name = pg_escape_string($conn, $data['name']);
$email = pg_escape_string($conn, $data['email']);
$year = pg_escape_string($conn, $data['year']);
$section = pg_escape_string($conn, $data['section']);

$new_descriptor = $data['descriptor'];

$status = "inactive";


$checkQuery = "
    SELECT id
    FROM students
    WHERE student_id = '$student_id'
";

$checkResult = pg_query($conn, $checkQuery);

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
        name,
        email,
        year,
        section,
        face_descriptor,
        status
    )
    VALUES (
        '$student_id',
        '$name',
        '$email',
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