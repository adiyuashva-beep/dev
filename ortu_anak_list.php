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

    $username = $_GET['username'] ?? '';
    if ($username === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'username wajib']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT siswa_username AS username, u.name AS nama, u.kelas
        FROM ortu_anak oa
        JOIN users u ON u.username = oa.siswa_username AND u.school_id = oa.school_id
        WHERE oa.school_id = ? AND oa.ortu_username = ?
        ORDER BY u.name ASC
    ");
    $stmt->execute([$sid, $username]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}