<?php
session_start();
include "../../backend/db.php";

date_default_timezone_set("Asia/Manila");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

if ($_SESSION['role'] !== 'instructor') {
    header("Location: ../admin/dashboard.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];

$instructorQuery = "
    SELECT name, email
    FROM users
    WHERE id = '$instructor_id'
    AND role = 'instructor'
    LIMIT 1
";

$instructorResult = pg_query($conn, $instructorQuery);

if ($instructorResult && pg_num_rows($instructorResult) > 0) {

    $instructorData = pg_fetch_assoc($instructorResult);

    $instructorName  = $instructorData['name'];
    $instructorEmail = $instructorData['email'];

    $nameParts = explode(" ", trim($instructorName));

    $initials = "";

    foreach ($nameParts as $part) {

        $initials .= strtoupper(substr($part, 0, 1));

        if (strlen($initials) >= 2) {
            break;
        }
    }

} else {

    $instructorName  = $_SESSION['name'] ?? 'Instructor';
    $instructorEmail = 'No email found';
    $initials        = 'IN';
}

$instructor_name = pg_escape_string($conn, $instructorName);

$subjectQuery = "
    SELECT DISTINCT subject
    FROM instructor_assignment
    WHERE LOWER(TRIM(instructor_name)) = LOWER(TRIM('$instructor_name'))
    ORDER BY subject ASC
";

$subjectResult = pg_query($conn, $subjectQuery);

$subjectList = [];

if ($subjectResult && pg_num_rows($subjectResult) > 0) {

    while ($subjectRow = pg_fetch_assoc($subjectResult)) {
        $subjectList[] = $subjectRow['subject'];
    }
}

$currentDate = date("Y-m-d");
$currentTime = date("H:i:s");

$assignmentQuery = "
    SELECT *
    FROM instructor_assignment
    WHERE LOWER(TRIM(instructor_name)) = LOWER(TRIM('$instructor_name'))
";

$assignmentResult = pg_query($conn, $assignmentQuery);

while ($assignment = pg_fetch_assoc($assignmentResult)) {

    $subject    = pg_escape_string($conn, $assignment['subject']);
    $year_level = pg_escape_string($conn, $assignment['year_level']);
    $section    = pg_escape_string($conn, $assignment['section']);
    $end_time   = $assignment['end_time'];

    // CHECK IF CLASS ALREADY ENDED
    if (strtotime($currentTime) >= strtotime($end_time)) {

        // GET ALL ACTIVE STUDENTS
        $studentQuery = "
            SELECT *
            FROM students
            WHERE status = 'active'
            AND LOWER(TRIM(year)) = LOWER(TRIM('$year_level'))
            AND LOWER(TRIM(section)) = LOWER(TRIM('$section'))
        ";

        $studentResult = pg_query($conn, $studentQuery);

        while ($student = pg_fetch_assoc($studentResult)) {

            $student_id = pg_escape_string($conn, $student['student_id']);
            $name       = pg_escape_string($conn, $student['name']);
            $email      = pg_escape_string($conn, $student['email']);

            // CHECK IF STUDENT ALREADY HAS ATTENDANCE TODAY
            $attendanceCheckQuery = "
                SELECT *
                FROM attendance
                WHERE student_id = '$student_id'
                AND subject = '$subject'
                AND date = '$currentDate'
                LIMIT 1
            ";

            $attendanceCheckResult = pg_query($conn, $attendanceCheckQuery);

            // IF NO RECORD -> INSERT ABSENT
            if (pg_num_rows($attendanceCheckResult) == 0) {

                $insertAbsentQuery = "
                    INSERT INTO attendance (
                        student_id,
                        name,
                        email,
                        instructor_name,
                        subject,
                        year_level,
                        section,
                        date,
                        time_in,
                        time_out,
                        status
                    )
                    VALUES (
                        '$student_id',
                        '$name',
                        '$email',
                        '$instructor_name',
                        '$subject',
                        '$year_level',
                        '$section',
                        '$currentDate',
                        NULL,
                        NULL,
                        'Absent'
                    )
                ";

                pg_query($conn, $insertAbsentQuery);
            }
            else {

                // UPDATE RECORD IF NO TIME IN
                $attendanceData = pg_fetch_assoc($attendanceCheckResult);

                if (
                    empty($attendanceData['time_in']) &&
                    empty($attendanceData['time_out'])
                ) {

                    $attendance_id = $attendanceData['id'];

                    $updateAbsentQuery = "
                        UPDATE attendance
                        SET status = 'Absent',
                            time_in = NULL,
                            time_out = NULL
                        WHERE id = '$attendance_id'
                    ";

                    pg_query($conn, $updateAbsentQuery);
                }
            }
        }
    }
}

$attendanceQuery = "
    SELECT *
    FROM attendance
    WHERE instructor_name='$instructor_name'
    ORDER BY date DESC, time_in DESC
";

$attendanceResult = pg_query($conn, $attendanceQuery);

$attendanceList = [];

while ($row = pg_fetch_assoc($attendanceResult)) {
    $attendanceList[] = $row;
}

$present = count(array_filter(
    $attendanceList,
    fn($a) => $a['status'] == 'Present'
));

$absent = count(array_filter(
    $attendanceList,
    fn($a) => $a['status'] == 'Absent'
));

$late = count(array_filter(
    $attendanceList,
    fn($a) => $a['status'] == 'Late'
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-BSIT Attendance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header class="header">
    <div class="logo-title">
        <img src="/css/EVSU_Official_Logo.png" alt="EVSU Logo">
        <h2>EVSU-BSIT</h2>
    </div>

    <div class="profile-wrapper">
        <div class="profile" id="profileBtn"><?php echo htmlspecialchars($initials); ?></div>

        <div class="profile-dropdown" id="profileDropdown" style="display:none;">
            <div class="profile-header">
                <div class="profile-circle"><?php echo htmlspecialchars($initials); ?></div>
                <br>
                <h4><?php echo htmlspecialchars($instructorName); ?></h4>
                <p><?php echo htmlspecialchars($instructorEmail); ?></p>
                <span class="badge">INSTRUCTOR</span>
            </div>

            <div class="profile-actions">
                <a href="../../index.php">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>

<div class="container">
<div class="sidebar">
    <ul>
        <li>
            <a href="/frontend/instructor/dashboard.php">
                <i class="fa-solid fa-gauge"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="/frontend/instructor/attendance.php" class="active">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Attendance</span>
            </a>
        </li>
        <li>
            <a href="/frontend/instructor/scanner.php">
                <i class="fa-solid fa-camera"></i>
                <span>Face Scanner</span>
            </a>
        </li>
    </ul>
</div>
<div class="main">
    <h3>Attendance</h3>
    <div class="cards">
        <div class="card">
            <h4>Present</h4>
            <p><?php 
                $present = count(array_filter($attendanceList, fn($a) => $a['status'] == 'Present')); 
                echo $present;
            ?></p>
        </div>

        <div class="card">
            <h4>Absent</h4>
            <p><?php 
                $absent = count(array_filter($attendanceList, fn($a) => $a['status'] == 'Absent')); 
                echo $absent;
            ?></p>
        </div>

        <div class="card">
            <h4>Late</h4>
            <p><?php 
                $late = count(array_filter($attendanceList, fn($a) => $a['status'] == 'Late')); 
                echo $late;
            ?></p>
        </div>
    </div>
    <div class="student-section">
        <h4>Student Attendance</h4>
        <br>
            <div class="search-filter">
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search students..."
                >
                <div class="filter-actions">

                    <button class="icon-btn" id="filterToggle" title="Filter">
                        <i class="fa-solid fa-filter"></i>
                    </button>

                    <button class="icon-btn" id="downloadPDF" title="Download PDF">
                        <i class="fa-solid fa-file-pdf"></i>
                    </button>
                </div>
            </div>
        <div class="filter-panel" id="filterPanel">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Year Level</label>
                    <select id="filterYear">
                        <option value="">All</option>
                        <option value="1st">1st</option>
                        <option value="2nd">2nd</option>
                        <option value="3rd">3rd</option>
                        <option value="4th">4th</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Section</label>
                    <select id="filterSection">
                        <option value="">All</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option> 
                        <option value="D">D</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" id="filterDate">
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus">
                        <option value="">All</option>
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Late">Late</option>
                    </select>
                </div>
            </div>
        </div>
        <table class="student-table" data-page="attendance">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Year</th>
                    <th>Section</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="attendanceTable">
                <?php if (!empty($attendanceList)): ?>
                    <?php foreach ($attendanceList as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo htmlspecialchars($row['year_level']); ?></td>
                            <td><?php echo htmlspecialchars($row['section']); ?></td>
                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                            <td>
                                <?php
                                    echo (!empty($row['time_in']) && $row['time_in'] != '00:00:00')
                                        ? date("g:i A", strtotime($row['time_in']))
                                        : 'No Data';
                                ?>
                            </td>

                            <td>
                                <?php
                                    echo (!empty($row['time_out']) && $row['time_out'] != '00:00:00')
                                        ? date("g:i A", strtotime($row['time_out']))
                                        : 'No Data';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align:center;">No attendance records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<script src="/backend/script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
    const profileBtn = document.getElementById("profileBtn");
    const dropdown = document.getElementById("profileDropdown");

    profileBtn.addEventListener("click", () => {
        dropdown.style.display =
            dropdown.style.display === "block" ? "none" : "block";
    });
    document.addEventListener("click", function(e) {
        if (!profileBtn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = "none";
        }
    });
    document.getElementById("downloadPDF").addEventListener("click", function () {

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    let tableData = [];
    const rows = document.querySelectorAll("#attendanceTable tr");

    rows.forEach(row => {
        if (row.style.display === "none") return;

        const cols = row.querySelectorAll("td");
        if (cols.length === 10) {

            tableData.push([
                cols[0].textContent.trim(), 
                cols[1].textContent.trim(), 
                cols[2].textContent.trim(), 
                cols[3].textContent.trim(),
                cols[4].textContent.trim(),
                cols[5].textContent.trim(), 
                cols[6].textContent.trim(), 
                cols[7].textContent.trim(), 
                cols[8].textContent.trim(), 
                cols[9].textContent.trim()  
            ]);
        }
    });

    // Title
    doc.setFontSize(14);
    doc.text("Attendance", 14, 15);

    // Table
    doc.autoTable({
        startY: 25,
        head: [[
            "Student ID",
            "Name",
            "Email",
            "Subject",
            "Year",
            "Section",
            "Date",
            "Time In",
            "Time Out",
            "Status"
        ]],
        body: tableData,
        theme: "grid",
        headStyles: {
            fillColor: [128, 0, 0]
        },
        styles: {
            fontSize: 8
        }
    });

    doc.save("attendance.pdf");
});
</script>
</body>
</html>