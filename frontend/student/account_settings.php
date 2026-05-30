<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../backend/db.php";

if (!isset($_SESSION['student_id'])) {
    exit;
}

$student_id = $_SESSION['student_id'];

$message = "";

if (isset($_POST['save_changes'])) {

    $first_name = pg_escape_string($conn, $_POST['first_name']);
    $last_name  = pg_escape_string($conn, $_POST['last_name']);
    $email      = pg_escape_string($conn, $_POST['email']);
    $year       = pg_escape_string($conn, $_POST['year']);
    $section    = pg_escape_string($conn, $_POST['section']);

    $updateQuery = "
        UPDATE students
        SET
            first_name = '$first_name',
            last_name = '$last_name',
            email = '$email',
            year = '$year',
            section = '$section'
        WHERE student_id = '$student_id'
    ";

    if (pg_query($conn, $updateQuery)) {
        $message = "Account updated successfully.";
    } else {
        $message = "Failed to update account.";
    }
}

$studentQuery = "
    SELECT *
    FROM students
    WHERE student_id = '$student_id'
";

$student = pg_fetch_assoc(
    pg_query($conn, $studentQuery)
);
?>

<div class="content-card">

    <h2>Account Settings</h2>

    <?php if($message != ""): ?>

        <div class="success-message">
            <?php echo $message; ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-group">

            <label>First Name</label>

            <input
                type="text"
                name="first_name"
                value="<?php echo htmlspecialchars($student['first_name']); ?>"
                required
            >

        </div>

        <div class="form-group">

            <label>Last Name</label>

            <input
                type="text"
                name="last_name"
                value="<?php echo htmlspecialchars($student['last_name']); ?>"
                required
            >

        </div>

        <div class="form-group">

            <label>Email</label>

            <input
                type="email"
                name="email"
                value="<?php echo htmlspecialchars($student['email']); ?>"
                required
            >

        </div>

        <div class="form-group">

            <label>Year</label>

            <select name="year" required>

                <option value="1st Year" <?php if($student['year']=="1st Year") echo "selected"; ?>>
                    1st Year
                </option>

                <option value="2nd Year" <?php if($student['year']=="2nd Year") echo "selected"; ?>>
                    2nd Year
                </option>

                <option value="3rd Year" <?php if($student['year']=="3rd Year") echo "selected"; ?>>
                    3rd Year
                </option>

                <option value="4th Year" <?php if($student['year']=="4th Year") echo "selected"; ?>>
                    4th Year
                </option>

            </select>

        </div>

        <div class="form-group">

            <label>Section</label>

            <select name="section" required>

                <option value="A" <?php if($student['section']=="A") echo "selected"; ?>>A</option>
                <option value="B" <?php if($student['section']=="B") echo "selected"; ?>>B</option>
                <option value="C" <?php if($student['section']=="C") echo "selected"; ?>>C</option>
                <option value="D" <?php if($student['section']=="D") echo "selected"; ?>>D</option>

            </select>

        </div>

        <button
            type="submit"
            name="save_changes"
            class="save-btn"
        >
            Save Changes
        </button>

    </form>

</div>