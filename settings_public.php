<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/tenant.php';
require __DIR__ . '/_schema.php';
if (!isset($pdo)) {
  echo json_encode(['ok'=>false,'error'=>'Koneksi DB ($pdo) tidak ditemukan. Pastikan config/database.php membuat $pdo']);
  exit;
}

function ok($data){ echo json_encode(['ok'=>true] + $data); exit; }
function fail($msg){ echo json_encode(['ok'=>false,'error'=>$msg]); exit; }

try {
  edugate_v5_ensure_tables($pdo);
  $sid = (int)($_SESSION['school_id'] ?? 0);
  if ($sid <= 0) fail('Tenant tidak valid');

  $pdo->prepare("INSERT IGNORE INTO pengaturan_sekolah (school_id) VALUES (?)")->execute([$sid]);

  $stCfg = $pdo->prepare("SELECT * FROM pengaturan_sekolah WHERE school_id=? LIMIT 1");
  $stCfg->execute([$sid]);
  $cfg = $stCfg->fetch(PDO::FETCH_ASSOC);

  // Ambil jadwal semua hari
  $stmt = $pdo->prepare("SELECT * FROM jam_operasional WHERE school_id=?");
  $stmt->execute([$sid]);
  $jadwalRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $jadwal = [];
  foreach ($jadwalRaw as $r) {
    $jadwal[$r['hari']] = [
      'is_libur' => (int)$r['is_libur'],
      'masuk' => substr((string)$r['masuk'],0,5),
      'telat' => substr((string)$r['telat'],0,5),
      'pulang'=> substr((string)$r['pulang'],0,5),
    ];
  }

  $mapHari = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];
  $todayName = $mapHari[(int)date('N')] ?? 'Senin';

  $today = $jadwal[$todayName] ?? ['is_libur'=>0,'masuk'=>'06:30','telat'=>'07:00','pulang'=>'15:30'];

  ok([
    'data' => [
      'mode_bebas_pulang' => (int)($cfg['mode_bebas_pulang'] ?? 0),
      'pesan_bebas_pulang'=> (string)($cfg['pesan_bebas_pulang'] ?? ''),
      'mode_gps'          => (int)($cfg['mode_gps'] ?? 1),
      'radius_m'          => (int)($cfg['radius_m'] ?? 50),
      'lokasi_lat'        => isset($cfg['lokasi_lat']) ? (float)$cfg['lokasi_lat'] : null,
      'lokasi_lng'        => isset($cfg['lokasi_lng']) ? (float)$cfg['lokasi_lng'] : null,

      'akses_siswa'       => (int)($cfg['akses_siswa'] ?? 1),
      'akses_guru'        => (int)($cfg['akses_guru'] ?? 1),
      'akses_ortu'        => (int)($cfg['akses_ortu'] ?? 0),
      'akses_pejabat'     => (int)($cfg['akses_pejabat'] ?? 1),

      'refleksi_ortu'     => (int)($cfg['refleksi_ortu'] ?? 0),
      'refleksi_guru'     => (int)($cfg['refleksi_guru'] ?? 1),
    ],
    'today' => [
      'hari' => $todayName,
      'is_libur' => (int)$today['is_libur'],
      'masuk' => $today['masuk'],
      'telat' => $today['telat'],
      'pulang'=> $today['pulang']
    ]
  ]);

} catch (Throwable $e) {
  fail($e->getMessage());
}
