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
    AND email LIKE '%@evsu.edu.ph'
    LIMIT 1
";

$studentResult = pg_query($conn, $studentQuery);

if (!$studentResult || pg_num_rows($studentResult) == 0) {
    session_destroy();
    echo "<script>
        alert('Access denied. Only EVSU email accounts are allowed.');
        window.location.href='../../index.php';
    </script>";
    exit;
}

$student = pg_fetch_assoc($studentResult);

$fullName = $student['first_name'] . ' ' . $student['last_name'];
$email = $student['email'];
$year = $student['year'];
$section = $student['section'];
$status = $student['status'];

$photo = !empty($student['photo'])
    ? $student['photo']
    : "https://ui-avatars.com/api/?name=" . urlencode($fullName);

$attendanceMonthQuery = "
    SELECT attendance.*,
           DATE(attendance.created_at) AS attendance_date
    FROM attendance
    INNER JOIN students
        ON students.student_id = attendance.student_id
    WHERE attendance.student_id = '$student_id'
    AND students.email LIKE '%@evsu.edu.ph'
    AND DATE(attendance.created_at) >= DATE_TRUNC('month', CURRENT_DATE)
    AND DATE(attendance.created_at) < (DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month')
    ORDER BY attendance.created_at DESC
";

$attendanceMonthResult = pg_query($conn, $attendanceMonthQuery);
$attendanceMonthRecords = [];

while ($row = pg_fetch_assoc($attendanceMonthResult)) {
    $attendanceMonthRecords[] = $row;
}

$page = $_GET['page'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard</title>

<link rel="stylesheet" href="../../css/student.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<div class="header">

    <div class="logo-title">
        <img src="/css/EVSU_Official_Logo.png">
        <h3>EVSU Student Portal</h3>
    </div>

    <div class="profile-wrapper">

        <div class="profile" id="profileBtn">
            <img src="<?php echo htmlspecialchars($photo); ?>">
        </div>

        <div class="profile-dropdown" id="profileDropdown">

            <div class="profile-header">
                <div class="profile-circle">
                    <img src="<?php echo htmlspecialchars($photo); ?>">
                </div>
                <h4><?php echo htmlspecialchars($fullName); ?></h4>
                <p><?php echo htmlspecialchars($email); ?></p>
            </div>

            <div class="account-menu">
                <a href="#" onclick="loadPage('upload_photo.php')">
                    <i class="fa fa-image"></i> Upload Photo
                </a>

                <a href="#" onclick="loadPage('account_settings.php')">
                    <i class="fa fa-user"></i> Account Settings
                </a>

                <a href="#" onclick="loadPage('change_password.php')">
                    <i class="fa fa-lock"></i> Change Password
                </a>

                <a href="../../index.php" class="logout-btn">
                    <i class="fa fa-sign-out"></i> Logout
                </a>
            </div>

        </div>

    </div>
</div>

<div class="container">

    <div class="sidebar">

        <a class="<?php echo ($page=='')?'active':'';?>" href="dashboard.php">
            <i class="fa-solid fa-gauge"></i>
            Dashboard
        </a>

        <a href="schedule.php">
            <i class="fa-solid fa-calendar-days"></i>
            Schedule
        </a>

        <a href="attendance.php">
            <i class="fa-solid fa-calendar-check"></i>
            Attendance
        </a>

        <a href="records.php">
            <i class="fa-solid fa-clock-rotate-left"></i>
            Records
        </a>

    </div>

    <div class="main">
        <div id="dynamicContent"></div>
        <div class="card dashboard-card">
            <h3 style="margin-bottom:18px;"></h3>

            <div class="quick-actions">

                <a href="attendance.php" class="qa-card">
                    <div class="icon">📌</div>
                    <div class="title">Attendance</div>
                    <div class="desc">View your attendance records</div>
                </a>

                <a href="schedule.php" class="qa-card">
                    <div class="icon">📅</div>
                    <div class="title">Schedule</div>
                    <div class="desc">Check your class schedule</div>
                </a>

                <a href="records.php" class="qa-card">
                    <div class="icon">📊</div>
                    <div class="title">Records</div>
                    <div class="desc">View academic history</div>
                </a>

            </div>
        </div>

        <div class="card">

            <h2>Attendance Calendar</h2>

            <div class="calendar-grid" id="calendarGrid" style="margin-top:18px;"></div>

        </div>

    </div>
</div>

<script>

const attendanceRecords = <?php echo json_encode($attendanceMonthRecords); ?>;
const recordsByDate = {};

attendanceRecords.forEach(r=>{
    const d = r.attendance_date;
    if(!recordsByDate[d]) recordsByDate[d]=[];
    recordsByDate[d].push(r);
});

let calendarDate = new Date();

function pad(n){ return n.toString().padStart(2,'0'); }

function renderCalendar(){

    const grid = document.getElementById("calendarGrid");
    grid.innerHTML="";

    const y = calendarDate.getFullYear();
    const m = calendarDate.getMonth();

    const first = new Date(y,m,1).getDay();
    const days = new Date(y,m+1,0).getDate();

    for(let i=0;i<first;i++) grid.innerHTML += "<div></div>";

    for(let d=1; d<=days; d++){

        const date = `${y}-${pad(m+1)}-${pad(d)}`;
        const rec = recordsByDate[date];

        let cls = "calendar-day";

        if(rec){
            cls += " " + rec[0].status.toLowerCase();
        }

        const today = new Date();
        if(d===today.getDate() && m===today.getMonth() && y===today.getFullYear()){
            cls += " today";
        }

        grid.innerHTML += `<div class="${cls}" onclick="show('${date}')">${d}</div>`;
    }
}

function show(date){
    const box = document.getElementById("calendarDetails");
    const rec = recordsByDate[date];

    if(!rec){
        box.innerHTML="No record";
        return;
    }

    box.innerHTML = `
        <b>${date}</b><br>
        Status: ${rec[0].status}<br>
        Time In: ${rec[0].time_in || '-'}<br>
        Time Out: ${rec[0].time_out || '-'}
    `;
}

document.getElementById("profileBtn").onclick=()=>{
    const d=document.getElementById("profileDropdown");
    d.style.display = d.style.display==="block"?"none":"block";
};

document.addEventListener("click",(e)=>{
    if(!document.getElementById("profileBtn").contains(e.target)){
        document.getElementById("profileDropdown").style.display="none";
    }
});

renderCalendar();

function loadPage(page){
    fetch(page)
        .then(res => res.text())
        .then(html => {

            document.querySelectorAll(".dashboard-card").forEach(c => {
                c.style.display = "none";
            });


            document.getElementById("dynamicContent").innerHTML = html;
        });
}

/* PROFILE TOGGLE */
document.getElementById("profileBtn").onclick = () => {
    const d = document.getElementById("profileDropdown");
    d.style.display = d.style.display === "block" ? "none" : "block";
};

document.addEventListener("click",(e)=>{
    if(!document.getElementById("profileBtn").contains(e.target)){
        document.getElementById("profileDropdown").style.display="none";
    }
});

</script>

</body>
</html>