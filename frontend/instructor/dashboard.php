<?php
session_start();
include "../../backend/db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../sign_in.html");
    exit;
}
if($_SESSION['role'] !== 'instructor'){
    header("Location: ../sign_in.html");
    exit;
}

$instructor_id = $_SESSION['user_id'];

$instructorQuery = "SELECT name, email FROM users WHERE id = '$instructor_id' AND role = 'instructor' LIMIT 1";
$instructorResult = mysqli_query($conn, $instructorQuery);

if ($instructorResult && mysqli_num_rows($instructorResult) > 0) {
    $instructorData = mysqli_fetch_assoc($instructorResult);
    $instructorName = $instructorData['name'];
    $instructorEmail = $instructorData['email'];

    $nameParts = explode(" ", trim($instructorName));
    $initials = "";
    foreach ($nameParts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
        if (strlen($initials) >= 2) break;
    }
} else {
    $instructorName = "Instructor";
    $instructorEmail = "No email found";
    $initials = "IN";
}
$assignedStudents = [];
$totalStudents = 0;
$activeStudents = 0;
$inactiveStudents = 0;

$assignmentQuery = "SELECT DISTINCT year_level, section 
                    FROM instructor_assignment 
                    WHERE instructor_name = '".mysqli_real_escape_string($conn, $instructorName)."'";

$assignmentResult = mysqli_query($conn, $assignmentQuery);

$conditions = [];

if ($assignmentResult && mysqli_num_rows($assignmentResult) > 0) {
    while ($assignment = mysqli_fetch_assoc($assignmentResult)) {
        $year = mysqli_real_escape_string($conn, $assignment['year_level']);
        $section = mysqli_real_escape_string($conn, $assignment['section']);

        $conditions[] = "(year = '$year' AND section = '$section')";
    }
}

if (!empty($conditions)) {
    $studentQuery = "SELECT * FROM students 
                     WHERE " . implode(" OR ", $conditions) . "
                     ORDER BY year ASC, section ASC, name ASC";

    $studentResult = mysqli_query($conn, $studentQuery);

    if ($studentResult) {
        while ($student = mysqli_fetch_assoc($studentResult)) {
            $assignedStudents[] = $student;
            $totalStudents++;
            if (isset($student['status'])) {
                if (strtolower($student['status']) === 'active') {
                    $activeStudents++;
                } else {
                    $inactiveStudents++;
                }
            } else {
                $activeStudents++;
            }
        }
    }
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

<!-- Header -->
<header class="header">
    <div class="logo-title">
        <img src="/css/EVSU_Official_Logo.png" alt="EVSU Logo">
        <h2>EVSU-BSIT</h2>
    </div>
    <div class="profile-wrapper">
        <div class="profile" id="profileBtn"><?php echo htmlspecialchars($initials); ?></div>

        <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-header">
                <div class="profile-circle"><?php echo htmlspecialchars($initials); ?></div>
                <br>
                <h4><?php echo htmlspecialchars($instructorName); ?></h4>
                <p><?php echo htmlspecialchars($instructorEmail); ?></p>
                <span class="badge">INSTRUCTOR</span>
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
                <a href="/frontend/instructor/dashboard.php" class="active">
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
                <input type="text" placeholder="Search students...">

                <button class="filter-btn" id="filterToggle">
                    <i class="fa-solid fa-filter"></i>
                </button>
            </div>
            <div class="filter-panel" id="filterPanel">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Year Level</label>
                        <select id="filterYear">
                            <option value="">All</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
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
                    <div class="filter-group">
                        <label>Date</label>
                        <input type="date" id="filterDate">
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
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($assignedStudents)): ?>
                    <?php foreach ($assignedStudents as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['year']); ?></td>
                            <td><?php echo htmlspecialchars($student['section']); ?></td>
                            <td><?php echo isset($student['status']) ? htmlspecialchars($student['status']) : 'Active'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">No assigned students found for your year and section.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

</div>
</body>
    <script src="/backend/script.js"></script>
     <script>
        const profileBtn = document.getElementById("profileBtn");
        const dropdown = document.getElementById("profileDropdown");

        profileBtn.addEventListener("click", () => {
            dropdown.style.display =
                dropdown.style.display === "block" ? "none" : "block";
        });

        document.addEventListener("click", function(e){
            if(!profileBtn.contains(e.target) && !dropdown.contains(e.target)){
                dropdown.style.display = "none";
            }
        });
    </script>
</html>