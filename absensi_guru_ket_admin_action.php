<?php
require __DIR__ . '/_bootstrap.php';

require_login(['admin','super','kesiswaan','kurikulum','bk','staff']);
ensure_schema();

$sid = require_tenant();
$in = json_in();

$id = (int)($in['id'] ?? 0);
$action = trim((string)($in['action'] ?? '')); // approve|reject
$note = trim((string)($in['note'] ?? ''));

if ($id <= 0) json_out(['ok'=>false,'error'=>'id wajib'], 400);
if (!in_array($action, ['approve','reject'], true)) json_out(['ok'=>false,'error'=>'action harus approve|reject'], 400);

$u = $_SESSION['user'] ?? [];
$validator_username = (string)($u['username'] ?? '');
$validator_name = (string)($u['name'] ?? $validator_username);

$now = date('Y-m-d H:i:s');
$newStatus = $action === 'approve' ? 'approved' : 'rejected';

// ambil data ket
$st = $pdo->prepare('SELECT id, tanggal, username, nama, jenis, jumlah_hari, status FROM absensi_guru_ket WHERE school_id=? AND id=? LIMIT 1');
$st->execute([$sid, $id]);
$ket = $st->fetch();
if (!$ket) json_out(['ok'=>false,'error'=>'Pengajuan tidak ditemukan'], 404);

$pdo->beginTransaction();
try {
  $up = $pdo->prepare('UPDATE absensi_guru_ket
    SET status=?, validator_username=?, validator_name=?, validator_note=?, validated_at=?
    WHERE school_id=? AND id=?');
  $up->execute([$newStatus, $validator_username, $validator_name, ($note!==''?$note:null), $now, $sid, $id]);

  // Update status_terakhir di absensi_guru
  $tgl = $ket['tanggal'];
  $username = $ket['username'];
  $nama = $ket['nama'];
  $jenis = $ket['jenis'];

  if ($newStatus === 'approved') {
    $status_terakhir = 'izin';
    if ($jenis === 'sakit') $status_terakhir = 'sakit';
    elseif (str_starts_with($jenis, 'dinas_')) $status_terakhir = 'dinas';
    elseif (str_starts_with($jenis, 'cuti_')) $status_terakhir = 'cuti';

    $st2 = $pdo->prepare('INSERT INTO absensi_guru (school_id, tanggal, username, nama, status_terakhir)
      VALUES (?,?,?,?,?)
      ON DUPLICATE KEY UPDATE status_terakhir=VALUES(status_terakhir), updated_at=CURRENT_TIMESTAMP');
    $st2->execute([$sid, $tgl, $username, $nama, $status_terakhir]);
  } else {
    // reject: kalau sebelumnya sudah diset status_terakhir, jangan ubah jam masuk/pulang.
    // kita set status_terakhir NULL hanya jika record belum punya jam masuk/pulang.
    $st3 = $pdo->prepare('SELECT jam_masuk, jam_pulang FROM absensi_guru WHERE school_id=? AND tanggal=? AND username=? LIMIT 1');
    $st3->execute([$sid, $tgl, $username]);
    $r = $st3->fetch();
    if (!$r || (empty($r['jam_masuk']) && empty($r['jam_pulang']))) {
      $pdo->prepare('UPDATE absensi_guru SET status_terakhir=NULL WHERE school_id=? AND tanggal=? AND username=?')->execute([$sid, $tgl, $username]);
    }
  }

  $pdo->commit();
  json_out(['ok'=>true,'message'=>'Status diperbarui','data'=>['status'=>$newStatus]]);
} catch (Throwable $e) {
  $pdo->rollBack();
  json_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}
