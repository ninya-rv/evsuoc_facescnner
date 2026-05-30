<?php
session_start();
include "../../backend/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../index.php");
    exit;
}

date_default_timezone_set('Asia/Manila');

$student_id = $_SESSION['student_id'];

$studentQuery = "
    SELECT *
    FROM students
    WHERE student_id = '$student_id'
    LIMIT 1
";

$studentResult = pg_query($conn, $studentQuery);

if (!$studentResult || pg_num_rows($studentResult) == 0) {
    die("Student not found.");
}

$student = pg_fetch_assoc($studentResult);

$fullName =
    $student['first_name'] . ' ' .
    $student['last_name'];

$email = $student['email'];

$photo = !empty($student['photo'])
    ? $student['photo']
    : "https://ui-avatars.com/api/?name=" .
      urlencode($fullName);

$query = "
    SELECT attendance.*
    FROM attendance
    INNER JOIN students
        ON students.student_id = attendance.student_id
    WHERE attendance.student_id = '$student_id'
    AND (
        attendance.status = 'Present'
        OR attendance.status = 'Late'
        OR attendance.status = 'Absent'
    )
    AND LOWER(TRIM(students.email)) LIKE '%@evsu.edu.ph'
    ORDER BY attendance.created_at DESC
";

$records = pg_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Records</title>
<link rel="stylesheet" href="../../css/student.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<div class="header">

    <div class="logo-title">

        <img
            src="/css/EVSU_Official_Logo.png"
            alt="EVSU Logo"
        >

        <h2>EVSU Student Portal</h2>

    </div>

    <div class="profile-wrapper">

        <div class="profile" id="profileBtn">

            <img
                src="<?php echo htmlspecialchars($photo); ?>"
            >

        </div>

        <div class="profile-dropdown" id="profileDropdown">

            <div class="profile-header">

                <div class="profile-circle">

                    <img
                        src="<?php echo htmlspecialchars($photo); ?>"
                    >

                </div>

                <h4>
                    <?php echo htmlspecialchars($fullName); ?>
                </h4>

                <p>
                    <?php echo htmlspecialchars($email); ?>
                </p>

                <span class="badge">
                    STUDENT
                </span>

            </div>

            <div class="account-menu">

                <a href="#" onclick="loadPage('upload_photo.php')">
                    <i class="fa-solid fa-image"></i>
                    Upload Photo
                </a>

                <a href="#" onclick="loadPage('account_settings.php')">
                    <i class="fa-solid fa-user-gear"></i>
                    Account Settings
                </a>

                <a href="#" onclick="loadPage('change_password.php')">
                    <i class="fa-solid fa-lock"></i>
                    Change Password
                </a>

                <a
                    href="../../index.php"
                    class="logout-btn"
                >
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>

            </div>

        </div>

    </div>

</div>

<div class="container">

    <div class="sidebar">

        <a href="dashboard.php">
            <i class="fa-solid fa-gauge"></i>
            Dashboard
        </a>

        <a href="schedule.php">
            <i class="fa-solid fa-calendar"></i>
            Schedule
        </a>

        <a href="attendance.php">
            <i class="fa-solid fa-calendar-check"></i>
            Attendance
        </a>

        <a href="records.php" class="active">
            <i class="fa-solid fa-clock"></i>
            Records
        </a>

    </div>

    <div class="main" id="mainContent">
    <div id="dynamicContent"></div>
        <div class="page-card">

            <h2>Late & Absence Records</h2>

            <!-- SEARCH + FILTER (LIKE SCHEDULE FORMAT) -->
            <div class="search-filter">

                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search subject, status, date..."
                >

                <button class="filter-btn" id="filterToggle">
                    <i class="fa fa-filter"></i>
                </button>

            </div>

            <!-- FILTER PANEL -->
            <div class="filter-panel" id="filterPanel">

                <div class="filter-grid">

                    <div class="filter-group">
                        <label>Status</label>
                        <select id="filterStatus">
                            <option value="">All</option>
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                        </select>
                    </div>

                </div>

            </div>

            <table class="student-table">

                <thead>

                    <tr>
                        <th>Subject</th>
                        <th>Day</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($records && pg_num_rows($records) > 0): ?>

                    <?php while($row = pg_fetch_assoc($records)): ?>

                        <?php

                        $statusClass = '';

                        if ($row['status'] == 'Present') {
                            $statusClass = 'present';
                        } elseif ($row['status'] == 'Late') {
                            $statusClass = 'late';
                        } else {
                            $statusClass = 'absent';
                        }

                        ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($row['subject']); ?>
                            </td>

                            <td>
                                <?php echo date(
                                    "l",
                                    strtotime($row['date'])
                                ); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['date']); ?>
                            </td>

                            <td>

                                <span class="status-badge <?php echo $statusClass; ?>">

                                    <?php echo htmlspecialchars($row['status']); ?>

                                </span>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="4" style="text-align:center;">
                            No attendance records found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>
    const searchInput = document.getElementById("searchInput");
const statusFilter = document.getElementById("filterStatus");
const filterToggle = document.getElementById("filterToggle");
const filterPanel = document.getElementById("filterPanel");

function filterRecords() {

    let search = (searchInput.value || "").toLowerCase();
    let status = statusFilter.value.toLowerCase();

    document.querySelectorAll(".record-row").forEach(row => {

        let subject = row.dataset.subject || "";
        let rowStatus = row.dataset.status || "";
        let date = row.dataset.date || "";

        let match =
            (subject.includes(search) ||
             rowStatus.includes(search) ||
             date.includes(search)) &&
            (!status || rowStatus === status);

        row.style.display = match ? "" : "none";
    });
}

searchInput.addEventListener("input", filterRecords);
statusFilter.addEventListener("change", filterRecords);

filterToggle.addEventListener("click", () => {
    filterPanel.style.display =
        filterPanel.style.display === "block" ? "none" : "block";
});

const profileBtn =
    document.getElementById("profileBtn");

const dropdown =
    document.getElementById("profileDropdown");

profileBtn.addEventListener("click", () => {

    dropdown.style.display =
        dropdown.style.display === "block"
        ? "none"
        : "block";
});

document.addEventListener("click", function(e) {

    if (
        !profileBtn.contains(e.target) &&
        !dropdown.contains(e.target)
    ) {
        dropdown.style.display = "none";
    }
});

function loadPage(page) {

    // hide main content (records table)
    document.getElementById("mainContent").style.display = "none";

    fetch(page)
        .then(res => res.text())
        .then(html => {

            document.getElementById("dynamicContent").innerHTML = html;

            // close dropdown
            document.getElementById("profileDropdown").style.display = "none";
        });
}

</script>

</body>
</html>