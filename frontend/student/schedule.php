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
$year = $student['year'];
$section = $student['section'];

$photo = !empty($student['photo'])
    ? $student['photo']
    : "https://ui-avatars.com/api/?name=" . urlencode($fullName);

$scheduleQuery = "
    SELECT *
    FROM instructor_assignment
    WHERE year_level = '$year'
    AND section = '$section'
    ORDER BY
        CASE
            WHEN day = 'Monday' THEN 1
            WHEN day = 'Tuesday' THEN 2
            WHEN day = 'Wednesday' THEN 3
            WHEN day = 'Thursday' THEN 4
            WHEN day = 'Friday' THEN 5
            WHEN day = 'Saturday' THEN 6
            WHEN day = 'Sunday' THEN 7
        END,
        start_time ASC
";

$scheduleResult = pg_query($conn, $scheduleQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Schedule</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

/* BASE */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial,sans-serif;
}

body{ background:#f4f4f4; }

/* HEADER */
.header{
    width:100%;
    height:70px;
    background:#800000;
    color:#fff;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:0 20px;
}

.logo-title{
    display:flex;
    align-items:center;
    gap:10px;
}

.logo-title img{
    width:45px;
    height:45px;
}

/* SIDEBAR */
.container{
    display:flex;
    min-height:calc(100vh - 70px);
}

.sidebar{
    width:250px;
    background:#222;
    padding-top:20px;
}

.sidebar a{
    display:flex;
    align-items:center;
    gap:10px;
    color:#fff;
    text-decoration:none;
    padding:15px 20px;
}

.sidebar a:hover,
.sidebar a.active{
    background:#800000;
}

/* MAIN */
.main{
    flex:1;
    padding:25px;
}

.page-card{
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.08);
}

/* SEARCH + FILTER (RIGHT SIDE FIX) */
.search-filter{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-bottom:15px;
    flex-wrap:wrap;
}

.search-filter input{
    width:220px;
    padding:10px;
    border:1px solid #ccc;
    border-radius:8px;
    outline:none;
}

.filter-btn{
    padding:10px 14px;
    border:none;
    background:#800000;
    color:#fff;
    border-radius:8px;
    cursor:pointer;
}

/* FILTER PANEL */
.filter-panel{
    display:none;
    margin-bottom:15px;
    background:#fff;
    padding:15px;
    border-radius:10px;
    box-shadow:0 2px 8px rgba(0,0,0,0.08);
}

.filter-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:15px;
}

.filter-group select{
    width:100%;
    padding:10px;
    border-radius:8px;
    border:1px solid #ccc;
}

/* TABLE */
.student-table{
    width:100%;
    border-collapse:collapse;
}

.student-table th,
.student-table td{
    border:1px solid #ddd;
    padding:12px;
}

.student-table th{
    background:#800000;
    color:#fff;
}

/* CARD VIEW */
.card-grid{
    display:none;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:15px;
    margin-top:15px;
}

.schedule-card{
    background:#fff;
    padding:15px;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.08);
}

/* PROFILE */
.profile-wrapper{ position:relative; }

.profile{
    width:45px;
    height:45px;
    border-radius:50%;
    overflow:hidden;
    cursor:pointer;
}

.profile img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.profile-dropdown{
    position:absolute;
    top:60px;
    right:0;
    width:300px;
    background:#fff;
    border-radius:12px;
    display:none;
    box-shadow:0 5px 15px rgba(0,0,0,0.2);
}

.profile-header{
    background:#800000;
    color:#fff;
    text-align:center;
    padding:20px;
}

.account-menu a{
    display:flex;
    gap:10px;
    padding:14px;
    text-decoration:none;
    color:#333;
}

.logout-btn{ color:red !important; }

</style>

</head>

<body>

<div class="header">

    <div class="logo-title">
        <img src="/css/EVSU_Official_Logo.png">
        <h2>EVSU Student Portal</h2>
    </div>

    <div class="profile-wrapper">

        <div class="profile" id="profileBtn">
            <img src="<?php echo htmlspecialchars($photo); ?>">
        </div>

        <div class="profile-dropdown" id="profileDropdown">

            <div class="profile-header">
                <h4><?php echo htmlspecialchars($fullName); ?></h4>
                <p><?php echo htmlspecialchars($email); ?></p>
            </div>

        </div>

    </div>

</div>

<div class="container">

<div class="sidebar">

    <a href="dashboard.php"><i class="fa fa-gauge"></i> Dashboard</a>
    <a href="schedule.php" class="active"><i class="fa fa-calendar"></i> Schedule</a>
    <a href="attendance.php"><i class="fa fa-check"></i> Attendance</a>
    <a href="records.php"><i class="fa fa-clock"></i> Records</a>

</div>

<div class="main">

<div class="page-card">

<h3>Class Schedule</h3>

<!-- SEARCH + FILTER RIGHT SIDE -->
<div class="search-filter">

    <input type="text" id="searchInput" placeholder="Search instructor or subject...">

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
            <label>Instructor</label>
            <select id="filterInstructor">
                <option value="">All</option>
                <?php
                $instQuery = "SELECT DISTINCT instructor_name FROM instructor_assignment ORDER BY instructor_name ASC";
                $instResult = pg_query($conn, $instQuery);

                while ($inst = pg_fetch_assoc($instResult)) {
                    echo '<option value="'.strtolower($inst['instructor_name']).'">'.$inst['instructor_name'].'</option>';
                }
                ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Day</label>
            <select id="filterDay">
                <option value="">All</option>
                <option>Monday</option>
                <option>Tuesday</option>
                <option>Wednesday</option>
                <option>Thursday</option>
                <option>Friday</option>
                <option>Saturday</option>
            </select>
        </div>

    </div>

</div>

<!-- TABLE VIEW -->
<table class="student-table" id="tableView">

<thead>
<tr>
    <th>Subject</th>
    <th>Instructor</th>
    <th>Day</th>
    <th>Start</th>
    <th>End</th>
</tr>
</thead>

<tbody>

<?php while($row = pg_fetch_assoc($scheduleResult)) { ?>

<tr class="schedule-row"
    data-subject="<?php echo strtolower($row['subject']); ?>"
    data-instructor="<?php echo strtolower($row['instructor_name']); ?>"
    data-day="<?php echo $row['day']; ?>">

    <td><?php echo $row['subject']; ?></td>
    <td><?php echo $row['instructor_name']; ?></td>
    <td><?php echo $row['day']; ?></td>
    <td><?php echo date('g:i A', strtotime($row['start_time'])); ?></td>
    <td><?php echo date('g:i A', strtotime($row['end_time'])); ?></td>

</tr>

<?php } ?>

</tbody>

</table>

<!-- CARD VIEW -->
<div class="card-grid" id="cardView">

<?php
$scheduleResult2 = pg_query($conn, $scheduleQuery);
while($row = pg_fetch_assoc($scheduleResult2)) {
?>

<div class="schedule-card"
    data-subject="<?php echo strtolower($row['subject']); ?>"
    data-instructor="<?php echo strtolower($row['instructor_name']); ?>"
    data-day="<?php echo $row['day']; ?>">

    <h4><?php echo $row['subject']; ?></h4>
    <p><?php echo $row['instructor_name']; ?></p>
    <p><?php echo $row['day']; ?></p>

</div>

<?php } ?>

</div>

</div>

</div>

</div>

<script>

const search = document.getElementById("searchInput");
const dayFilter = document.getElementById("filterDay");
const instructorFilter = document.getElementById("filterInstructor");

const table = document.getElementById("tableView");
const card = document.getElementById("cardView");

const filterToggleBtn = document.getElementById("filterToggle");
const filterPanel = document.getElementById("filterPanel");
const toggleViewBtn = document.getElementById("toggleView");

function filterData(){

    let value = (search.value || "").toLowerCase();
    let day = dayFilter.value;
    let instructor = instructorFilter.value;

    document.querySelectorAll(".schedule-row, .schedule-card").forEach(el => {

        let subject = (el.dataset.subject || "");
        let instr = (el.dataset.instructor || "");
        let elDay = el.dataset.day || "";

        let match =
            (subject.includes(value) || instr.includes(value)) &&
            (!day || elDay === day) &&
            (!instructor || instr === instructor);

        el.style.display = match ? "" : "none";
    });
}

search.addEventListener("input", filterData);
dayFilter.addEventListener("change", filterData);
instructorFilter.addEventListener("change", filterData);

filterToggleBtn.addEventListener("click", () => {
    filterPanel.style.display =
        filterPanel.style.display === "block" ? "none" : "block";
});

toggleViewBtn.addEventListener("click", () => {

    if (!table || !card) return;

    if (table.style.display === "none") {
        table.style.display = "";
        card.style.display = "none";
    } else {
        table.style.display = "none";
        card.style.display = "grid";
    }

});

</script>

</body>
</html>