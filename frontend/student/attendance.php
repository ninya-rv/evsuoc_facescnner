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

$search = $_GET['search'] ?? '';

$attendanceQuery = "
    SELECT *
    FROM attendance
    WHERE student_id = '$student_id'
";

$statusFilter = $_GET['status'] ?? '';

if (!empty($search)) {

    $attendanceQuery .= "
        AND (
            LOWER(subject) LIKE LOWER('%$search%')
            OR LOWER(status) LIKE LOWER('%$search%')
            OR LOWER(date::text) LIKE LOWER('%$search%')
        )
    ";
}

if (!empty($statusFilter)) {

    $attendanceQuery .= "
        AND status = '$statusFilter'
    ";
}

$attendanceQuery .= "
    ORDER BY created_at DESC
";

$attendanceResult = pg_query(
    $conn,
    $attendanceQuery
);

$totalPresent = pg_fetch_assoc(
    pg_query(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM attendance
        WHERE student_id = '$student_id'
        AND status = 'Present'
        "
    )
);

$totalLate = pg_fetch_assoc(
    pg_query(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM attendance
        WHERE student_id = '$student_id'
        AND status = 'Late'
        "
    )
);

$totalAbsent = pg_fetch_assoc(
    pg_query(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM attendance
        WHERE student_id = '$student_id'
        AND status = 'Absent'
        "
    )
);
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Attendance</title>
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

        <a href="attendance.php" class="active">
            <i class="fa-solid fa-calendar-check"></i>
            Attendance
        </a>

        <a href="records.php">
            <i class="fa-solid fa-clock"></i>
            Records
        </a>

    </div>

    <div class="main" id="mainContent">

        <div class="cards">

    <div class="card">

        <div style="
            display:flex;
            justify-content:space-between;
            align-items:center;
        ">

            <div>

                <h4>Total Present</h4>

                <p>
                    <?php echo $totalPresent['total']; ?>
                </p>

            </div>

            <i class="fa-solid fa-circle-check" style="
                font-size:45px;
                color:#198754;
            "></i>

        </div>

    </div>

    <div class="card">

        <div style="
            display:flex;
            justify-content:space-between;
            align-items:center;
        ">

            <div>

                <h4>Total Late</h4>

                <p>
                    <?php echo $totalLate['total']; ?>
                </p>

            </div>

            <i class="fa-solid fa-clock" style="
                font-size:45px;
                color:#fd7e14;
            "></i>

        </div>

    </div>

    <div class="card">

        <div style="
            display:flex;
            justify-content:space-between;
            align-items:center;
        ">

            <div>

                <h4>Total Absent</h4>

                <p>
                    <?php echo $totalAbsent['total']; ?>
                </p>

            </div>

            <i class="fa-solid fa-circle-xmark" style="
                font-size:45px;
                color:#dc3545;
            "></i>

        </div>

    </div>

    <?php

        $attendanceRate = 0;

        $totalAll =
            $totalPresent['total'] +
            $totalLate['total'] +
            $totalAbsent['total'];

        if ($totalAll > 0) {

            $attendanceRate = round(
                (
                    (
                        $totalPresent['total'] +
                        $totalLate['total']
                    ) / $totalAll
                ) * 100
            );
        }

        ?>

        <div class="card">

            <div style="
                display:flex;
                justify-content:space-between;
                align-items:center;
            ">

                <div>

                    <h4>Attendance Rate</h4>

                    <p>
                        <?php echo $attendanceRate; ?>%
                    </p>

                </div>

                <i class="fa-solid fa-chart-line" style="
                    font-size:45px;
                    color:#800000;
                "></i>

            </div>

        </div>

    </div>

        <div class="section">

            <h2 style="margin-bottom:20px;">Attendance Records</h2>

            <!-- RIGHT SIDE SEARCH + FILTER (LIKE SCHEDULE) -->
            <div class="search-filter">

                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search subject, status, date..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >

                <button class="filter-btn" id="filterToggle">
                    <i class="fa fa-filter"></i>
                </button>

                <button class="filter-btn" id="toggleView">
                    <i class="fa fa-grip"></i>
                </button>

            </div>

            <!-- FILTER PANEL -->
            <div class="filter-panel" id="filterPanel">

                <div class="filter-grid">

                    <div class="filter-group">
                        <label>Status</label>
                        <select id="filterStatus">
                            <option value="">All</option>
                            <option value="Present">Present</option>
                            <option value="Late">Late</option>
                            <option value="Absent">Absent</option>
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
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($attendanceResult && pg_num_rows($attendanceResult) > 0): ?>

                    <?php while($attendance = pg_fetch_assoc($attendanceResult)): ?>

                        <?php

                        $statusClass = '';

                        if ($attendance['status'] == 'Present') {
                            $statusClass = 'present';
                        } elseif ($attendance['status'] == 'Late') {
                            $statusClass = 'late';
                        } else {
                            $statusClass = 'absent';
                        }

                        ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($attendance['subject']); ?>
                            </td>

                            <td>
                                <?php
                                echo date(
                                    "l",
                                    strtotime($attendance['date'])
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($attendance['date']); ?>
                            </td>

                            <td>

                                <?php

                                if (!empty($attendance['time_in'])) {

                                    echo date(
                                        "g:i A",
                                        strtotime($attendance['time_in'])
                                    );

                                } else {

                                    echo "—";
                                }

                                ?>

                            </td>

                            <td>

                                <?php

                                if (!empty($attendance['time_out'])) {

                                    echo date(
                                        "g:i A",
                                        strtotime($attendance['time_out'])
                                    );

                                } else {

                                    echo "—";
                                }

                                ?>

                            </td>

                            <td>

                                <span class="status-badge <?php echo $statusClass; ?>">

                                    <?php echo htmlspecialchars($attendance['status']); ?>

                                </span>

                            </td>

                            <td>

                                <?php

                                if ($attendance['status'] == 'Present') {

                                    echo "
                                        <span style='color:#198754;font-weight:bold;'>
                                            On Time
                                        </span>
                                    ";

                                } elseif ($attendance['status'] == 'Late') {

                                    echo "
                                        <span style='color:#fd7e14;font-weight:bold;'>
                                            Late Arrival
                                        </span>
                                    ";

                                } else {

                                    echo "
                                        <span style='color:#dc3545;font-weight:bold;'>
                                            No Attendance
                                        </span>
                                    ";
                                }

                                ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="5" style="text-align:center;">
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

const profileBtn =
    document.getElementById("profileBtn");

const dropdown =
    document.getElementById("profileDropdown");

const mainContent =
    document.getElementById("mainContent");

let dynamicContainer =
    document.getElementById("dynamicContent");

// PROFILE DROPDOWN TOGGLE
if (profileBtn && dropdown) {

    profileBtn.addEventListener("click", (e) => {
        e.stopPropagation();

        dropdown.style.display =
            dropdown.style.display === "block"
                ? "none"
                : "block";
    });

    document.addEventListener("click", function (e) {

        if (
            !profileBtn.contains(e.target) &&
            !dropdown.contains(e.target)
        ) {
            dropdown.style.display = "none";
        }
    });
}


// LOAD PAGE (LIKE SCHEDULE SYSTEM)
function loadPage(page) {

    if (!mainContent) return;

    // hide main page
    mainContent.style.display = "none";

    fetch(page)
        .then(res => res.text())
        .then(html => {

            if (!dynamicContainer) {
                dynamicContainer = document.createElement("div");
                dynamicContainer.id = "dynamicContent";
                document.querySelector(".container").appendChild(dynamicContainer);
            }

            dynamicContainer.innerHTML = html;

            // close dropdown
            if (dropdown) {
                dropdown.style.display = "none";
            }
        })
        .catch(err => {
            console.error("Page load error:", err);
            alert("Failed to load page.");
        });
}


// OPTIONAL: restore main page (useful if needed later)
function showMainPage() {

    if (mainContent) {
        mainContent.style.display = "block";
    }

    if (dynamicContainer) {
        dynamicContainer.innerHTML = "";
    }
}

</script>

</body>
</html>