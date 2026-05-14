<?php
session_start();
include "../../backend/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../sign_in.html");
    exit;
}

$adminName = trim($_SESSION['name'] ?? '');
$adminInitials = 'SA';

if ($adminName !== '') {
    $nameParts = preg_split('/\s+/', $adminName);

    if (count($nameParts) > 1) {
        $adminInitials = strtoupper(
            substr($nameParts[0], 0, 1) .
            substr(end($nameParts), 0, 1)
        );
    } else {
        $adminInitials = strtoupper(substr($nameParts[0], 0, 2));
    }
}

$adminEmail = $_SESSION['email'] ?? 'admin@evsu.edu.ph';


$totalUsersQuery = "
    SELECT COUNT(*) as total
    FROM users
    WHERE role = 'instructor'
";

$totalResult = pg_query($conn, $totalUsersQuery);

$totalRow = pg_fetch_assoc($totalResult);

$totalUsers = $totalRow['total'];


$activeUsersQuery = "
    SELECT COUNT(*) as active
    FROM users
    WHERE role = 'instructor'
    AND status='active'
";

$activeResult = pg_query($conn, $activeUsersQuery);

$activeRow = pg_fetch_assoc($activeResult);

$activeUsers = $activeRow['active'];


$inactiveUsersQuery = "
    SELECT COUNT(*) as inactive
    FROM users
    WHERE role = 'instructor'
    AND status='inactive'
";

$inactiveResult = pg_query($conn, $inactiveUsersQuery);

$inactiveRow = pg_fetch_assoc($inactiveResult);

$inactiveUsers = $inactiveRow['inactive'];
?>