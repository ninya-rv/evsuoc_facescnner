<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost","root","","class_attendance");
if($conn->connect_error){
    echo json_encode([]);
    exit;
}

$result = $conn->query("SELECT student_id, name, email, face_descriptor FROM students WHERE status = 'active'");

$students = [];

while($row = $result->fetch_assoc()){
    $students[] = [
        "student_id" => $row["student_id"],
        "name" => $row["name"],
        "email" => $row["email"],
        "face_descriptor" => json_decode($row["face_descriptor"]),
        "status" => "active"
    ];
}

echo json_encode($students);
?>