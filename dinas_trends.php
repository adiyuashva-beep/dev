<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['dinas', 'super', 'admin']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
    $jenjang = $_GET['jenjang'] ?? '';
    $kecamatan = $_GET['kecamatan'] ?? '';

    $labels = [];
    $siswa = [];
    $guru = [];
    $jurnal = [];
    $seven = [];

    for ($i = 6; $i >= 0; $i--) {
        $tgl = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('d/m', strtotime($tgl));

        // Kehadiran siswa
        $sql = "SELECT COUNT(*) FROM absensi a JOIN users u ON a.username = u.username AND a.school_id = u.school_id JOIN schools s ON u.school_id = s.id WHERE a.tanggal = ? AND s.status='active' AND (a.status_terakhir LIKE '%Masuk%' OR a.status_terakhir LIKE '%Hadir%')";
        $params = [$tgl];
        if ($jenjang) { $sql .= " AND s.jenjang = ?"; $params[] = $jenjang; }
        if ($kecamatan) { $sql .= " AND s.kecamatan = ?"; $params[] = $kecamatan; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $siswa[] = (int)$stmt->fetchColumn();

        // Kehadiran guru
        $sql = "SELECT COUNT(*) FROM absensi_guru a JOIN users u ON a.username = u.username AND a.school_id = u.school_id JOIN schools s ON u.school_id = s.id WHERE a.tanggal = ? AND s.status='active' AND a.jam_masuk IS NOT NULL";
        $params = [$tgl];
        if ($jenjang) { $sql .= " AND s.jenjang = ?"; $params[] = $jenjang; }
        if ($kecamatan) { $sql .= " AND s.kecamatan = ?"; $params[] = $kecamatan; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $guru[] = (int)$stmt->fetchColumn();

        // Jumlah jurnal
        $sql = "SELECT COUNT(*) FROM jurnal_guru j JOIN users u ON j.guru_username = u.username AND j.school_id = u.school_id JOIN schools s ON u.school_id = s.id WHERE j.tanggal = ? AND s.status='active'";
        $params = [$tgl];
        if ($jenjang) { $sql .= " AND s.jenjang = ?"; $params[] = $jenjang; }
        if ($kecamatan) { $sql .= " AND s.kecamatan = ?"; $params[] = $kecamatan; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $jurnal[] = (int)$stmt->fetchColumn();

        // Partisipasi 7 kebiasaan (status != draft)
        $sql = "SELECT COUNT(*) FROM kebiasaan7 k JOIN users u ON k.siswa_username = u.username AND k.school_id = u.school_id JOIN schools s ON u.school_id = s.id WHERE k.tanggal = ? AND s.status='active' AND k.status != 'draft'";
        $params = [$tgl];
        if ($jenjang) { $sql .= " AND s.jenjang = ?"; $params[] = $jenjang; }
        if ($kecamatan) { $sql .= " AND s.kecamatan = ?"; $params[] = $kecamatan; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $seven[] = (int)$stmt->fetchColumn();
    }

    echo json_encode([
        'ok' => true,
        'labels' => $labels,
        'siswa' => $siswa,
        'guru' => $guru,
        'jurnal' => $jurnal,
        'seven' => $seven
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}