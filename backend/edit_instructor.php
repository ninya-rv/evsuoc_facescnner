<?php
include "db.php";

if(isset(
    $_POST['id'],
    $_POST['year_level'],
    $_POST['section'],
    $_POST['day'],
    $_POST['subject'],
    $_POST['room'],
    $_POST['start_time'],
    $_POST['end_time']
)){

    $id = pg_escape_string($conn, $_POST['id']);
    $year = pg_escape_string($conn, $_POST['year_level']);
    $section = pg_escape_string($conn, $_POST['section']);
    $day = pg_escape_string($conn, $_POST['day']);
    $subject = pg_escape_string($conn, $_POST['subject']);
    $room = pg_escape_string($conn, $_POST['room']);
    $start = pg_escape_string($conn, $_POST['start_time']);
    $end = pg_escape_string($conn, $_POST['end_time']);

    $sql = "
        UPDATE instructor_assignment
        SET
            year_level='$year',
            section='$section',
            day='$day',
            subject='$subject',
            room='$room',
            start_time='$start',
            end_time='$end'
        WHERE id='$id'
    ";

    if(pg_query($conn, $sql)){

        echo "success";

    } else {

        echo "error";

    }
}
?>