<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['dinas', 'admin', 'super']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
    edugate_v5_ensure_tables($pdo);

    // Ambil semua kecamatan unik dari tabel schools
    $stmt = $pdo->query("SELECT DISTINCT kecamatan FROM schools WHERE kecamatan IS NOT NULL AND kecamatan != '' ORDER BY kecamatan");
    $kecamatan = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Ambil semua jenjang unik
    $stmt = $pdo->query("SELECT DISTINCT jenjang FROM schools ORDER BY jenjang");
    $jenjang = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'ok' => true,
        'data' => [
            'kecamatan' => $kecamatan,
            'jenjang' => $jenjang,
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}