<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['guru','staff','bk','kesiswaan','kurikulum','admin','super']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json; charset=utf-8');

function clean_name(string $s): string {
  $s = preg_replace('/[^a-zA-Z0-9_\-\.]+/', '_', $s);
  $s = trim($s, '._-');
  return $s !== '' ? $s : 'file';
}

function allowed_kind(string $jenis): bool {
  $allowed = [
    'izin',
    'sakit',
    'dinas_dalam_kota',
    'dinas_luar_kota',
    'cuti_tahunan',
    'cuti_sakit',
    'cuti_alasan_penting',
    'cuti_besar',
    'cuti_melahirkan',
    'lainnya'
  ];
  return in_array($jenis, $allowed, true);
}

try {
  edugate_v5_ensure_tables($pdo);

  $sid = school_id();
  if ($sid <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Tenant tidak valid']);
    exit;
  }

  $u = $_SESSION['user'] ?? [];
  $username = (string)($u['username'] ?? '');
  $nama = (string)($u['name'] ?? $username);

  $tanggal = date('Y-m-d');
  $tahun = (int)date('Y');

  $jenis = trim((string)($_POST['jenis'] ?? ''));
  $keterangan = trim((string)($_POST['keterangan'] ?? ''));
  $jumlah_hari = (int)($_POST['jumlah_hari'] ?? 0);

  if ($jenis === '' || !allowed_kind($jenis)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Jenis keterangan tidak valid']);
    exit;
  }

  // Cegah submit ket kalau guru sudah absen masuk/pulang hari ini
  $st = $pdo->prepare("SELECT jam_masuk, jam_pulang FROM absensi_guru WHERE school_id=? AND tanggal=? AND username=? LIMIT 1");
  $st->execute([$sid, $tanggal, $username]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  if ($r && (!empty($r['jam_masuk']) || !empty($r['jam_pulang']))) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Tidak bisa ajukan izin/dinas/cuti/sakit karena sudah presensi masuk/pulang hari ini. Hubungi admin jika perlu koreksi.']);
    exit;
  }

  $isCuti = str_starts_with($jenis, 'cuti_');
  if ($isCuti) {
    if ($jumlah_hari <= 0) $jumlah_hari = 1;
  } else {
    $jumlah_hari = null;
  }

  // Handle bukti upload (image/pdf) optional
  $bukti_url = null;
  if (isset($_FILES['bukti']) && is_array($_FILES['bukti']) && ($_FILES['bukti']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $tmp = (string)$_FILES['bukti']['tmp_name'];
    $orig = (string)($_FILES['bukti']['name'] ?? 'bukti');
    $size = (int)($_FILES['bukti']['size'] ?? 0);

    // limit 6MB
    if ($size > 6_000_000) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'error'=>'File bukti terlalu besar (maks 6MB).']);
      exit;
    }

    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $allowExt = ['jpg','jpeg','png','pdf'];
    if (!in_array($ext, $allowExt, true)) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'error'=>'Format bukti harus jpg/png/pdf']);
      exit;
    }

    $dirFs = __DIR__ . '/../uploads/' . $sid . '/bukti_guru/' . $tanggal;
    if (!is_dir($dirFs)) @mkdir($dirFs, 0775, true);

    $base = clean_name($username . '_' . $jenis . '_' . time());
    $fname = $base . '.' . $ext;

    $destFs = rtrim($dirFs,'/') . '/' . $fname;
    if (!@move_uploaded_file($tmp, $destFs)) {
      http_response_code(500);
      echo json_encode(['ok'=>false,'error'=>'Gagal menyimpan file bukti. Pastikan folder uploads writable.']);
      exit;
    }

    $bukti_url = '/uploads/' . $sid . '/bukti_guru/' . $tanggal . '/' . $fname;
  }

  // Ensure saldo cuti tahunan default 12
  $st = $pdo->prepare("INSERT IGNORE INTO guru_cuti_saldo (school_id, tahun, username, sisa_hari) VALUES (?,?,?,12)");
  $st->execute([$sid, $tahun, $username]);

  // Jika cuti tahunan, kurangi saldo
  if ($jenis === 'cuti_tahunan') {
    $st = $pdo->prepare("SELECT sisa_hari FROM guru_cuti_saldo WHERE school_id=? AND tahun=? AND username=? LIMIT 1");
    $st->execute([$sid, $tahun, $username]);
    $sisa = (int)($st->fetchColumn() ?? 12);

    $butuh = (int)($jumlah_hari ?? 1);
    if ($butuh <= 0) $butuh = 1;

    if ($sisa < $butuh) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'error'=>"Sisa cuti tahunan tidak cukup. Sisa: {$sisa} hari."]);
      exit;
    }

    $st = $pdo->prepare("UPDATE guru_cuti_saldo SET sisa_hari = sisa_hari - ? WHERE school_id=? AND tahun=? AND username=?");
    $st->execute([$butuh, $sid, $tahun, $username]);
  }

  // Insert/Update ket (1 record per hari)
  $st = $pdo->prepare("
    INSERT INTO absensi_guru_ket (school_id, tanggal, username, nama, jenis, jumlah_hari, keterangan, bukti_url, status)
    VALUES (?,?,?,?,?,?,?,?,'submitted')
    ON DUPLICATE KEY UPDATE
      jenis=VALUES(jenis),
      jumlah_hari=VALUES(jumlah_hari),
      keterangan=VALUES(keterangan),
      bukti_url=COALESCE(VALUES(bukti_url), bukti_url),
      status='submitted',
      updated_at=CURRENT_TIMESTAMP
  ");
  $st->execute([$sid, $tanggal, $username, $nama, $jenis, $jumlah_hari, ($keterangan!==''?$keterangan:null), $bukti_url]);

  // Update absensi_guru status_terakhir (grouped agar kompatibel)
  $status_terakhir = 'izin';
  if ($jenis === 'sakit') $status_terakhir = 'sakit';
  elseif (str_starts_with($jenis, 'dinas_')) $status_terakhir = 'dinas';
  elseif (str_starts_with($jenis, 'cuti_')) $status_terakhir = 'cuti';
  elseif ($jenis === 'lainnya') $status_terakhir = 'izin';

  $st = $pdo->prepare("
    INSERT INTO absensi_guru (school_id, tanggal, username, nama, status_terakhir)
    VALUES (?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
      status_terakhir=VALUES(status_terakhir),
      updated_at=CURRENT_TIMESTAMP
  ");
  $st->execute([$sid, $tanggal, $username, $nama, $status_terakhir]);

  echo json_encode(['ok'=>true,'message'=>'Keterangan tersimpan', 'bukti_url'=>$bukti_url]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}