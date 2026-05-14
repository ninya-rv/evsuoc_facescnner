<?php

$host = "yamanote.proxy.rlwy.net";
$user = "root";
$password = "SarWFTEweHBQurbtsUKOytVrLQgyKveg";
$database = "railway";
$port = 42227;

$conn = mysqli_connect(
    $host,
    $user,
    $password,
    $database,
    $port
);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>