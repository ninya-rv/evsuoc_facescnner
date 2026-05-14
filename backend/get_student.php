<?php
header('Content-Type: application/json');
include "db.php";

if (!$conn) {
    echo json_encode([
        "error" => "Database connection failed",
        "students" => []
    ]);
    exit;
}

$query = "
    SELECT student_id, name, email, face_descriptor, status
    FROM students
";

$result = pg_query($conn, $query);

if (!$result) {
    echo json_encode([
        "error" => pg_last_error($conn),
        "students" => []
    ]);
    exit;
}

$students = [];

while ($row = pg_fetch_assoc($result)) {
    $descriptor = $row["face_descriptor"];

    if (is_string($descriptor)) {
        $descriptor = json_decode($descriptor, true);
    }

    if (!is_array($descriptor)) {
        continue;
    }

    $students[] = [
        "student_id" => $row["student_id"],
        "name" => $row["name"],
        "email" => $row["email"],
        "face_descriptor" => array_values($descriptor),
        "status" => $row["status"]
    ];
}

echo json_encode([
    "count" => count($students),
    "students" => $students
]);