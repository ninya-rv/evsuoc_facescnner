<?php
session_start();
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if(!isset($data['email']) || !isset($data['password'])){
    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);
    exit;
}

$email = mysqli_real_escape_string($conn, $data['email']);
$password = $data['password'];

$sql = "SELECT * FROM users WHERE email='$email' LIMIT 1";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) === 1){

    $user = mysqli_fetch_assoc($result);
    if($user['password'] === $password){

        /* Check account status */
        if($user['status'] !== "active"){
            echo json_encode([
                "success" => false,
                "message" => "Account is inactive."
            ]);
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        echo json_encode([
            "success" => true,
            "role" => $user['role']
        ]);
        exit;

    }else{

        echo json_encode([
            "success" => false,
            "message" => "Wrong password."
        ]);
        exit;

    }

}else{

    echo json_encode([
        "success" => false,
        "message" => "Email not found."
    ]);
    exit;

}
?>