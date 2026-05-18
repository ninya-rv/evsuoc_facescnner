<?php
include __DIR__ . '/backend/db.php';

function printRows($title, $result) {
    echo "$title\n";
    if (!$result) {
        echo "ERROR: " . pg_last_error() . "\n";
        return;
    }
    while ($row = pg_fetch_assoc($result)) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

$assignQ = pg_query($conn, "SELECT instructor_name, year_level, section FROM instructor_assignment WHERE instructor_name ILIKE '%jamirah%' ORDER BY year_level, section");
printRows('ASSIGNMENTS', $assignQ);

$yearsQ = pg_query($conn, "SELECT DISTINCT year FROM students ORDER BY year");
printRows('STUDENT_YEARS', $yearsQ);

$studentsQ = pg_query($conn, "SELECT student_id, name, email, year, section, status FROM students WHERE year ILIKE '%3%' OR year ILIKE '%third%' ORDER BY year, section, name LIMIT 50");
printRows('STUDENTS_3RD', $studentsQ);

$q3 = pg_query($conn, "SELECT student_id, name, year, section, status FROM students WHERE LOWER(TRIM(year)) = LOWER(TRIM('3rd Year')) AND LOWER(TRIM(section)) = LOWER(TRIM('B')) ORDER BY name");
printRows('STUDENTS_3RD_YEAR_B', $q3);

$q4 = pg_query($conn, "SELECT student_id, name, year, section, status FROM students WHERE LOWER(TRIM(year)) = LOWER(TRIM('4th Year')) AND LOWER(TRIM(section)) = LOWER(TRIM('A')) ORDER BY name");
printRows('STUDENTS_4TH_YEAR_A', $q4);
