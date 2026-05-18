<?php
include "db.php";

if (isset($_POST['assign'])) {

    $id = trim($_POST['edit_id'] ?? '');

    $instructor = pg_escape_string($conn, $_POST['instructor_name']);
    $year = pg_escape_string($conn, $_POST['year_level']);
    $section = pg_escape_string($conn, $_POST['section']);
    $day = pg_escape_string($conn, $_POST['day']);
    $subject = pg_escape_string($conn, $_POST['subject']);
    $room = pg_escape_string($conn, $_POST['room']);
    $start = pg_escape_string($conn, $_POST['start_time']);
    $end = pg_escape_string($conn, $_POST['end_time']);

    if (!empty($id)) {

        $id = (int)$id;

        $sql = "
            UPDATE instructor_assignment
            SET
                instructor_name = '$instructor',
                year_level = '$year',
                section = '$section',
                day = '$day',
                subject = '$subject',
                room = '$room',
                start_time = '$start',
                end_time = '$end'
            WHERE id = $id
        ";

    } else {

        $sql = "
            INSERT INTO instructor_assignment (
                instructor_name,
                year_level,
                section,
                day,
                subject,
                room,
                start_time,
                end_time
            )
            VALUES (
                '$instructor',
                '$year',
                '$section',
                '$day',
                '$subject',
                '$room',
                '$start',
                '$end'
            )
        ";
    }

    $result = pg_query($conn, $sql);

    if ($result) {

        header("Location: ../frontend/admin/instructor_assignment.php");
        exit();

    } else {

        echo "Database Error: " . pg_last_error($conn);
    }
}
?>