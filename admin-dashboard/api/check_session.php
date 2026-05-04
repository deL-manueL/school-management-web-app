<?php
require_once '../config.php';

configureCors('GET, OPTIONS');
startSession();

if (isset($_SESSION['student_logged_in']) && $_SESSION['student_logged_in'] === true) {
    apiResponse(true, 'Logged in', [
        'logged_in' => true,
        'student_id' => $_SESSION['student_id'],
        'student_name' => $_SESSION['student_name']
    ]);
} else {
    apiResponse(false, 'Unauthorized', ['logged_in' => false], 401);
}
?>
