<?php
include "db.php";

if (isset($_POST['id'])) {

    $id = (int) $_POST['id'];

    $sql = "
        DELETE FROM instructor_assignment
        WHERE id = $id
    ";

    $result = pg_query($conn, $sql);

    if ($result) {

        echo "success";

    } else {

        echo "error: " . pg_last_error($conn);
    }

} else {

    echo "Invalid request.";
}
?>