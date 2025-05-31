<?php
// Memulai sesi di auth.php
session_start();
header("Content-Type: application/json");

// Panggil koneksi database
require_once __DIR__ . '/../config/database.php';

// Cek apakah requestnya POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data POST dari form
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validasi data
    if (!empty($email) && !empty($password)) {
        // Inisialisasi koneksi database
        $conn = Database::getConnection();

        // Query untuk mencari user berdasarkan email dan password
        $query = "SELECT id, email, password FROM users WHERE email = :email AND password = :password LIMIT 1";

        // Siapkan query menggunakan prepared statement
        $stmt = $conn->prepare($query);

        // Bind parameter untuk mencegah SQL Injection
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":password", $password, PDO::PARAM_STR);

        // Eksekusi query
        $stmt->execute();

        // Ambil hasilnya
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Jika user ditemukan
        if ($user) {
            // Jika login sukses, simpan session
            $_SESSION['id'] = $user['id'];
            $_SESSION['email'] = $user['email'];

            // Redirect ke dashboard
            header("Location: ../views/dashboard.php");
            exit();
        } else {
            // Jika login gagal
            $_SESSION['error_message'] = 'Invalid email or password';
            header("Location: ../views/login.php"); // Redirect kembali ke login
            exit();
        }
    } else {
        // Jika data email dan password tidak ada
        $_SESSION['error_message'] = 'email and password are required';
        header("Location: ../views/login.php"); // Redirect kembali ke login
        exit();
    }
} else {
    // Jika request bukan POST
    $_SESSION['error_message'] = 'Invalid request method';
    header("Location: ../views/login.php"); // Redirect kembali ke login
    exit();
}
