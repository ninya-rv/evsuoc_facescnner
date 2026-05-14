<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

$id = pg_escape_string($conn, $_GET['id']);
$status = pg_escape_string($conn, $_GET['status']);

$sql = "
    UPDATE users
    SET status='$status'
    WHERE id='$id'
";

$result = pg_query($conn, $sql);

if ($result) {

    header("Location: ../frontend/admin/users.php");
    exit;

} else {

    die("Failed to update status.");

}
?>