<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['admin', 'super']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

function json_in() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

try {
    edugate_v5_ensure_tables($pdo);
    $sid = school_id();
    if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');

    $in = json_in();
    $username = trim($in['username'] ?? '');
    if ($username === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'username wajib']);
        exit;
    }

    $pdo->beginTransaction();

    // Hapus relasi anak
    $stmt = $pdo->prepare("DELETE FROM ortu_anak WHERE school_id = ? AND ortu_username = ?");
    $stmt->execute([$sid, $username]);

    // Hapus user
    $stmt = $pdo->prepare("DELETE FROM users WHERE school_id = ? AND username = ? AND role = 'ortu'");
    $stmt->execute([$sid, $username]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'message' => 'Orang tua dihapus']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}