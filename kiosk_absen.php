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

try {
  edugate_v5_ensure_tables($pdo);

  $sid = school_id();
  if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');

  $in = json_in();
  $username = trim((string)($in['username'] ?? ''));
  $mode = strtolower(trim((string)($in['mode'] ?? 'auto')));
  if ($username === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'username wajib']);
    exit;
  }
  if (!in_array($mode, ['auto','masuk','pulang'], true)) $mode='auto';

  // Ambil siswa
  $stmt = $pdo->prepare("SELECT username, name, kelas FROM users WHERE school_id=? AND username=? AND role='siswa' LIMIT 1");
  $stmt->execute([$sid, $username]);
  $s = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$s) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'Siswa tidak ditemukan']);
    exit;
  }

  $tanggal = date('Y-m-d');
  $waktu = date('Y-m-d H:i:s');

  $stmt = $pdo->prepare('SELECT jam_masuk, jam_pulang FROM absensi WHERE school_id=? AND tanggal=? AND username=? LIMIT 1');
  $stmt->execute([$sid, $tanggal, $username]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  $aksi = $mode;
  if ($mode === 'auto') {
    if (!$row || empty($row['jam_masuk'])) $aksi = 'masuk';
    else if (empty($row['jam_pulang'])) $aksi = 'pulang';
    else $aksi = 'selesai';
  }

  if ($aksi === 'selesai') {
    echo json_encode(['ok'=>true,'message'=>'Sudah masuk & pulang', 'voice'=>'Sudah presensi lengkap']);
    exit;
  }

  if ($aksi === 'pulang' && (!$row || empty($row['jam_masuk']))) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Belum presensi masuk']);
    exit;
  }

  if (!$row) {
    $pdo->prepare('INSERT INTO absensi (school_id, tanggal, username, nama, kelas, status_terakhir, jam_masuk) VALUES (?,?,?,?,?,?,?)')
        ->execute([$sid, $tanggal, $username, $s['name'], $s['kelas'], $aksi, $aksi==='masuk' ? date('H:i:s') : null]);
  } else {
    if ($aksi === 'masuk' && !empty($row['jam_masuk'])) {
      echo json_encode(['ok'=>true,'message'=>'Sudah presensi masuk','voice'=>'Sudah masuk']);
      exit;
    }
    if ($aksi === 'pulang' && !empty($row['jam_pulang'])) {
      echo json_encode(['ok'=>true,'message'=>'Sudah presensi pulang','voice'=>'Sudah pulang']);
      exit;
    }

    if ($aksi === 'masuk') {
      $pdo->prepare('UPDATE absensi SET jam_masuk=?, status_terakhir=? WHERE school_id=? AND tanggal=? AND username=?')
          ->execute([date('H:i:s'), $aksi, $sid, $tanggal, $username]);
    } else {
      $pdo->prepare('UPDATE absensi SET jam_pulang=?, status_terakhir=? WHERE school_id=? AND tanggal=? AND username=?')
          ->execute([date('H:i:s'), $aksi, $sid, $tanggal, $username]);
    }
  }

  $pdo->prepare('INSERT INTO absensi_log (school_id, tanggal, username, waktu, status, ket) VALUES (?,?,?,?,?,?)')
      ->execute([$sid, $tanggal, $username, $waktu, $aksi, 'kiosk']);

  $voice = $aksi==='masuk' ? 'Presensi masuk berhasil' : 'Presensi pulang berhasil';
  echo json_encode(['ok'=>true,'message'=>"{$s['name']} ({$s['kelas']}) - $aksi OK", 'voice'=>$voice]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
