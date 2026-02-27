<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['ortu']); // hanya untuk role ortu
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

function json_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

try {
    edugate_v5_ensure_tables($pdo);
    $sid = school_id();
    if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');

    $ortu_username = $_SESSION['user']['username'] ?? '';
    if (!$ortu_username) throw new RuntimeException('Session tidak valid');

    // Ambil parameter siswa dari URL
    $siswa_username = $_GET['siswa'] ?? '';
    if (!$siswa_username) {
        json_out(['ok' => false, 'error' => 'Parameter siswa wajib'], 400);
    }

    // Verifikasi bahwa ortu ini memang berhak atas siswa tersebut
    $stmt = $pdo->prepare("SELECT 1 FROM ortu_anak WHERE school_id = ? AND ortu_username = ? AND siswa_username = ?");
    $stmt->execute([$sid, $ortu_username, $siswa_username]);
    if (!$stmt->fetch()) {
        json_out(['ok' => false, 'error' => 'Anda tidak berhak mengakses data siswa ini'], 403);
    }

    // Ambil data siswa
    $stmt = $pdo->prepare("SELECT username, name AS nama, kelas FROM users WHERE school_id = ? AND username = ?");
    $stmt->execute([$sid, $siswa_username]);
    $siswa = $stmt->fetch();
    if (!$siswa) {
        json_out(['ok' => false, 'error' => 'Siswa tidak ditemukan'], 404);
    }

    // Nama sekolah (dari tabel schools)
    $stmt = $pdo->prepare("SELECT nama_sekolah FROM schools WHERE id = ?");
    $stmt->execute([$sid]);
    $sekolah = $stmt->fetchColumn() ?: '-';

    $tanggal = date('Y-m-d');

    // Data hari ini (absensi)
    $stmt = $pdo->prepare("SELECT jam_masuk, jam_pulang, status_terakhir AS status, foto_masuk FROM absensi WHERE school_id = ? AND tanggal = ? AND username = ? LIMIT 1");
    $stmt->execute([$sid, $tanggal, $siswa_username]);
    $hari_ini = $stmt->fetch();

    // Jurnal hari ini
    $stmt = $pdo->prepare("SELECT mapel, topik AS materi, catatan, guru_nama, jam_ke_mulai, jam_ke_selesai FROM jurnal_guru WHERE school_id = ? AND tanggal = ? AND kelas = ?");
    $stmt->execute([$sid, $tanggal, $siswa['kelas']]);
    $jurnal = $stmt->fetchAll();

    // Riwayat 7 hari terakhir (termasuk hari ini)
    $stmt = $pdo->prepare("
        SELECT tanggal, jam_masuk, jam_pulang, status_terakhir AS status
        FROM absensi
        WHERE school_id = ? AND username = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY tanggal DESC
    ");
    $stmt->execute([$sid, $siswa_username]);
    $riwayat = $stmt->fetchAll();

    // Untuk setiap riwayat, kita bisa tambahkan materi singkat dari jurnal (opsional)
    // Sederhananya, kita bisa ambil jurnal per hari nanti di JS, tapi biar ringan kita kosongkan dulu.
    foreach ($riwayat as &$r) {
        $r['materi_singkat'] = '-'; // bisa diisi nanti
    }

    // 7 Kebiasaan untuk 7 hari terakhir
    $stmt = $pdo->prepare("
        SELECT tanggal, data_json, catatan
        FROM kebiasaan7
        WHERE school_id = ? AND siswa_username = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY tanggal DESC
    ");
    $stmt->execute([$sid, $siswa_username]);
    $seven_hebat = $stmt->fetchAll();

    // Decode data_json
    foreach ($seven_hebat as &$s) {
        $s['data'] = json_decode($s['data_json'], true) ?: [];
        unset($s['data_json']);
    }

    // Susun response
    $response = [
        'ok' => true,
        'data' => [
            'siswa' => [
                'nama' => $siswa['nama'],
                'kelas' => $siswa['kelas'],
                'sekolah' => $sekolah,
                'nisn' => $siswa_username
            ],
            'hari_ini' => $hari_ini,
            'jurnal' => $jurnal,
            'riwayat' => $riwayat,
            'seven_hebat' => $seven_hebat
        ]
    ];

    json_out($response);

} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 500);
}