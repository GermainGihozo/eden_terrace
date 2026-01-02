<?php
// admin/index.php
require_once __DIR__ . '/../includes/auth.php';

startSession();

if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

header('Location: dashboard.php');
exit();
?>