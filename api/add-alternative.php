<?php
require_once '../api/auth-check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $nip = $_POST['nip'] ?? '';
    $position = $_POST['position'] ?? '';
    $type = $_POST['type'] ?? '';

    if ($name && $nip && $position && $type) {
        try {
            $conn = Database::getConnection();

            if ($id) {
                // Mode UPDATE
                $query = "UPDATE alternatives 
                          SET name = :name, nip = :nip, position = :position, type = :type 
                          WHERE id = :id AND deleted_at IS NULL";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            } else {
                // Mode INSERT
                $query = "INSERT INTO alternatives (name, nip, position, type)
                          VALUES (:name, :nip, :position, :type)";
                $stmt = $conn->prepare($query);
            }

            // Bind data
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':nip', $nip);
            $stmt->bindParam(':position', $position);
            $stmt->bindParam(':type', $type);

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
