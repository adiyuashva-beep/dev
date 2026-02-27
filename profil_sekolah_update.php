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
    $npsn = trim($in['npsn'] ?? '');
    $provinsi = trim($in['provinsi'] ?? '');
    $kabupaten = trim($in['kabupaten'] ?? '');
    $kecamatan = trim($in['kecamatan'] ?? '');
    $kelurahan = trim($in['kelurahan'] ?? '');
    $kode_pos = trim($in['kode_pos'] ?? '');
    $alamat = trim($in['alamat'] ?? '');

    if ($npsn === '' || $provinsi === '' || $kabupaten === '' || $kecamatan === '' || $kelurahan === '' || $alamat === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'NPSN, provinsi, kabupaten, kecamatan, kelurahan, alamat wajib diisi']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE schools SET npsn = ?, provinsi = ?, kabupaten = ?, kecamatan = ?, kelurahan = ?, kode_pos = ?, alamat = ? WHERE id = ?");
    $stmt->execute([$npsn, $provinsi, $kabupaten, $kecamatan, $kelurahan, $kode_pos, $alamat, $sid]);

    echo json_encode(['ok' => true, 'message' => 'Data sekolah diperbarui']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}