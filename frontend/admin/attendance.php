<?php
session_start();
include "../../backend/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
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

        $adminInitials = strtoupper(
            substr($nameParts[0], 0, 2)
        );
    }
}

$adminEmail =
    $_SESSION['email']
    ?? 'admin@evsu.edu.ph';
$attendanceQuery = "
    SELECT *
    FROM attendance
    ORDER BY date DESC, time_in DESC
";

$attendanceResult = pg_query(
    $conn,
    $attendanceQuery
);

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

<div class="header">
    
    <div class="logo-title">
        <img src="/css/EVSU_Official_Logo.png" alt="EVSU logo">
        <h2>EVSU-BSIT</h2>
    </div>

    <div class="profile-wrapper">
        <div class="profile" id="profileBtn"><?php echo htmlspecialchars($adminInitials); ?></div>

        <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-header">
                <div class="profile-circle"><?php echo htmlspecialchars($adminInitials); ?></div>
                <br>
                <h4>System Administrator</h4>
                <p><?php echo htmlspecialchars($adminEmail); ?></p>
                <span class="badge">ADMINISTRATOR</span>
            </div>

            <div class="profile-actions">
                <a href="../../index.php">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </div>
    </div>

    <div class="container">

    <div class="sidebar">
        <ul>
             <li>
                <a href="/frontend/admin/dashboard.php" class="active">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="attendance.php" class="active">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            </li>

            <li>
                <a href="users.php">
                    <i class="fa-solid fa-users"></i>
                    <span>Users</span>
                </a>
            </li>

            <li>
                <a href="instructor_assignment.php">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Instructor Assignment</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="main">
        <h3>Attendance</h3>

        <!-- Cards -->
        <div class="cards">
            <div class="card">
                <h4>Present</h4>
                <p><?php echo $present; ?></p>
            </div>

            <div class="card">
                <h4>Absent</h4>
                <p><?php echo $absent; ?></p>
            </div>

            <div class="card">
                <h4>Late</h4>
                <p><?php echo $late; ?></p>
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
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
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

            <table class="student-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Year</th>
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
                            <td><?php echo htmlspecialchars($row['email']);?></td>
                            <td><?php echo htmlspecialchars($row['year_level']); ?></td>
                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                            <td><?php echo !empty($row['time_in']) ? date("g:i A", strtotime($row['time_in'])) : '-'; ?></td>
                            <td><?php echo !empty($row['time_out']) ? date("g:i A", strtotime($row['time_out'])) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">No attendance records found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="/backend/script.js"></script>
    <script>
    const searchInput = document.getElementById("searchInput");
    const filterYear = document.getElementById("filterYear");
    const filterDate = document.getElementById("filterDate");
    const filterStatus = document.getElementById("filterStatus");

    const table = document.getElementById("attendanceTable");
    const rows = table.getElementsByTagName("tr");

    function filterTable() {
        const searchValue = searchInput.value.toLowerCase();
        const yearValue = filterYear.value.toLowerCase();
        const dateValue = filterDate.value;
        const statusValue = filterStatus.value.toLowerCase();

        for (let i = 0; i < rows.length; i++) {
            let cols = rows[i].getElementsByTagName("td");

            if (cols.length === 0) continue;

            let studentID = cols[0].textContent.toLowerCase();
            let name = cols[1].textContent.toLowerCase();
            let email = cols[2].textContent.toLowerCase();
            let year = cols[3].textContent.toLowerCase();
            let date = cols[4].textContent;
            let status = cols[7] ? cols[7].textContent.toLowerCase() : "";

            let matchSearch =
                studentID.includes(searchValue) ||
                name.includes(searchValue) ||
                email.includes(searchValue);

            let matchYear = !yearValue || year.includes(yearValue);
            let matchDate = !dateValue || date === dateValue;
            let matchStatus = !statusValue || status === statusValue;

            if (matchSearch && matchYear && matchDate && matchStatus) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
    searchInput.addEventListener("keyup", filterTable);
    filterYear.addEventListener("change", filterTable);
    filterDate.addEventListener("change", filterTable);
    filterStatus.addEventListener("change", filterTable);

    const profileBtn = document.getElementById("profileBtn");
    const dropdown = document.getElementById("profileDropdown");

    if (profileBtn && dropdown) {
        profileBtn.addEventListener("click", () => {
            dropdown.style.display =
                dropdown.style.display === "block" ? "none" : "block";
        });

        document.addEventListener("click", function(e) {
            if (!profileBtn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = "none";
            }
        });
    }
    const downloadPDF = document.getElementById("downloadPDF");

downloadPDF.addEventListener("click", () => {

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    let tableData = [];

    const rows = document.querySelectorAll("#attendanceTable tr");

    rows.forEach(row => {

        if (row.style.display !== "none") {

            const cells = row.querySelectorAll("td");
            if (cells.length >= 8) {

                tableData.push([
                    cells[0].textContent.trim(), 
                    cells[1].textContent.trim(), 
                    cells[2].textContent.trim(), 
                    cells[3].textContent.trim(), 
                    cells[4].textContent.trim(), 
                    cells[5].textContent.trim(), 
                    cells[6].textContent.trim(), 
                    cells[7].textContent.trim()  
                ]);
            }
        }
    });

    doc.setFontSize(16);
    doc.text("Attendance", 14, 15);

    doc.autoTable({
        startY: 25,

        head: [[
            "Student ID",
            "Name",
            "Email",
            "Year",
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
            fontSize: 9
        }
    });

    doc.save("attendance.pdf");
});
    </script>
</html>