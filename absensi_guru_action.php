<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['guru','staff','bk','kesiswaan','kurikulum','admin','super']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

function json_in(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '[]', true);
  return is_array($data) ? $data : [];
}

function saveDataUrlJpg(?string $dataUrl, string $dir, string $filenameBase): ?string {
  if (!$dataUrl) return null;
  $dataUrl = trim($dataUrl);
  if ($dataUrl === '' || $dataUrl === '-') return null;
  if (!preg_match('#^data:image/(png|jpeg|jpg);base64,#', $dataUrl)) return null;

  $parts = explode(',', $dataUrl, 2);
  if (count($parts) !== 2) return null;
  if (strlen($parts[1]) > 6_000_000) return null;

  $bin = base64_decode($parts[1], true);
  if ($bin === false) return null;
  if (strlen($bin) > 4_000_000) return null;

  if (!is_dir($dir)) mkdir($dir, 0775, true);
  $path = rtrim($dir,'/') . '/' . $filenameBase . '.jpg';
  file_put_contents($path, $bin);
  return $path;
}

function haversine_m(float $lat1, float $lon1, float $lat2, float $lon2): float {
  $R = 6371000.0;
  $dLat = deg2rad($lat2 - $lat1);
  $dLon = deg2rad($lon2 - $lon1);
  $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
  $c = 2 * asin(min(1.0, sqrt($a)));
  return $R * $c;
}

try {
  edugate_v5_ensure_tables($pdo);

  // [FIX] Ambil school_id dari session
  $sid = school_id();

  // pastikan row pengaturan_sekolah ada (dengan school_id)
  try { $pdo->prepare("INSERT IGNORE INTO pengaturan_sekolah (school_id) VALUES (?)")->execute([$sid]); } catch (Throwable $e) {}

  // cek akses guru (per sekolah)
  $aksesGuru = 1;
  try {
    $st = $pdo->prepare("SELECT akses_guru FROM pengaturan_sekolah WHERE school_id = ?");
    $st->execute([$sid]);
    $aksesGuru = (int)($st->fetchColumn() ?? 1);
  } catch (Throwable $e) {
    $aksesGuru = 1;
  }
  if ($aksesGuru === 0) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'Akses presensi guru sedang OFF oleh admin.']);
    exit;
  }

  $u = $_SESSION['user'] ?? [];
  $username = (string)($u['username'] ?? '');
  $nama = (string)($u['name'] ?? $username);

  $in = json_in();
  $tipe = strtolower((string)($in['tipe'] ?? ''));
  if (!in_array($tipe, ['masuk','pulang'], true)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'tipe harus masuk/pulang']);
    exit;
  }

  $lat = isset($in['lat']) ? (float)$in['lat'] : null;
  $lng = isset($in['lng']) ? (float)$in['lng'] : null;
  $akurasi = isset($in['akurasi']) ? (float)$in['akurasi'] : null;

  // Enforce GPS jika mode_gps=1 (per sekolah)
  try {
    $st = $pdo->prepare("SELECT mode_gps, lokasi_lat, lokasi_lng, radius_m FROM pengaturan_sekolah WHERE school_id = ?");
    $st->execute([$sid]);
    $set = $st->fetch(PDO::FETCH_ASSOC);
    if ($set && (int)$set['mode_gps'] === 1) {
      if ($lat === null || $lng === null) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'GPS wajib diaktifkan (lat/lng kosong).']);
        exit;
      }
      $d = haversine_m($lat, $lng, (float)$set['lokasi_lat'], (float)$set['lokasi_lng']);
      if ($d > (float)$set['radius_m']) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'Di luar radius sekolah.']);
        exit;
      }
    }
  } catch (Throwable $e) {
    // settings optional
  }

  $tanggal = date('Y-m-d');
  $waktu = date('Y-m-d H:i:s');

  // foto (selfie)
  $foto = isset($in['foto']) ? (string)$in['foto'] : null;
  $fotoPath = null;
  if ($foto) {
    $dir = __DIR__ . '/../uploads/absen_guru/' . $tanggal;
    $base = $username . '_' . $tipe . '_' . time();
    $saved = saveDataUrlJpg($foto, $dir, $base);
    if ($saved) $fotoPath = $saved;
  }
  $fotoUrl = null;
  if ($fotoPath) {
    $publicBase = (strpos($_SERVER['REQUEST_URI'] ?? '', '/app/') !== false) ? '/app' : '';
    $fotoUrl = $publicBase . '/uploads/absen_guru/' . $tanggal . '/' . basename($fotoPath);
  }

  if ($fotoUrl === null) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Foto selfie wajib untuk presensi.']);
    exit;
  }

  // ambil status existing dengan school_id
  $stmt = $pdo->prepare('SELECT jam_masuk, jam_pulang FROM absensi_guru WHERE school_id = ? AND tanggal = ? AND username = ? LIMIT 1');
  $stmt->execute([$sid, $tanggal, $username]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row && $tipe === 'pulang') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Belum presensi masuk hari ini.']);
    exit;
  }

  if (!$row) {
    $pdo->prepare('INSERT INTO absensi_guru (school_id, tanggal, username, nama, status_terakhir, jam_masuk, lokasi_masuk, foto_masuk, foto_pulang) VALUES (?,?,?,?,?,?,?,?,?)')
        ->execute([
          $sid,
          $tanggal,
          $username,
          $nama,
          $tipe,
          $tipe==='masuk' ? date('H:i:s') : null,
          ($lat!==null && $lng!==null) ? ("lat=$lat,lng=$lng,acc=$akurasi") : null,
          $tipe==='masuk' ? $fotoUrl : null,
          $tipe==='pulang' ? $fotoUrl : null,
        ]);
  } else {
    if ($tipe === 'masuk' && !empty($row['jam_masuk'])) {
      echo json_encode(['ok'=>true,'message'=>'Jam masuk sudah tercatat.','data'=>$row]);
      exit;
    }
    if ($tipe === 'pulang' && empty($row['jam_masuk'])) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'error'=>'Belum presensi masuk hari ini.']);
      exit;
    }
    if ($tipe === 'pulang' && !empty($row['jam_pulang'])) {
      echo json_encode(['ok'=>true,'message'=>'Jam pulang sudah tercatat.','data'=>$row]);
      exit;
    }

    if ($tipe === 'masuk') {
      $pdo->prepare('UPDATE absensi_guru SET jam_masuk=?, status_terakhir=?, lokasi_masuk=?, foto_masuk=? WHERE school_id=? AND tanggal=? AND username=?')
          ->execute([date('H:i:s'), $tipe, ($lat!==null && $lng!==null) ? ("lat=$lat,lng=$lng,acc=$akurasi") : null, $fotoUrl, $sid, $tanggal, $username]);
    } else {
      $pdo->prepare('UPDATE absensi_guru SET jam_pulang=?, status_terakhir=?, lokasi_pulang=?, foto_pulang=? WHERE school_id=? AND tanggal=? AND username=?')
          ->execute([date('H:i:s'), $tipe, ($lat!==null && $lng!==null) ? ("lat=$lat,lng=$lng,acc=$akurasi") : null, $fotoUrl, $sid, $tanggal, $username]);
    }
  }

  // log dengan school_id
  $pdo->prepare('INSERT INTO absensi_guru_log (school_id, tanggal, username, waktu, status, ket, lat, lng, akurasi) VALUES (?,?,?,?,?,?,?,?,?)')
      ->execute([$sid, $tanggal, $username, $waktu, $tipe, null, $lat, $lng, $akurasi]);

  $stmt = $pdo->prepare('SELECT jam_masuk, jam_pulang, status_terakhir, foto_masuk, foto_pulang FROM absensi_guru WHERE school_id=? AND tanggal=? AND username=? LIMIT 1');
  $stmt->execute([$sid, $tanggal, $username]);
  $out = $stmt->fetch(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true,'message'=>'Presensi tersimpan.','data'=>$out]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}