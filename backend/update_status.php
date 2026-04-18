<?php
include "db.php";

$id = $_GET['id'];
$status = $_GET['status'];

$sql = "UPDATE users SET status='$status' WHERE id='$id'";
mysqli_query($conn,$sql);

header("Location: ../frontend/admin/users.php");
?>