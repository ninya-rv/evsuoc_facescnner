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

$instructor_name = $instructorName;


$classQuery = "SELECT * FROM instructor_assignment 
               WHERE instructor_name = '$instructor_name'
               ORDER BY start_time ASC";

$classResult = mysqli_query($conn, $classQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EVSU-BSIT Dashboard - Face Scanner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">

    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script defer src="/api/instructorScanner.js"></script>

    <style>
        html,
        body {
            height: 100%;
        }

        body {
            background-color: #f3f3f3;
            overflow: hidden;
        }

        #video {
            width: 720px;
            max-width: 100%;
            height: 520px;
            border: 3px solid #8B0000;
            border-radius: 16px;
            background: #000;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
        }

        #status,
        #warning {
            width: 100%;
            text-align: center;
            margin-top: 12px;
            font-weight: bold;
        }

        #status {
            color: #8B0000;
        }

        .warning-text {
            display: none;
            color: #B00000;
            margin-top: 10px;
            padding: 12px 14px;
            border: 1px solid rgba(176, 0, 0, 0.28);
            border-radius: 12px;
            background: rgba(255, 220, 220, 0.88);
        }

        #classSelect {
            padding: 10px;
            width: 100%;
            max-width: 520px;
            margin-bottom: 15px;
            border-radius: 10px;
            border: 1px solid #ccc;
        }

        #scannerArea {
            display: none;
            margin-top: 24px;
            width: 100%;
            max-width: 940px;
            margin-left: auto;
            margin-right: auto;
            flex-direction: column;
            align-items: center;
        }

        .student-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 110px;
            width: 100%;
            max-width: 860px;
            margin: 0 auto;
            padding-bottom: 40px;
            z-index: 2;
        }

        .step-indicator {
            margin-bottom: 16px;
            display: flex;
            gap: 14px;
            justify-content: center;
            width: 100%;
            max-width: 420px;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            width: 98px;
            padding: 8px 0;
            border-radius: 14px;
        }

        .step-number {
            width: 34px;
            height: 34px;
            border-radius: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
            background: #ddd;
            color: #333;
        }

        .step.active .step-number {
            background: #8B0000;
            color: #fff;
        }

        .step-label {
            font-size: 12px;
            font-weight: 600;
            color: #333;
            text-align: center;
            line-height: 1.2;
        }

        .time-mode-row {
            display: flex;
            gap: 10px;
            justify-content: center;
            width: 100%;
            max-width: 520px;
        }

        .scanner-mode-btn {
            flex: 1;
            min-width: 118px;
            padding: 10px 14px;
            border: none;
            border-radius: 12px;
            background: #8B0000;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .scanner-mode-btn:hover {
            transform: translateY(-1px);
            background: #a30000;
        }

        .scanner-mode-btn.active {
            background: #4d0003;
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

                <?php if ($classResult && mysqli_num_rows($classResult) > 0): ?>
                    <?php while ($class = mysqli_fetch_assoc($classResult)): ?>
                        <option 
                            value="<?php echo $class['id']; ?>"
                            data-year="<?php echo htmlspecialchars($class['year_level']); ?>"
                            data-section="<?php echo htmlspecialchars($class['section']); ?>"
                            data-subject="<?php echo htmlspecialchars($class['subject']); ?>"
                            data-start="<?php echo htmlspecialchars($class['start_time']); ?>"
                            data-end="<?php echo htmlspecialchars($class['end_time']); ?>"
                        >
                            <?php 
                            echo htmlspecialchars($class['subject']) . " | " . 
                                 htmlspecialchars($class['year_level']) . " | Section " . 
                                 htmlspecialchars($class['section']) . " | " . 
                                 date("g:i A", strtotime($class['start_time'])) . " - " . 
                                 date("g:i A", strtotime($class['end_time']));
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