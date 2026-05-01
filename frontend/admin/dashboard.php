<?php
session_start();
include "../../backend/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /frontend/sign_in.html");
    exit;
}

$adminName = trim($_SESSION['name'] ?? '');
$adminInitials = 'SA';
if ($adminName !== '') {
    $nameParts = preg_split('/\s+/', $adminName);
    if (count($nameParts) > 1) {
        $adminInitials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
    } else {
        $adminInitials = strtoupper(substr($nameParts[0], 0, 2));
    }
}
$adminEmail = $_SESSION['email'] ?? 'admin@evsu.edu.ph';

$totalStudentsQuery = "SELECT COUNT(*) as total FROM students";
$totalStudents = mysqli_fetch_assoc(mysqli_query($conn, $totalStudentsQuery))['total'];


$activeQuery = "SELECT COUNT(*) as total FROM students WHERE status='Active'";
$activeStudents = mysqli_fetch_assoc(mysqli_query($conn, $activeQuery))['total'];

$inactiveQuery = "SELECT COUNT(*) as total FROM students WHERE status='Inactive'";
$inactiveStudents = mysqli_fetch_assoc(mysqli_query($conn, $inactiveQuery))['total'];

$combinedQuery = "
    SELECT 
        student_id AS id,
        name,
        email,
        year,
        status,
        'Student' AS type
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
                <a href="../sign_in.html">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
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
                <input type="text" id="searchInput" placeholder="Search students...">

                <button class="filter-btn" id="filterToggle">
                    <i class="fa-solid fa-filter"></i>
                </button>
            </div>

            <div class="filter-panel" id="filterPanel" style="display:none;">
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
                        <label>Status</label>
                        <select id="filterStatus">
                            <option value="">All</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                </div>
            </div>

            <table class="student-table">
                <thead>
                    <tr>
                        <th>STUDENT ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Year</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="userTable">
                <?php if (!empty($combinedList)): ?>
                    <?php foreach ($combinedList as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['year']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">No records found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

</div>
</body>
    <script>
    const searchInput = document.getElementById("searchInput");
    const filterYear = document.getElementById("filterYear");
    const filterStatus = document.getElementById("filterStatus");
    const resetBtn = document.getElementById("resetFilter");
    const filterToggle = document.getElementById("filterToggle");
    const filterPanel = document.getElementById("filterPanel");

    function filterTable() {
        const searchValue = searchInput.value.toLowerCase();
        const yearValue = filterYear.value.toLowerCase();
        const statusValue = filterStatus.value.toLowerCase();

        const rows = document.querySelectorAll("#userTable tr");

        rows.forEach(row => {
            const cells = row.querySelectorAll("td");
            if (cells.length < 5) return;

            const id = cells[0].textContent.toLowerCase();
            const name = cells[1].textContent.toLowerCase();
            const email = cells[2].textContent.toLowerCase();
            const year = cells[3].textContent.toLowerCase();
            const status = cells[4].textContent.toLowerCase();

            const matchesSearch =
                searchValue === "" ||
                [id, name, email, year, status].some(value => value.includes(searchValue));

            const matchesYear = yearValue === "" || year === yearValue;
            const matchesStatus = statusValue === "" || status === statusValue;

            row.style.display =
                matchesSearch && matchesYear && matchesStatus
                    ? ""
                    : "none";
        });
    }

    if (searchInput) {
        searchInput.addEventListener("input", filterTable);
    }
    if (filterYear) {
        filterYear.addEventListener("change", filterTable);
    }
    if (filterStatus) {
        filterStatus.addEventListener("change", filterTable);
    }
    if (resetBtn && filterPanel) {
        resetBtn.addEventListener("click", () => {
            if (searchInput) searchInput.value = "";
            if (filterYear) filterYear.value = "";
            if (filterStatus) filterStatus.value = "";

            const rows = document.querySelectorAll("#userTable tr");
            rows.forEach(row => row.style.display = "");

            filterPanel.style.display = "none";
        });
    }

    if (filterToggle && filterPanel) {
        filterToggle.addEventListener("click", () => {
            const isVisible = filterPanel.style.display === "block";
            filterPanel.style.display = isVisible ? "none" : "block";
            filterToggle.innerHTML = isVisible
                ? '<i class="fa-solid fa-filter"></i>'
                : '<i class="fa-solid fa-arrows-rotate"></i>';
        });
    }
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
    </script>
</html>