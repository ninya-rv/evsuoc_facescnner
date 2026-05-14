<?php
$host = "aws-1-ap-southeast-1.pooler.supabase.com";
$port = "6543";
$dbname = "postgres";
$user = "postgres.znjyxpbotlfyyxrykoeb";
$password = "evsuOCCscanner";

$conn = pg_connect("
    host=$host
    port=$port
    dbname=$dbname
    user=$user
    password=$password
    sslmode=require
");

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}
?>