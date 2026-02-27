<?php
require __DIR__ . '/../auth/guard.php';
require_login(['admin','super','bk','kesiswaan','kurikulum']);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/database.php';
require __DIR__ . '/_schema.php';
if (!isset($pdo)) {
  echo json_encode(['ok'=>false,'error'=>'Koneksi DB ($pdo) tidak ditemukan. Pastikan config/database.php membuat $pdo']);
  exit;
}

function ok($data=[]){ echo json_encode(['ok'=>true] + $data); exit; }
function fail($msg){ echo json_encode(['ok'=>false,'error'=>$msg]); exit; }

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) fail('Body JSON tidak valid');

$section = $body['section'] ?? '';
if (!$section) fail('section wajib');

try {
  edugate_v5_ensure_tables($pdo);
  $sid = (int)($_SESSION['school_id'] ?? 0);
  if ($sid <= 0) fail('Tenant tidak valid');

  $pdo->prepare("INSERT IGNORE INTO pengaturan_sekolah (school_id) VALUES (?)")->execute([$sid]);

  if ($section === 'mode') {
    $mode = !empty($body['mode_bebas_pulang']) ? 1 : 0;
    $pesan = (string)($body['pesan_bebas_pulang'] ?? '');

    $st = $pdo->prepare("UPDATE pengaturan_sekolah SET mode_bebas_pulang=?, pesan_bebas_pulang=? WHERE school_id=?");
    $st->execute([$mode, $pesan, $sid]);
    ok();
  }

  if ($section === 'gps') {
    $mode_gps = !empty($body['mode_gps']) ? 1 : 0;
    $radius_m = (int)($body['radius_m'] ?? 50);
    if ($radius_m < 10) $radius_m = 10;
    if ($radius_m > 2000) $radius_m = 2000;

    $lat = $body['lokasi_lat'] ?? null;
    $lng = $body['lokasi_lng'] ?? null;
    $lat = ($lat === null || $lat === '') ? null : (float)$lat;
    $lng = ($lng === null || $lng === '') ? null : (float)$lng;

    $st = $pdo->prepare("UPDATE pengaturan_sekolah SET mode_gps=?, radius_m=?, lokasi_lat=?, lokasi_lng=? WHERE school_id=?");
    $st->execute([$mode_gps, $radius_m, $lat, $lng, $sid]);
    ok();
  }

  if ($section === 'akses') {
    $akses_siswa   = !empty($body['akses_siswa']) ? 1 : 0;
    $akses_guru    = !empty($body['akses_guru']) ? 1 : 0;
    $akses_ortu    = !empty($body['akses_ortu']) ? 1 : 0;
    $akses_pejabat = !empty($body['akses_pejabat']) ? 1 : 0;

    $refleksi_ortu = !empty($body['refleksi_ortu']) ? 1 : 0;
    $refleksi_guru = !empty($body['refleksi_guru']) ? 1 : 0;

    $st = $pdo->prepare("UPDATE pengaturan_sekolah
      SET akses_siswa=?, akses_guru=?, akses_ortu=?, akses_pejabat=?,
          refleksi_ortu=?, refleksi_guru=?
      WHERE school_id=?");
    $st->execute([$akses_siswa,$akses_guru,$akses_ortu,$akses_pejabat,$refleksi_ortu,$refleksi_guru,$sid]);
    ok();
  }

  if ($section === 'jadwal') {
    $jadwal = $body['jadwal'] ?? null;
    if (!is_array($jadwal)) fail('jadwal harus object');

    $hariList = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
    $ins = $pdo->prepare("
      INSERT INTO jam_operasional (school_id, hari, masuk, telat, pulang, is_libur)
      VALUES (:sid, :hari, :masuk, :telat, :pulang, :is_libur)
      ON DUPLICATE KEY UPDATE masuk=VALUES(masuk), telat=VALUES(telat), pulang=VALUES(pulang), is_libur=VALUES(is_libur)
    ");

    $count = 0;
    foreach ($hariList as $h) {
      $d = $jadwal[$h] ?? null;
      if (!is_array($d)) continue;

      $is_libur = !empty($d['is_libur']) ? 1 : 0;

      $masuk  = (string)($d['masuk'] ?? '06:30');  if (strlen($masuk)===5)  $masuk .= ':00';
      $telat  = (string)($d['telat'] ?? '07:00');  if (strlen($telat)===5)  $telat .= ':00';
      $pulang = (string)($d['pulang']?? '15:30');  if (strlen($pulang)===5) $pulang .= ':00';

      $ins->execute([
        ':sid'=>$sid,
        ':hari'=>$h,
        ':masuk'=>$masuk,
        ':telat'=>$telat,
        ':pulang'=>$pulang,
        ':is_libur'=>$is_libur
      ]);
      $count++;
    }

    ok(['updated'=>$count]);
  }

  fail('section tidak dikenal: '.$section);

} catch (Throwable $e) {
  fail($e->getMessage());
}
