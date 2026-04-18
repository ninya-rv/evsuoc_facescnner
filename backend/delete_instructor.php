<?php
include "db.php";

if(isset($_POST['id'])){
    $id = $_POST['id'];
    $sql = "DELETE FROM instructor_assignment WHERE id=$id";
    if(mysqli_query($conn, $sql)){
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }
}
?>