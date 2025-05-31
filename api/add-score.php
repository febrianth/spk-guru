<?php
require_once '../api/auth-check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $alternative_id = $_POST['alternative_id'] ?? '';

    // Skor nilai
    $kehadiran = $_POST['kehadiran'] ?? null;
    $sikap = $_POST['sikap_profesional'] ?? null;
    $tanggung_jawab = $_POST['tanggung_jawab'] ?? null;
    $orientasi = $_POST['orientasi_layanan'] ?? null;

    if ($kehadiran && $sikap && $tanggung_jawab && $orientasi) {
        try {
            $conn = Database::getConnection();

            if ($id) {
                // UPDATE
                $query = "UPDATE scores 
                          SET kehadiran = :kehadiran, sikap_profesional = :sikap, tanggung_jawab = :tanggung_jawab, orientasi_layanan = :orientasi 
                          WHERE alternative_id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            } else {
                // INSERT
                $query = "INSERT INTO scores (alternative_id, kehadiran, sikap_profesional, tanggung_jawab, orientasi_layanan) 
                          VALUES (:alternative_id, :kehadiran, :sikap, :tanggung_jawab, :orientasi)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':alternative_id', $alternative_id);
            }

            $stmt->bindParam(':kehadiran', $kehadiran);
            $stmt->bindParam(':sikap', $sikap);
            $stmt->bindParam(':tanggung_jawab', $tanggung_jawab);
            $stmt->bindParam(':orientasi', $orientasi);
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
