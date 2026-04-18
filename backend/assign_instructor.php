<?php
include "db.php";

if(isset($_POST['assign'])){
    $id = $_POST['edit_id']; 
    $instructor = $_POST['instructor_name'];
    $year = $_POST['year_level'];
    $section = $_POST['section'];
    $subject = $_POST['subject'];
    $room = $_POST['room'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    if(!empty($id)){
        $sql = "UPDATE instructor_assignment 
                SET instructor_name='$instructor', year_level='$year', section='$section', subject='$subject', room='$room', start_time='$start', end_time='$end' 
                WHERE id=$id";
    } else {
        $sql = "INSERT INTO instructor_assignment (instructor_name, year_level, section, subject, room, start_time, end_time)
                VALUES ('$instructor','$year','$section','$subject','$room','$start','$end')";
    }

    if(mysqli_query($conn, $sql)){
        header("Location: ../frontend/admin/instructor_assignment.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>