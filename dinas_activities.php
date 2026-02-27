<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['dinas', 'super', 'admin']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
    // Ambil aktivitas dari absensi_log (siswa)
    $stmt1 = $pdo->query("
        SELECT 
            CONCAT('Siswa ', u.name, ' ', a.status) as pesan,
            a.created_at as waktu,
            'bg-emerald-500' as warna
        FROM absensi_log a
        JOIN users u ON u.username = a.username AND u.school_id = a.school_id
        ORDER BY a.created_at DESC
        LIMIT 20
    ");
    $siswa = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // Ambil aktivitas dari absensi_guru_log (guru)
    $stmt2 = $pdo->query("
        SELECT 
            CONCAT('Guru ', u.name, ' ', a.status) as pesan,
            a.created_at as waktu,
            'bg-blue-500' as warna
        FROM absensi_guru_log a
        JOIN users u ON u.username = a.username AND u.school_id = a.school_id
        ORDER BY a.created_at DESC
        LIMIT 20
    ");
    $guru = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Ambil aktivitas dari jurnal_guru
    $stmt3 = $pdo->query("
        SELECT 
            CONCAT('Guru ', guru_nama, ' mengisi jurnal ', mapel) as pesan,
            created_at as waktu,
            'bg-yellow-500' as warna
        FROM jurnal_guru
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $jurnal = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    // Ambil aktivitas dari kebiasaan7
    $stmt4 = $pdo->query("
        SELECT 
            CONCAT('Siswa ', siswa_nama, ' mengisi 7 kebiasaan') as pesan,
            created_at as waktu,
            'bg-purple-500' as warna
        FROM kebiasaan7
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $kebiasaan = $stmt4->fetchAll(PDO::FETCH_ASSOC);

    // Gabungkan semua, urutkan berdasarkan waktu terbaru, ambil 50
    $all = array_merge($siswa, $guru, $jurnal, $kebiasaan);
    usort($all, function($a, $b) {
        return strtotime($b['waktu']) - strtotime($a['waktu']);
    });
    $all = array_slice($all, 0, 50);

    // Format waktu jadi jam:menit
    foreach ($all as &$r) {
        $r['waktu'] = date('H:i', strtotime($r['waktu']));
    }

    echo json_encode(['ok' => true, 'data' => $all]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}