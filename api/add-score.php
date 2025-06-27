<?php
require_once '../api/auth-check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $alternative_id = $_POST['alternative_id'] ?? '';

    // Skor nilai
    $C1 = $_POST['C1'] ?? null;
    $C2 = $_POST['C2'] ?? null;
    $C3 = $_POST['C3'] ?? null;
    $C4 = $_POST['C4'] ?? null;

    if ($C1 && $C2 && $C3 && $C4) {
        try {
            $conn = Database::getConnection();

            if ($id) {
                // UPDATE
                $query = "UPDATE scores 
                          SET C1 = :C1, C2 = :C2, C3 = :C3, C4 = :C4 
                          WHERE alternative_id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            } else {
                // INSERT
                $query = "INSERT INTO scores (alternative_id, c1, C2, C3, C4) 
                          VALUES (:alternative_id, :C1, :C2, :C3, :C4)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':alternative_id', $alternative_id);
            }

            $stmt->bindParam(':C1', $C1);
            $stmt->bindParam(':C2', $C2);
            $stmt->bindParam(':C3', $C3);
            $stmt->bindParam(':C4', $C4);
            $stmt->execute();

            echo json_encode([
                'success' => true,
                'message' => $id ? 'Data berhasil diperbarui.' : 'Data berhasil ditambahkan.'
            ]);
        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Kesalahan server: ' . $e->getMessage()
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Semua field harus diisi.'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metode tidak valid.'
    ]);
}
