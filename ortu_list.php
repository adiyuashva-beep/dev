<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['admin', 'super']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
    edugate_v5_ensure_tables($pdo);
    $sid = school_id();
    if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');

    // Ambil semua user dengan role ortu, lengkap dengan jumlah anak
    $stmt = $pdo->prepare("
        SELECT u.username, u.name AS nama, COUNT(oa.siswa_username) AS jumlah_anak
        FROM users u
        LEFT JOIN ortu_anak oa ON oa.school_id = u.school_id AND oa.ortu_username = u.username
        WHERE u.school_id = ? AND u.role = 'ortu'
        GROUP BY u.username
        ORDER BY u.name ASC
    ");
    $stmt->execute([$sid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}