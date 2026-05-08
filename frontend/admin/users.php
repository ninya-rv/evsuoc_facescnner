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
        $adminInitials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
    } else {
        $adminInitials = strtoupper(substr($nameParts[0], 0, 2));
    }
}
$adminEmail = $_SESSION['email'] ?? 'admin@evsu.edu.ph';

$totalUsersQuery = "SELECT COUNT(*) as total FROM users";
    $totalResult = mysqli_query($conn, $totalUsersQuery);
    $totalRow = mysqli_fetch_assoc($totalResult);
    $totalUsers = $totalRow['total'];

    $activeUsersQuery = "SELECT COUNT(*) as active FROM users WHERE status='active'";
    $activeResult = mysqli_query($conn, $activeUsersQuery);
    $activeRow = mysqli_fetch_assoc($activeResult);
    $activeUsers = $activeRow['active'];

    $inactiveUsersQuery = "SELECT COUNT(*) as inactive FROM users WHERE status='inactive'";
    $inactiveResult = mysqli_query($conn, $inactiveUsersQuery);
    $inactiveRow = mysqli_fetch_assoc($inactiveResult);
    $inactiveUsers = $inactiveRow['inactive'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-BSIT Dashboard</title>
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
                <a href="../sign_in.html">
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
        <h3>Users</h3>
        <div class="cards">
            <div class="card">
                <h4>Total Users</h4>
                <p><?php echo $totalUsers; ?></p>
            </div>
            <div class="card">
                <h4>Active</h4>
                <p><?php echo $activeUsers; ?></p>
            </div>
            <div class="card">
                <h4>Inactive</h4>
                <p><?php echo $inactiveUsers; ?></p>
            </div>
        </div>
        <div class="search-filter">
            <input type="text" id="searchInput" placeholder="Search instructor...">
        </div>  
        <div class="student-section">
            <form action="../../backend/add_user.php" method="POST" class="assignment-form" id="addUserForm">
                <div class="form-row">
                    <input type="text" name="name" id="name" placeholder="Full Name" required>
                    <input type="email" name="email" id="email" placeholder="Email" required>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <button type="submit" name="add_user" class="assign-btn">Add User</button>
                </div>
            </form>
            <table class="student-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>

                <tbody id="instructorTable">

                <?php
                $sql = "SELECT * FROM users WHERE role != 'admin' ORDER BY name ASC";
                $result = mysqli_query($conn, $sql);

                while($row = mysqli_fetch_assoc($result)){
                ?>

                    <tr data-id="<?php echo $row['id']; ?>">

                        <td><?php echo $row['name']; ?></td>

                        <td><?php echo $row['email']; ?></td>

                        <td><?php echo $row['password']; ?></td>

                        <td>
                            <?php if($row['status'] == "active"){ ?>
                                <span style="color:#800000;font-weight:bold;">Active</span>
                            <?php } else { ?>
                                <span style="color:#800000;opacity:0.6;font-weight:bold;">Inactive</span>
                            <?php } ?>
                        </td>

                        <td>

                            <?php if($row['status'] == "active"){ ?>

                                <a href="../../backend/update_status.php?id=<?php echo $row['id']; ?>&status=inactive" title="Set Inactive">
                                    <i class="fa-solid fa-user-slash action-icon"></i>
                                </a>

                            <?php } else { ?>

                                <a href="../../backend/update_status.php?id=<?php echo $row['id']; ?>&status=active" title="Set Active">
                                    <i class="fa-solid fa-user-check action-icon"></i>
                                </a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
    <script>
        const searchInput = document.getElementById("searchInput");
        const rows = document.querySelectorAll("#instructorTable tr");

        searchInput.addEventListener("input", function() {

            const value = searchInput.value.toLowerCase();

            rows.forEach(row => {

                const name = row.cells[0].textContent.toLowerCase();
                const email = row.cells[1].textContent.toLowerCase();

                if (name.includes(value) || email.includes(value)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
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