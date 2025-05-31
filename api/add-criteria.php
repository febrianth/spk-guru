<?php
require_once '../api/auth-check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $weight = $_POST['weight'] ?? '';

    if ($weight !== '' && $id) {
        try {
            $conn = Database::getConnection();

            // Get old weight
            $stmtOld = $conn->prepare("SELECT weight FROM criterias WHERE id = :id");
            $stmtOld->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtOld->execute();
            $old_weight = $stmtOld->fetchColumn();

            // Sum total weight
            $stmtSum = $conn->query("SELECT SUM(weight) FROM criterias");
            $sum_weight = $stmtSum->fetchColumn();

            $max_weight = $sum_weight - $old_weight;
            $new_weight = $max_weight + $weight;

            if ($new_weight > 1) {
                $available_weight = 1 - $max_weight;
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal! Bobot melebihi 1. Total bobot tersedia: ' . number_format($available_weight, 2)
                ]);
                exit;
            }

            // UPDATE
            $query = "UPDATE criterias 
                        SET weight = :weight
                        WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            $stmt->bindParam(':weight', $weight);
            $stmt->execute();

            echo json_encode([
                'success' => true,
                'message' => $id ? 'Data berhasil diperbarui.' : 'Data berhasil ditambahkan.'
            ]);
        } catch (PDOException $e) {
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
