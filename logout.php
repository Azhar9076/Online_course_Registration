<?php
require_once 'db.php';

// Destroy the session
$_SESSION = [];
session_destroy();

// Redirect to login
header("Location: login.php");
exit();
?>