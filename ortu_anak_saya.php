<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['ortu']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
    edugate_v5_ensure_tables($pdo);
    $sid = school_id();
    if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');

    $ortu_username = $_SESSION['user']['username'] ?? '';
    if (!$ortu_username) throw new RuntimeException('Session tidak valid');

    // Ambil daftar anak untuk orang tua ini, lengkap dengan nama sekolah
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            u.name AS nama,
            u.kelas,
            s.nama_sekolah AS sekolah,
            LEFT(u.name, 1) AS inisial
        FROM ortu_anak oa
        JOIN users u ON u.username = oa.siswa_username AND u.school_id = oa.school_id
        JOIN schools s ON s.id = u.school_id
        WHERE oa.school_id = ? AND oa.ortu_username = ?
        ORDER BY u.name ASC
    ");
    $stmt->execute([$sid, $ortu_username]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}