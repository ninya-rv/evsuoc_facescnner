<?php
include "db.php";

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);
    exit;
}

$name = mysqli_real_escape_string($conn, $data['name']);
$email = mysqli_real_escape_string($conn, $data['email']);
$password = mysqli_real_escape_string($conn, $data['password']);

$role = isset($data['role']) ? mysqli_real_escape_string($conn, $data['role']) : 'instructor';

if ($role === 'admin') {
    echo json_encode([
        "success" => false,
        "message" => "Admin account cannot be created here."
    ]);
    exit;
}

$check = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $check);

if (mysqli_num_rows($result) > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Email already exists."
    ]);
    exit;
}

$sql = "INSERT INTO users (name, email, password, role, status)
        VALUES ('$name', '$email', '$password', '$role', 'inactive')";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        "success" => true,
        "message" => "Account created. Waiting for admin approval."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Registration failed."
    ]);
}
?>