<?php
session_start();
include "../../backend/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /frontend/sign_in.html");
    exit;
}
include "../../backend/mail.php";

if (isset($_GET['toggle']) && isset($_GET['id'])) {

    $student_id = mysqli_real_escape_string($conn, $_GET['id']);

    $checkStudent = mysqli_query($conn, "
        SELECT status, email, name
        FROM students 
        WHERE student_id='$student_id'
    ");

    if ($checkStudent && mysqli_num_rows($checkStudent) > 0) {

        $student = mysqli_fetch_assoc($checkStudent);

        $newStatus = ($student['status'] === 'Active') ? 'Inactive' : 'Active';

        mysqli_query($conn, "
            UPDATE students
            SET status='$newStatus'
            WHERE student_id='$student_id'
        ");

        if ($newStatus === 'Active') {
            sendActivationEmail($student['email'], $student['name']);
        }
    }

    header("Location: dashboard.php");
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

$adminEmail = $_SESSION['email'] ?? 'aprilsheen.pinar@evsu.edu.ph';


$totalStudentsQuery = "
    SELECT COUNT(*) as total 
    FROM students
";

$totalStudents = mysqli_fetch_assoc(
    mysqli_query($conn, $totalStudentsQuery)
)['total'];

$activeQuery = "
    SELECT COUNT(*) as total
    FROM students
    WHERE status='Active'
";

$activeStudents = mysqli_fetch_assoc(
    mysqli_query($conn, $activeQuery)
)['total'];

$inactiveQuery = "
    SELECT COUNT(*) as total
    FROM students
    WHERE status='Inactive'
";

$inactiveStudents = mysqli_fetch_assoc(
    mysqli_query($conn, $inactiveQuery)
)['total'];

$combinedQuery = "
    SELECT
        student_id AS id,
        name,
        email,
        year,
        section,
        status
    FROM students
    ORDER BY name ASC
";

$result = mysqli_query($conn, $combinedQuery);

$combinedList = [];

while ($row = mysqli_fetch_assoc($result)) {
    $combinedList[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EVSU-BSIT Dashboard</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="/css/style.css">

    <style>

        .status-badge{
            padding:6px 12px;
            border-radius:20px;
            color:#fff;
            font-size:13px;
            font-weight:bold;
        }

        .status-active{
            background:#198754;
        }

        .status-inactive{
            background:#dc3545;
        }

        .action-btn{
            border:none;
            padding:8px 14px;
            border-radius:6px;
            color:white;
            cursor:pointer;
            font-size:13px;
            font-weight:bold;
            transition:0.3s ease;
            text-decoration:none;
            display:inline-block;
        }

        .btn-deactivate{
            background:#dc3545;
        }

        .btn-activate{
            background:#198754;
        }

        .action-btn:hover{
            transform:scale(1.05);
            opacity:0.9;
        }

    </style>
</head>

<body>

<header class="header">

    <div class="logo-title">
        <img src="/css/EVSU_Official_Logo.png" alt="EVSU Logo">
        <h2>EVSU-BSIT</h2>
    </div>

    <div class="profile-wrapper">

        <div class="profile" id="profileBtn">
            <?php echo htmlspecialchars($adminInitials); ?>
        </div>

        <div class="profile-dropdown" id="profileDropdown">

            <div class="profile-header">

                <div class="profile-circle">
                    <?php echo htmlspecialchars($adminInitials); ?>
                </div>

                <br>

                <h4>System Administrator</h4>

                <p><?php echo htmlspecialchars($adminEmail); ?></p>

                <span class="badge">ADMINISTRATOR</span>

            </div>

            <div class="profile-actions">
                <a href="../sign_in.html">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>
            </div>

        </div>

    </div>

</header>

<div class="container">

    <aside class="sidebar">

        <ul>

            <li>
                <a href="/frontend/admin/dashboard.php" class="active">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="attendance.php">
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

    </aside>

    <main class="main">

        <h3>Dashboard</h3>

        <div class="cards">

            <div class="card">
                <h4>Total Students</h4>
                <p><?php echo $totalStudents; ?></p>
            </div>

            <div class="card">
                <h4>Active Students</h4>
                <p><?php echo $activeStudents; ?></p>
            </div>

            <div class="card">
                <h4>Inactive Students</h4>
                <p><?php echo $inactiveStudents; ?></p>
            </div>

        </div>

        <section class="student-section">

            <h4>Student List</h4>

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
            <div
                class="filter-panel"
                id="filterPanel"
                style="display:none;"
            >
                <div class="filter-grid">
                    <div class="filter-group">

                        <label>Year Level</label>

                        <select id="filterYear">
                            <option value="">All</option>
                            <option value="1st year">1st Year</option>
                            <option value="2nd year">2nd Year</option>
                            <option value="3rd year">3rd Year</option>
                            <option value="4th year">4th Year</option>
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

                        <label>Status</label>

                        <select id="filterStatus">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
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
                        <th>Section</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody id="userTable">

                <?php if (!empty($combinedList)): ?>

                    <?php foreach ($combinedList as $row): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($row['id']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['email']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['year']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['section']); ?>
                            </td>

                            <td>

                                <?php if ($row['status'] === 'Active'): ?>

                                    <span class="status-badge status-active">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="status-badge status-inactive">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($row['status'] === 'Active'): ?>

                                    <a
                                        href="?toggle=1&id=<?php echo urlencode($row['id']); ?>"
                                        class="action-btn btn-deactivate"
                                    >
                                        Deactivate
                                    </a>

                                <?php else: ?>
                                    <a
                                        href="?toggle=1&id=<?php echo urlencode($row['id']); ?>"
                                        class="action-btn btn-activate"
                                    >
                                        Activate
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">
                            No records found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
<script>
const searchInput = document.getElementById("searchInput");
const filterYear = document.getElementById("filterYear");
const filterSection = document.getElementById("filterSection");
const filterStatus = document.getElementById("filterStatus");
const filterToggle = document.getElementById("filterToggle");
const filterPanel = document.getElementById("filterPanel");

function filterTable() {

    const searchValue = searchInput.value.toLowerCase().trim();
    const yearValue = filterYear.value.toLowerCase().trim();
    const sectionValue = filterSection.value.toLowerCase().trim();
    const statusValue = filterStatus.value.toLowerCase().trim();

    const rows = document.querySelectorAll("#userTable tr");

    rows.forEach(row => {

        const cells = row.querySelectorAll("td");

        if (cells.length < 7) return;

        const id = cells[0].textContent.toLowerCase().trim();
        const name = cells[1].textContent.toLowerCase().trim();
        const email = cells[2].textContent.toLowerCase().trim();
        const year = cells[3].textContent.toLowerCase().trim();
        const section = cells[4].textContent.toLowerCase().trim();
        const status = cells[5].textContent.toLowerCase().trim();

        const matchesSearch =
            searchValue === "" ||
            id.includes(searchValue) ||
            name.includes(searchValue) ||
            email.includes(searchValue) ||
            year.includes(searchValue) ||
            section.includes(searchValue) ||
            status.includes(searchValue);

        const matchesYear =
            yearValue === "" ||
            year.includes(yearValue);

        const matchesSection =
            sectionValue === "" ||
            section.includes(sectionValue);

        const matchesStatus =
            statusValue === "" ||
            status.includes(statusValue);

        if (
            matchesSearch &&
            matchesYear &&
            matchesSection &&
            matchesStatus
        ) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}
searchInput.addEventListener("input", filterTable);
filterYear.addEventListener("change", filterTable);
filterSection.addEventListener("change", filterTable);
filterStatus.addEventListener("change", filterTable);
filterToggle.addEventListener("click", () => {

    if (filterPanel.style.display === "block") {

        filterPanel.style.display = "none";

        filterToggle.innerHTML =
            '<i class="fa-solid fa-filter"></i>';

    } else {

        filterPanel.style.display = "block";

        filterToggle.innerHTML =
            '<i class="fa-solid fa-arrows-rotate"></i>';
    }
});
const profileBtn = document.getElementById("profileBtn");
const dropdown = document.getElementById("profileDropdown");

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
const downloadPDF = document.getElementById("downloadPDF");

downloadPDF.addEventListener("click", () => {

    const { jsPDF } = window.jspdf;

    const doc = new jsPDF();

    let tableData = [];

    const rows = document.querySelectorAll("#userTable tr");

    rows.forEach(row => {
        if (row.style.display !== "none") {

            const cells = row.querySelectorAll("td");

            if (cells.length >= 6) {

                tableData.push([
                    cells[0].textContent.trim(), 
                    cells[1].textContent.trim(), 
                    cells[2].textContent.trim(), 
                    cells[3].textContent.trim(), 
                    cells[4].textContent.trim(), 
                ]);
            }
        }
    });

    doc.setFontSize(16);

    doc.text("Student List", 14, 15);

    doc.autoTable({
        startY: 25,

        head: [[
            "Student ID",
            "Name",
            "Email",
            "Year",
            "Section",
        ]],

        body: tableData,

        theme: "grid",

        headStyles: {
            fillColor: [128, 0, 0]
        },

        styles: {
            fontSize: 10
        }
    });

    doc.save("filtered_students.pdf");
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</body>
</html>