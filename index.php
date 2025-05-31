<?php
session_start();

// Cek jika user sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: views/dashboard.php");
    exit();
}

// Jika tidak login, arahkan ke halaman login
header("Location: views/login.php");
exit();
?>