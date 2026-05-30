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

if (isset($_POST['upload_photo'])) {

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {

        $allowed = ['jpg', 'jpeg', 'png'];

        $fileName = $_FILES['photo']['name'];
        $fileTmp  = $_FILES['photo']['tmp_name'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {

            $newFileName =
                time() . "_" .
                basename($fileName);

            $uploadPath =
                "../../uploads/" .
                $newFileName;

            if (move_uploaded_file($fileTmp, $uploadPath)) {

                $photoPath =
                    "/uploads/" .
                    $newFileName;

                $updateQuery = "
                    UPDATE students
                    SET photo = '$photoPath'
                    WHERE student_id = '$student_id'
                ";

                pg_query($conn, $updateQuery);

                $message = "Photo uploaded successfully.";

            } else {

                $message = "Failed to upload photo.";
            }

        } else {

            $message = "Only JPG, JPEG, PNG allowed.";
        }
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

$photo = !empty($student['photo'])
    ? $student['photo']
    : "https://ui-avatars.com/api/?name=" .
      urlencode($student['first_name'] . ' ' . $student['last_name']);
?>

<div class="content-card">

    <h2>Upload Photo</h2>

    <?php if($message != ""): ?>

        <div class="success-message">
            <?php echo $message; ?>
        </div>

    <?php endif; ?>

    <div style="text-align:center;">

        <img
            src="<?php echo htmlspecialchars($photo); ?>"
            class="profile-image"
        >

    </div>

    <form
        method="POST"
        enctype="multipart/form-data"
    >

        <div class="form-group">

            <input
                type="file"
                name="photo"
                required
            >

        </div>

        <button
            type="submit"
            name="upload_photo"
            class="save-btn"
        >
            Upload Photo
        </button>

    </form>

</div>