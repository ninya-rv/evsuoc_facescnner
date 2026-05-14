<?php
session_start();
include "../../backend/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../sign_in.html");
    exit;
}

if ($_SESSION['role'] !== 'instructor') {
    header("Location: ../admin/dashboard.php");
    exit;
}

date_default_timezone_set('Asia/Manila');

$instructor_id = $_SESSION['user_id'];

$manilaNow = new DateTime('now', new DateTimeZone('Asia/Manila'));
$currentDate = $manilaNow->format('Y-m-d');

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

    $instructorName = $instructorData['name'];
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

    $instructorName = "Instructor";
    $instructorEmail = "No email found";
    $initials = "IN";
}

$instructor_name = pg_escape_string($conn, $instructorName);

$classQuery = "
    SELECT *
    FROM instructor_assignment
    WHERE instructor_name = '$instructor_name'
    ORDER BY start_time ASC
";

$classResult = pg_query($conn, $classQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EVSU-BSIT Dashboard - Face Scanner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/scanner.css">

    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script defer src="/api/instructorScanner.js"></script>

</head>
<body>
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
                <a href="../../index.php">
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
                <a href="/frontend/instructor/dashboard.php">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/frontend/instructor/attendance.php">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li>
                <a href="/frontend/instructor/scanner.php" class="active">
                    <i class="fa-solid fa-camera"></i>
                    <span>Face Scanner</span>
                </a>
            </li>
        </ul>
    </aside>
    <main class="main">
        <h3>Face Scanner</h3>

        <div class="student-section">
            <h4>Face Scanner</h4>
            <div class="step-indicator">
                <div class="step active" id="step1Indicator">
                    <div class="step-number">1</div>
                    <div class="step-label">Select mode</div>
                </div>
                <div class="step" id="step2Indicator">
                    <div class="step-number">2</div>
                    <div class="step-label">Choose class</div>
                </div>
            </div>

            <div class="time-mode-row">
                <button id="timeInBtn" class="scanner-mode-btn" type="button">Time In</button>
                <button id="timeOutBtn" class="scanner-mode-btn" type="button">Time Out</button>
            </div>

            <select id="classSelect" required disabled>
                <option value="">Select your class assignment</option>

                <?php if ($classResult && pg_num_rows($classResult) > 0): ?>
                    <?php while ($class = pg_fetch_assoc($classResult)): ?>
                        <?php 
                    $classEndTimestamp = strtotime($currentDate . ' ' . $class['end_time']);
                    $isEnded = $classEndTimestamp < $manilaNow->getTimestamp();
                ?>
                <option 
                            value="<?php echo $class['id']; ?>"
                            data-year="<?php echo htmlspecialchars($class['year_level']); ?>"
                            data-section="<?php echo htmlspecialchars($class['section']); ?>"
                            data-subject="<?php echo htmlspecialchars($class['subject']); ?>"
                            data-start="<?php echo htmlspecialchars($class['start_time']); ?>"
                            data-end="<?php echo htmlspecialchars($class['end_time']); ?>"
                            <?php echo $isEnded ? 'disabled' : ''; ?>
                        >
                            <?php 
                            echo htmlspecialchars($class['subject']) . " | " . 
                                 htmlspecialchars($class['year_level']) . " | Section " . 
                                 htmlspecialchars($class['section']) . " | " . 
                                 date("g:i A", strtotime($class['start_time'])) . " - " . 
                                 date("g:i A", strtotime($class['end_time']));
                            if ($isEnded) {
                                echo ' (Ended)';
                            }
                            ?>
                        </option>
                    <?php endwhile; ?>
                <?php else: ?>
                    <option value="">No class assignments found</option>
                <?php endif; ?>
            </select>

            <div id="scannerArea">
                <video id="video" autoplay muted></video>
                <div id="status">Scanner ready...</div>
                <div id="warning" class="warning-text"></div>
            </div>
        </div>
    </main>
</div>
</body>
</html>