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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EVSU-BSIT Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../../css/style.css">
</head>

<body>

<div class="header">
    <div class="logo-title">
        <img src="../../css/EVSU_Official_Logo.png" alt="EVSU logo">
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
            <li><a href="../../frontend/admin/dashboard.php" class="active"><i class="fa-solid fa-gauge"></i><span>Dashboard</span></a></li>
            <li><a href="attendance.php"><i class="fa-solid fa-calendar-check"></i><span>Attendance</span></a></li>
            <li><a href="users.php"><i class="fa-solid fa-users"></i><span>Users</span></a></li>
            <li><a href="instructor_assignment.php"><i class="fa-solid fa-user-shield"></i><span>Instructor Assignment</span></a></li>
        </ul>
    </div>

    <div class="main">

        <h3>Instructor Year and Section Assignments</h3>

        <div class="search-filter">
            <input type="text" id="searchInput" placeholder="Search instructor...">
            <button class="filter-btn" id="filterToggle">
                <i class="fa-solid fa-filter"></i>
            </button>       
	 </div>
        <div class="filter-panel" id="filterPanel" style="display:none;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Year</label>
                    <select id="filterYear">
                        <option value="">All</option>
                        <?php
                        $yearQuery = "SELECT DISTINCT year_level FROM instructor_assignment ORDER BY year_level ASC";
                        $yearResult = mysqli_query($conn, $yearQuery);
                        while($yearRow = mysqli_fetch_assoc($yearResult)){
                            echo '<option value="'.$yearRow['year_level'].'">'.$yearRow['year_level'].'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Section</label>
                    <select id="filterSection">
                        <option value="">All</option>
                        <?php
                        $sectionQuery = "SELECT DISTINCT section FROM instructor_assignment ORDER BY section ASC";
                        $sectionResult = mysqli_query($conn, $sectionQuery);
                        while($sectionRow = mysqli_fetch_assoc($sectionResult)){
                            echo '<option value="'.$sectionRow['section'].'">'.$sectionRow['section'].'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Subject</label>
                    <select id="filterSubject">
                        <option value="">All</option>
                        <?php
                        $subjectQuery = "SELECT DISTINCT subject FROM instructor_assignment ORDER BY subject ASC";
                        $subjectResult = mysqli_query($conn, $subjectQuery);
                        while($subjectRow = mysqli_fetch_assoc($subjectResult)){
                            echo '<option value="'.$subjectRow['subject'].'">'.$subjectRow['subject'].'</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="student-section">
            <form action="../../backend/assign_instructor.php" method="POST" class="assignment-form" id="assignForm">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-row">
                    <select name="instructor_name" id="instructor_name" required>
                        <option value="">Select Instructor</option>
                        <?php
                        $instructorQuery = "SELECT name FROM users WHERE role = 'instructor' AND status = 'active' ORDER BY name ASC";
                        $instructorResult = mysqli_query($conn, $instructorQuery);
                        while($instructor = mysqli_fetch_assoc($instructorResult)){
                            echo '<option value="'.$instructor['name'].'">'.$instructor['name'].'</option>';
                        }
                        ?>
                    </select>
                    <select name="year_level" id="year_level" required>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                    <select name="section" id="section" required>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                    <div class="subject-search-container">
                        <input type="text" id="subjectInput" placeholder="Search Subject Code or Name" autocomplete="off">
                        <input type="hidden" name="subject" id="subject">
                        <div class="subject-dropdown" id="subjectDropdown"></div>
                    </div>
                    <select name="room" id="room" required>
                        <option value="">Select Room</option>
                        <option value="Room 1">Room 1</option>
                        <option value="Room 2">Room 2</option>
                        <option value="Room 3">Room 3</option>
                        <option value="Room 4">Room 4</option>
                        <option value="Room 5">Room 5</option>
                        <option value="Room 6">Room 6</option>
                    </select>

                    <div class="time-inputs">
                        <label for="start_time">Start Time</label>
                        <input type="time" name="start_time" id="start_time" required>
                    </div>

                    <div class="time-inputs">
                        <label for="end_time">End Time</label>
                        <input type="time" name="end_time" id="end_time" required>
                    </div>

                    <button type="submit" name="assign" class="assign-btn">Assign</button>
                </div>
            </form>
            <table class="student-table" data-page="assignment">
                <thead>
                    <tr>
                        <th>Instructor Name</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Subject</th>
                        <th>Room</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="instructorTable">
                    <?php
                    $sql = "SELECT * FROM instructor_assignment";
                    $result = mysqli_query($conn, $sql);
                    while($row = mysqli_fetch_assoc($result)){
                    ?>
                    <tr data-id="<?php echo $row['id']; ?>">
                        <td><?php echo $row['instructor_name']; ?></td>
                        <td><?php echo $row['year_level']; ?></td>
                        <td><?php echo $row['section']; ?></td>
                        <td><?php echo $row['subject']; ?></td>
                        <td><?php echo $row['room']; ?></td>
                        <td>
                            <?php 
                            echo date("g:i A", strtotime($row['start_time'])) . " - " . 
                                 date("g:i A", strtotime($row['end_time']));
                            ?>
                        </td>
                        <td>
                            <i class="fa-solid fa-pen-to-square edit-btn" style="color:#8B0000; cursor:pointer; margin-right:10px;"></i>
                            <i class="fa-solid fa-trash delete-btn" style="color:red; cursor:pointer;"></i>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../../backend/script.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {

    const subjects = [
    {code:"IT1131A",name:"Introduction to Computing"},
    {code:"IT1341",name:"Computer Programming"},
    {code:"IT1231",name:"Human Computer Interaction"},
    {code:"IT1631",name:"Computer Programming 2"},
    {code:"IT1431",name:"Discrete Mathematics"},
    {code:"IT2132",name:"Data Structures and Algorithms"},
    {code:"IT2532",name:"Platform Technologies"},
    {code:"IT2332",name:"Object Oriented Programming"},
    {code:"IT2932",name:"Statistics and Probability"},
    {code:"IT2732C",name:"Web Systems Technologies"},
    {code:"IT3333",name:"Systems Analysis and Design"},
    {code:"IT3733",name:"Web Systems and Technology 2"},
    {code:"IT3933",name:"Social and Professional Issues"},
    {code:"IT3133",name:"Advanced Database Systems"},
    {code:"IT3233",name:"Software Engineering"},
    {code:"IT3633",name:"Information Assurance and Security"},
    {code:"IT3433",name:"Multimedia Systems"},
    {code:"IT3833",name:"Integrative Programming"}
    ];

    const input = document.getElementById("subjectInput");
    const dropdown = document.getElementById("subjectDropdown");
    const hidden = document.getElementById("subject");

    function showSubjects(filter=""){
        dropdown.innerHTML="";
        const f = filter.toLowerCase();

        const filtered = subjects.filter(sub =>
            sub.code.toLowerCase().includes(f) ||
            sub.name.toLowerCase().includes(f)
        );

        if(filtered.length === 0){
            dropdown.style.display="none";
            return;
        }

        filtered.forEach(sub=>{
            const item=document.createElement("div");
            const text=`${sub.code} - ${sub.name}`;
            item.textContent=text;

            item.onclick=()=>{
                input.value=text;
                hidden.value=text;
                dropdown.style.display="none";
            };

            dropdown.appendChild(item);
        });

        dropdown.style.display="block";
    }

    input.addEventListener("input",()=>{
        showSubjects(input.value);
    });

    document.addEventListener("click",(e)=>{
        if(!e.target.closest(".subject-search-container")){
            dropdown.style.display="none";
        }
    });

    document.querySelectorAll(".edit-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const row = btn.closest("tr");
            const subjectValue = row.cells[3].textContent;

            loadSubjects();
            subjectSelect.value = subjectValue;
            document.getElementById("edit_id").value = row.getAttribute("data-id");
            document.getElementById("instructor_name").value = row.cells[0].textContent;
            document.getElementById("year_level").value = row.cells[1].textContent;
            document.getElementById("section").value = row.cells[2].textContent;
            document.getElementById("room").value = row.cells[4].textContent;

            const timeRange = row.cells[5].textContent.split(" - ");
            if(timeRange.length === 2){
                document.getElementById("start_time").value = convertTo24Hour(timeRange[0]);
                document.getElementById("end_time").value = convertTo24Hour(timeRange[1]);
            }

            document.querySelector(".assignment-form").scrollIntoView({behavior: "smooth"});
        });
    });

    function convertTo24Hour(timeStr){
        const [time, modifier] = timeStr.split(" ");
        let [hours, minutes] = time.split(":");
        hours = parseInt(hours);
        if(modifier === "PM" && hours < 12) hours += 12;
        if(modifier === "AM" && hours === 12) hours = 0;
        return `${hours.toString().padStart(2,"0")}:${minutes}`;
    }
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
</body>
</html>