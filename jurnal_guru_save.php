<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['guru','staff','bk','kesiswaan','kurikulum','admin','super']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json; charset=utf-8');

function json_in(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '[]', true);
  return is_array($data) ? $data : [];
}

function save_dataurl_jpg(?string $dataUrl, string $dirFs, string $filenameBase): ?string {
  if (!$dataUrl) return null;
  $dataUrl = trim($dataUrl);
  if ($dataUrl === '' || $dataUrl === '-') return null;
  if (!preg_match('#^data:image/(png|jpeg|jpg);base64,#', $dataUrl)) return null;

  $parts = explode(',', $dataUrl, 2);
  if (count($parts) !== 2) return null;

  // limit: base64 ~6MB, binary ~4MB
  if (strlen($parts[1]) > 6_000_000) return null;
  $bin = base64_decode($parts[1], true);
  if ($bin === false) return null;
  if (strlen($bin) > 4_000_000) return null;

  if (!is_dir($dirFs)) @mkdir($dirFs, 0775, true);

  $pathFs = rtrim($dirFs, '/').'/'.$filenameBase.'.jpg';
  $ok = @file_put_contents($pathFs, $bin);
  if ($ok === false) return null;
  return $pathFs;
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
  $guru_username = (string)($u['username'] ?? '');
  $guru_nama = (string)($u['name'] ?? $guru_username);

  $in = json_in();

  $kelas = trim((string)($in['kelas'] ?? ''));
  $mapel = trim((string)($in['mapel'] ?? ''));
  $topik = trim((string)($in['topik'] ?? ($in['materi'] ?? '')));
  $catatan = trim((string)($in['catatan'] ?? ''));

  $jp_mulai = (int)($in['jam_ke_mulai'] ?? 0);
  $jp_selesai = (int)($in['jam_ke_selesai'] ?? 0);

  if ($kelas === '' || $mapel === '' || $topik === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'kelas, mapel, materi/topik wajib']);
    exit;
  }

  // validasi JP
  if (($jp_mulai > 0 && $jp_selesai <= 0) || ($jp_mulai <= 0 && $jp_selesai > 0)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Jam pelajaran (mulai & selesai) harus diisi lengkap (atau kosongkan keduanya).']);
    exit;
  }
  if ($jp_mulai > 0 && $jp_selesai > 0 && $jp_selesai < $jp_mulai) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Jam pelajaran selesai tidak boleh lebih kecil dari mulai.']);
    exit;
  }

  // foto jurnal optional (max 2)
  $fotos = $in['fotos'] ?? [];
  if (!is_array($fotos)) $fotos = [];
  $fotos = array_values(array_filter($fotos, fn($x)=>is_string($x) && trim($x) !== ''));
  $fotos = array_slice($fotos, 0, 2);

  $tanggal = date('Y-m-d');

  $urls = [];
  if (!empty($fotos)) {
    $dirFs = __DIR__ . '/../uploads/' . $sid . '/jurnal_guru/' . $tanggal;
    foreach ($fotos as $i => $dataUrl) {
      $suffix = substr(str_replace(['+','/','='], '', base64_encode(random_bytes(6))), 0, 8);
      $base = $guru_username . '_jurnal_' . time() . '_' . ($i+1) . '_' . $suffix;
      $savedFs = save_dataurl_jpg($dataUrl, $dirFs, $base);
      if ($savedFs) {
        $urls[] = '/uploads/' . $sid . '/jurnal_guru/' . $tanggal . '/' . basename($savedFs);
      }
    }
  }
  $foto_json = !empty($urls) ? json_encode($urls, JSON_UNESCAPED_SLASHES) : null;

  $stmt = $pdo->prepare('
    INSERT INTO jurnal_guru
      (school_id, tanggal, jam, jam_ke_mulai, jam_ke_selesai, guru_username, guru_nama, kelas, mapel, topik, catatan, foto_json)
    VALUES
      (?,?,?,?,?,?,?,?,?,?,?,?)
  ');
  $stmt->execute([
    $sid,
    $tanggal,
    null,
    ($jp_mulai > 0 ? $jp_mulai : null),
    ($jp_selesai > 0 ? $jp_selesai : null),
    $guru_username,
    $guru_nama,
    $kelas,
    $mapel,
    $topik,
    ($catatan !== '' ? $catatan : null),
    $foto_json
  ]);

  echo json_encode(['ok'=>true,'message'=>'Jurnal tersimpan']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
