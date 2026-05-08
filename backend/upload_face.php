<?php 

header("Content-Type: application/json");

$conn = new mysqli("localhost","root","","class_attendance");

if ($conn->connect_error) {

    echo json_encode([
        "success" => false,
        "msg" => "Database connection failed"
    ]);

    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if(!$data){

    echo json_encode([
        "success" => false,
        "msg" => "No data received"
    ]);

    exit;
}

$student_id = $data['student_id'];
$name = $data['name'];
$email = $data['email'];
$year = $data['year'];
$section = $data['section'];
$new_descriptor = $data['descriptor'];


$status = "inactive";


$checkID = $conn->prepare("SELECT id FROM students WHERE student_id = ?");

$checkID->bind_param("s", $student_id);

$checkID->execute();

$resultID = $checkID->get_result();

if($resultID->num_rows > 0){

    echo json_encode([
        "success" => false,
        "type" => "duplicate_id"
    ]);

    exit;
}


function faceDistance($a,$b){

    $sum = 0;

    for($i=0;$i<count($a);$i++){

        $sum += pow($a[$i] - $b[$i], 2);

    }

    return sqrt($sum);
}


$result = $conn->query("SELECT face_descriptor FROM students");

while($row = $result->fetch_assoc()){

    $stored_descriptor = json_decode($row['face_descriptor'], true);

    $distance = faceDistance($new_descriptor, $stored_descriptor);

    if($distance < 0.5){

        echo json_encode([
            "success" => false,
            "type" => "duplicate_face"
        ]);

        exit;
    }
}


$descriptor = json_encode($new_descriptor);


/* INSERT WITH STATUS */
$stmt = $conn->prepare("
    INSERT INTO students
    (student_id, name, email, year, section, face_descriptor, status)
    VALUES
    (?,?,?,?,?,?,?)
");

$stmt->bind_param(
    "sssssss",
    $student_id,
    $name,
    $email,
    $year,
    $section,
    $descriptor,
    $status
);


if($stmt->execute()){

    echo json_encode([
        "success" => true,
        "msg" => "Student registered successfully"
    ]);

}else{

    echo json_encode([
        "success" => false,
        "msg" => "Database insert failed"
    ]);

}

?>