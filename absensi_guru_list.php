<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['admin','super','kesiswaan','kurikulum','bk','staff']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

function normDate($s): string {
  $s = preg_replace('/[^0-9\-]/', '', (string)$s);
  return (strlen($s) === 10) ? $s : '';
}

function dayNameId(string $ymd): string {
  $n = (int)date('N', strtotime($ymd));
  return [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'][$n] ?? 'Senin';
}

function timePart(?string $v): ?string {
  if (!$v) return null;
  $v = trim($v);
  if ($v === '') return null;
  // format DATETIME => ambil HH:MM:SS
  if (strpos($v, ' ') !== false) {
    $parts = explode(' ', $v);
    return $parts[1] ?? $v;
  }
  return $v;
}

try {
  edugate_v5_ensure_tables($pdo);

  $sid = school_id();
  if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');

  $tanggal = normDate($_GET['tanggal'] ?? '');
  $start = normDate($_GET['start'] ?? '');
  $end = normDate($_GET['end'] ?? '');
  if ($tanggal !== '') {
    $start = $tanggal;
    $end = $tanggal;
  }
  if ($end === '') $end = date('Y-m-d');
  if ($start === '') $start = date('Y-m-d', strtotime('-7 days'));

  // Kalau range > 1 hari, balikin raw table (untuk export/rekap)
  if ($start !== $end) {
    $stmt = $pdo->prepare('SELECT tanggal, username, nama, jam_masuk, jam_pulang, status_terakhir, foto_masuk, foto_pulang FROM absensi_guru WHERE school_id=? AND tanggal BETWEEN ? AND ? ORDER BY tanggal DESC, nama');
    $stmt->execute([$sid, $start, $end]);
    echo json_encode(['ok'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }

  // Mode 1 hari: join dengan users, agar admin bisa lihat yang belum absen
  $hari = dayNameId($start);
  $telatTime = '07:00:00';
  try {
    $st = $pdo->prepare("SELECT telat FROM jam_operasional WHERE school_id=? AND hari=? LIMIT 1");
    $st->execute([$sid, $hari]);
    $t = $st->fetchColumn();
    if ($t) $telatTime = (strlen((string)$t) === 5) ? ((string)$t . ':00') : (string)$t;
  } catch (Throwable $e) {
    // optional
  }

  $roles = "('guru','staff','bk','kesiswaan','kurikulum')";
  $sql = "
    SELECT u.username,
           COALESCE(NULLIF(u.name,''), u.username) AS nama,
           u.role,
           a.jam_masuk, a.jam_pulang, a.status_terakhir,
           a.foto_masuk, a.foto_pulang
    FROM users u
    LEFT JOIN absensi_guru a
      ON a.school_id = :sid AND a.username = u.username AND a.tanggal = :tgl
    WHERE u.school_id = :sid AND u.role IN {$roles}
    ORDER BY nama
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':sid'=>$sid, ':tgl'=>$start]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  $out = [];
  foreach ($rows as $r) {
    $jm = $r['jam_masuk'] ?? null;
    $jp = $r['jam_pulang'] ?? null;
    $status = $r['status_terakhir'] ?? null;
    if (!$status) {
      $status = $jm ? 'masuk' : 'Belum Absen';
    }
    $jmTime = timePart($jm);
    $telat = 0;
    if ($jmTime) {
      $telat = (strtotime($jmTime) > strtotime($telatTime)) ? 1 : 0;
    }
    $out[] = [
      'tanggal' => $start,
      'nama' => $r['nama'] ?? '-',
      'username' => $r['username'] ?? '-',
      'role' => $r['role'] ?? '-',
      'jam_masuk' => $jm ?? null,
      'jam_pulang' => $jp ?? null,
      'status' => $status,
      'telat' => $telat,
      'foto_masuk' => $r['foto_masuk'] ?? null,
      'foto_pulang' => $r['foto_pulang'] ?? null,
    ];
  }

  echo json_encode(['ok'=>true,'data'=>$out]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
