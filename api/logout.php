<?php
require_once '../api/auth-check.php';

// Hapus semua session
$_SESSION = [];
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: ../views/login.php");
exit();
?>
