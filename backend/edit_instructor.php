<?php
include "db.php";

if(isset($_POST['id'], $_POST['year_level'], $_POST['section'], $_POST['subject'], $_POST['room'], $_POST['start_time'], $_POST['end_time'])){
    $id = $_POST['id'];
    $year = $_POST['year_level'];
    $section = $_POST['section'];
    $subject = $_POST['subject'];
    $room = $_POST['room'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    $sql = "UPDATE instructor_assignment 
            SET year_level='$year', section='$section', subject='$subject', room='$room', start_time='$start', end_time='$end'
            WHERE id=$id";

    if(mysqli_query($conn, $sql)){
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }
}
?>