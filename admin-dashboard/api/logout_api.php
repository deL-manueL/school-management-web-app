<?php
require_once '../config.php';

configureCors('GET, POST, OPTIONS');
startSession();

session_destroy();
apiResponse(true, 'Logged out successfully');
?>
