<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['dinas', 'super', 'admin']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

function angka($val) { return (int)$val; }

try {
    edugate_v5_ensure_tables($pdo);
    $sid = school_id(); // tapi untuk dinas, mungkin ingin lihat semua sekolah? Kita perlu menyesuaikan.
    // Karena role dinas bisa lihat semua sekolah, kita tidak filter school_id di sini.
    // Tapi kita bisa filter berdasarkan jenjang/kecamatan dari parameter.

    $jenjang = $_GET['jenjang'] ?? '';
    $kecamatan = $_GET['kecamatan'] ?? '';

    // Query untuk jumlah sekolah
    $sqlSekolah = "SELECT COUNT(*) FROM schools WHERE status='active'";
    $params = [];
    if ($jenjang !== '') {
        $sqlSekolah .= " AND jenjang = ?";
        $params[] = $jenjang;
    }
    if ($kecamatan !== '') {
        $sqlSekolah .= " AND kecamatan = ?";
        $params[] = $kecamatan;
    }
    $stmt = $pdo->prepare($sqlSekolah);
    $stmt->execute($params);
    $sekolah = angka($stmt->fetchColumn());

    // Untuk data lainnya, kita perlu jumlah siswa/guru per sekolah yang dihitung dari tabel users per school.
    // Kita bisa join dengan schools untuk filter.
    // Tapi ini kompleks, untuk sementara kita ambil global tanpa filter sekolah dulu? Atau kita buat lebih sederhana: hitung semua user dengan role siswa/guru di semua sekolah yang aktif.
    // Untuk filter jenjang/kecamatan, kita perlu join dengan schools.

    $sqlSiswa = "SELECT COUNT(*) FROM users u JOIN schools s ON u.school_id = s.id WHERE u.role='siswa' AND s.status='active'";
    $paramsS = [];
    if ($jenjang !== '') {
        $sqlSiswa .= " AND s.jenjang = ?";
        $paramsS[] = $jenjang;
    }
    if ($kecamatan !== '') {
        $sqlSiswa .= " AND s.kecamatan = ?";
        $paramsS[] = $kecamatan;
    }
    $stmt = $pdo->prepare($sqlSiswa);
    $stmt->execute($paramsS);
    $siswa = angka($stmt->fetchColumn());

    $sqlGuru = "SELECT COUNT(*) FROM users u JOIN schools s ON u.school_id = s.id WHERE u.role IN ('guru','staff','bk','kesiswaan','kurikulum') AND s.status='active'";
    $paramsG = [];
    if ($jenjang !== '') {
        $sqlGuru .= " AND s.jenjang = ?";
        $paramsG[] = $jenjang;
    }
    if ($kecamatan !== '') {
        $sqlGuru .= " AND s.kecamatan = ?";
        $paramsG[] = $kecamatan;
    }
    $stmt = $pdo->prepare($sqlGuru);
    $stmt->execute($paramsG);
    $guru = angka($stmt->fetchColumn());

    // Kehadiran siswa hari ini
    $today = date('Y-m-d');
    $sqlHadirSiswa = "SELECT COUNT(*) FROM absensi a JOIN users u ON a.username = u.username AND a.school_id = u.school_id JOIN schools s ON u.school_id = s.id WHERE a.tanggal = ? AND s.status='active' AND (a.status_terakhir LIKE '%Masuk%' OR a.status_terakhir LIKE '%Hadir%')";
    $paramsHS = [$today];
    if ($jenjang !== '') { $sqlHadirSiswa .= " AND s.jenjang = ?"; $paramsHS[] = $jenjang; }
    if ($kecamatan !== '') { $sqlHadirSiswa .= " AND s.kecamatan = ?"; $paramsHS[] = $kecamatan; }
    $stmt = $pdo->prepare($sqlHadirSiswa);
    $stmt->execute($paramsHS);
    $hadir_siswa = angka($stmt->fetchColumn());

    // Kehadiran guru hari ini
    $sqlHadirGuru = "SELECT COUNT(*) FROM absensi_guru a JOIN users u ON a.username = u.username AND a.school_id = u.school_id JOIN schools s ON u.school_id = s.id WHERE a.tanggal = ? AND s.status='active' AND a.jam_masuk IS NOT NULL";
    $paramsHG = [$today];
    if ($jenjang !== '') { $sqlHadirGuru .= " AND s.jenjang = ?"; $paramsHG[] = $jenjang; }
    if ($kecamatan !== '') { $sqlHadirGuru .= " AND s.kecamatan = ?"; $paramsHG[] = $kecamatan; }
    $stmt = $pdo->prepare($sqlHadirGuru);
    $stmt->execute($paramsHG);
    $hadir_guru = angka($stmt->fetchColumn());

    // Jurnal hari ini
    $sqlJurnal = "SELECT COUNT(*) FROM jurnal_guru j JOIN users u ON j.guru_username = u.username AND j.school_id = u.school_id JOIN schools s ON u.school_id = s.id WHERE j.tanggal = ? AND s.status='active'";
    $paramsJ = [$today];
    if ($jenjang !== '') { $sqlJurnal .= " AND s.jenjang = ?"; $paramsJ[] = $jenjang; }
    if ($kecamatan !== '') { $sqlJurnal .= " AND s.kecamatan = ?"; $paramsJ[] = $kecamatan; }
    $stmt = $pdo->prepare($sqlJurnal);
    $stmt->execute($paramsJ);
    $jurnal_hari_ini = angka($stmt->fetchColumn());

    // 7 kebiasaan hari ini (jumlah siswa yang mengisi)
    $sqlSeven = "SELECT COUNT(*) FROM kebiasaan7 k JOIN users u ON k.siswa_username = u.username AND k.school_id = u.school_id JOIN schools s ON u.school_id = s.id WHERE k.tanggal = ? AND s.status='active' AND k.status != 'draft'";
    $paramsS7 = [$today];
    if ($jenjang !== '') { $sqlSeven .= " AND s.jenjang = ?"; $paramsS7[] = $jenjang; }
    if ($kecamatan !== '') { $sqlSeven .= " AND s.kecamatan = ?"; $paramsS7[] = $kecamatan; }
    $stmt = $pdo->prepare($sqlSeven);
    $stmt->execute($paramsS7);
    $seven_hari_ini = angka($stmt->fetchColumn());

    // Jumlah validasi 7 kebiasaan (status = 'valid')
    $sqlSevenValid = "SELECT COUNT(*) FROM kebiasaan7 k JOIN users u ON k.siswa_username = u.username AND k.school_id = u.school_id JOIN schools s ON u.school_id = s.id WHERE k.tanggal = ? AND k.status = 'valid' AND s.status='active'";
    $paramsSV = [$today];
    if ($jenjang !== '') { $sqlSevenValid .= " AND s.jenjang = ?"; $paramsSV[] = $jenjang; }
    if ($kecamatan !== '') { $sqlSevenValid .= " AND s.kecamatan = ?"; $paramsSV[] = $kecamatan; }
    $stmt = $pdo->prepare($sqlSevenValid);
    $stmt->execute($paramsSV);
    $seven_valid = angka($stmt->fetchColumn());

    echo json_encode([
        'ok' => true,
        'data' => [
            'sekolah' => $sekolah,
            'siswa' => $siswa,
            'guru' => $guru,
            'hadir_siswa' => $hadir_siswa,
            'hadir_guru' => $hadir_guru,
            'jurnal_hari_ini' => $jurnal_hari_ini,
            'seven_hari_ini' => $seven_hari_ini,
            'seven_valid' => $seven_valid
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}