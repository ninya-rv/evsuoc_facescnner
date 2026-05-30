<?php
session_start();
include "../../backend/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

include "../../backend/mail.php";

if (isset($_GET['toggle']) && isset($_GET['id'])) {

    $student_id = pg_escape_string($conn, $_GET['id']);

    $checkStudent = pg_query($conn, "
        SELECT status, email, first_name, last_name, photo
        FROM students
        WHERE student_id='$student_id'
    ");

    if ($checkStudent && pg_num_rows($checkStudent) > 0) {

        $student = pg_fetch_assoc($checkStudent);

        $newStatus = ($student['status'] === 'Active')
            ? 'Inactive'
            : 'Active';

        pg_query($conn, "
            UPDATE students
            SET status='$newStatus'
            WHERE student_id='$student_id'
        ");

        if ($newStatus === 'Active') {
            $emailSent = sendActivationEmail(
                $student['email'],
                $student['first_name'],
                $student['last_name'],
                $student['photo']
            );

            if (!$emailSent) {
                error_log("Email failed for student: " . $student_id);
            }
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

        $adminInitials = strtoupper(
            substr($nameParts[0], 0, 2)
        );
    }
}

$adminEmail =
    $_SESSION['email']
    ?? 'admin@evsu.edu.ph';
$totalStudentsQuery = "
    SELECT COUNT(*) as total
    FROM students
";

$totalStudentsResult = pg_query(
    $conn,
    $totalStudentsQuery
);

$totalStudents = pg_fetch_assoc(
    $totalStudentsResult
)['total'];

$activeQuery = "
    SELECT COUNT(*) as total
    FROM students
    WHERE status='Active'
";

$activeResult = pg_query(
    $conn,
    $activeQuery
);

$activeStudents = pg_fetch_assoc(
    $activeResult
)['total'];

$inactiveQuery = "
    SELECT COUNT(*) as total
    FROM students
    WHERE status='Inactive'
";

$inactiveResult = pg_query(
    $conn,
    $inactiveQuery
);

$inactiveStudents = pg_fetch_assoc(
    $inactiveResult
)['total'];

$combinedQuery = "
    SELECT
        student_id AS id,
        photo,
        first_name,
        last_name,
        email,
        year,
        section,
        status
    FROM students
    ORDER BY first_name ASC, last_name ASC
";


$result = pg_query($conn, $combinedQuery);

$combinedList = [];

while ($row = pg_fetch_assoc($result)) {
    $combinedList[] = $row;
}

$totalSubjectsQuery = "
    SELECT COUNT(DISTINCT subject) AS total
    FROM instructor_assignment
";

$totalSubjectsResult = pg_query(
    $conn,
    $totalSubjectsQuery
);

$totalSubjects = pg_fetch_assoc(
    $totalSubjectsResult
)['total'];
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
            padding:8px;
            border-radius:50%;
            background:transparent;
            cursor:pointer;
            font-size:13px;
            font-weight:bold;
            transition:0.3s ease;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:38px;
            height:38px;
        }

        .action-btn i {
            color:#800000;
            font-size:16px;
        }

        .btn-deactivate,
        .btn-activate,
        .btn-edit {
            background:transparent;
        }

        .action-btn:hover{
            transform:scale(1.1);
            opacity:0.9;
        }

        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            border-radius: 10px;
            padding: 20px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 18px;
            font-size: 22px;
            cursor: pointer;
            color: #444;
        }

        .modal-content h4 {
            margin-top: 0;
            margin-bottom: 4px;
            font-size: 22px;
            color: #3f1f1f;
        }

        .modal-content p {
            margin: 0 0 18px;
            color: #5a5a5a;
            line-height: 1.5;
            font-size: 14px;
        }

        .modal-content .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .modal-content .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .modal-content label {
            font-weight: 600;
            color: #4f4f4f;
            font-size: 13px;
        }

        .modal-content .form-row input,
        .modal-content .form-row select {
            padding: 12px 14px;
            border: 1px solid #d1c7c7;
            border-radius: 10px;
            font-size: 14px;
            width: 100%;
            background: #faf8f6;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .modal-content .form-row input:focus,
        .modal-content .form-row select:focus {
            outline: none;
            border-color: #8c4b4b;
            box-shadow: 0 0 0 3px rgba(140, 75, 75, 0.12);
            background: #fff;
        }

        .modal-actions {
            margin-top: 24px;
            text-align: right;
        }

        .primary-btn {
            border: none;
            background: #800000;
            color: #fff;
            padding: 12px 22px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, opacity 0.2s ease, background 0.2s ease;
        }

        .primary-btn:hover {
            transform: translateY(-1px);
            opacity: 0.95;
            background: #5f0000;
        }

        @media (min-width: 520px) {
            .modal-content .form-row {
                grid-template-columns: 1fr 1fr;
            }

            .modal-content .form-row .form-group.full-width {
                grid-column: 1 / -1;
            }
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
                <a href="../../index.php">
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
                    <span>Instructors</span>
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
            <div class="card">
                <h4>Total Subject Codes</h4>
                <p><?php echo $totalSubjects; ?></p>
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
                        <th>Photo</th>
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

                        <tr data-id="<?php echo htmlspecialchars($row['id']); ?>">

                            <td>
                                <img src="<?php echo htmlspecialchars($row['photo']); ?>" alt="Student Photo" class="student-photo">
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['id']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
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
                                        title="Deactivate"
                                    >
                                        <i class="fa-solid fa-user-slash"></i>
                                    </a>

                                <?php else: ?>
                                    <a
                                        href="?toggle=1&id=<?php echo urlencode($row['id']); ?>"
                                        class="action-btn btn-activate"
                                        title="Activate"
                                    >
                                        <i class="fa-solid fa-user-check"></i>
                                    </a>
                                <?php endif; ?>

                                <a
                                    href="#"
                                    class="action-btn btn-edit editStudentBtn"
                                    title="Edit"
                                >
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
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

            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span id="closeModal" class="modal-close">&times;</span>
                    <h4>Edit Student</h4>
                    <form id="editStudentForm" action="../../backend/update_student.php" method="POST">
                        <input type="hidden" name="student_id" id="editStudentId">
                        <p>Update the student’s email, year level, and section with accurate details.</p>
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="editEmail">Email</label>
                                <input
                                    type="email"
                                    name="email"
                                    id="editEmail"
                                    placeholder="Student email"
                                    required
                                />
                            </div>

                            <div class="form-group">
                                <label for="editYear">Year</label>
                                <select name="year" id="editYear" required>
                                    <option value="">Select year</option>
                                    <option value="1st year">1st Year</option>
                                    <option value="2nd year">2nd Year</option>
                                    <option value="3rd year">3rd Year</option>
                                    <option value="4th year">4th Year</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="editSection">Section</label>
                                <select name="section" id="editSection" required>
                                    <option value="">Select section</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-actions">
                            <button type="submit" class="primary-btn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
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
const editModal = document.getElementById("editModal");
const editStudentId = document.getElementById("editStudentId");
const editEmail = document.getElementById("editEmail");
const editYear = document.getElementById("editYear");
const editSection = document.getElementById("editSection");
const closeModal = document.getElementById("closeModal");

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

function openEditModal(event) {
    event.preventDefault();
    const button = event.currentTarget;
    const row = button.closest("tr");

    if (!row) return;

    const cells = row.querySelectorAll("td");
    editStudentId.value = row.dataset.id || cells[0].textContent.trim();
    editEmail.value = cells[2].textContent.trim();
    editYear.value = cells[3].textContent.trim();
    editSection.value = cells[4].textContent.trim();
    editModal.style.display = "flex";
}

function closeEditModal() {
    editModal.style.display = "none";
}

const editButtons = document.querySelectorAll(".editStudentBtn");
editButtons.forEach(button => {
    button.addEventListener("click", openEditModal);
});

closeModal.addEventListener("click", closeEditModal);
window.addEventListener("click", event => {
    if (event.target === editModal) {
        closeEditModal();
    }
});

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
                    cells[1].textContent.trim(), 
                    cells[2].textContent.trim(), 
                    cells[3].textContent.trim(), 
                    cells[4].textContent.trim(), 
                    cells[5].textContent.trim(), 
                    cells[6].textContent.trim()
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