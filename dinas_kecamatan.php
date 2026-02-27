<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['dinas', 'super', 'admin']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT DISTINCT kecamatan FROM schools WHERE kecamatan IS NOT NULL AND kecamatan != '' ORDER BY kecamatan");
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['ok' => true, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}