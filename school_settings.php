<?php
require __DIR__ . "/../auth/guard.php";
require_login(['siswa','guru','admin','super','bk','kesiswaan','kurikulum','staff']);

require __DIR__ . "/../config/database.php";
require __DIR__ . "/_schema.php";

header('Content-Type: application/json; charset=utf-8');

function indo_hari(int $n): string {
  $map = [1=>"Senin",2=>"Selasa",3=>"Rabu",4=>"Kamis",5=>"Jumat",6=>"Sabtu",7=>"Minggu"]; 
  return $map[$n] ?? 'Senin';
}

try {
  edugate_v5_ensure_tables($pdo);
  $sid = (int)school_id();
  if ($sid <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Tenant tidak valid']);
    exit;
  }

  // Pastikan row setting ada
  $pdo->prepare("INSERT IGNORE INTO pengaturan_sekolah (school_id) VALUES (?)")->execute([$sid]);

  $st = $pdo->prepare("SELECT mode_gps, radius_m, lokasi_lat, lokasi_lng FROM pengaturan_sekolah WHERE school_id=? LIMIT 1");
  $st->execute([$sid]);
  $cfg = $st->fetch(PDO::FETCH_ASSOC) ?: [];

  $lat = (float)($cfg['lokasi_lat'] ?? -7.6739830);
  $lng = (float)($cfg['lokasi_lng'] ?? 109.6319560);
  $radius = (int)($cfg['radius_m'] ?? 50);
  $mode_gps = (int)($cfg['mode_gps'] ?? 1);

  // Jam operasional hari ini (dipakai sebagai referensi jam buka presensi)
  $hari = indo_hari((int)date('N'));
  $open = '05:55';
  $masuk = null; $telat = null; $pulang = null; $is_libur = 0;

  $st = $pdo->prepare("SELECT masuk, telat, pulang, is_libur FROM jam_operasional WHERE school_id=? AND hari=? LIMIT 1");
  $st->execute([$sid, $hari]);
  if ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $is_libur = (int)($r['is_libur'] ?? 0);
    if (!empty($r['masuk']))  { $masuk = substr((string)$r['masuk'], 0, 5); $open = $masuk; }
    if (!empty($r['telat']))  { $telat = substr((string)$r['telat'], 0, 5); }
    if (!empty($r['pulang'])) { $pulang = substr((string)$r['pulang'], 0, 5); }
  }

  echo json_encode([
    'ok'=>true,
    'data'=>[
      'mode_gps'=>$mode_gps,
      'sekolah_lat'=>$lat,
      'sekolah_lng'=>$lng,
      'sekolah_radius'=>$radius,
      'presensi_open_time'=>$open,
      'hari'=>$hari,
      'is_libur'=>$is_libur,
      'masuk'=>$masuk,
      'telat'=>$telat,
      'pulang'=>$pulang,
    ]
  ]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
