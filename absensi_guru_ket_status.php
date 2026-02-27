<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['guru','staff','bk','kesiswaan','kurikulum','admin','super']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json; charset=utf-8');

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

  // ensure saldo cuti tahunan ada
  $st = $pdo->prepare("INSERT IGNORE INTO guru_cuti_saldo (school_id, tahun, username, sisa_hari) VALUES (?,?,?,12)");
  $st->execute([$sid, $tahun, $username]);

  $st = $pdo->prepare("SELECT sisa_hari FROM guru_cuti_saldo WHERE school_id=? AND tahun=? AND username=? LIMIT 1");
  $st->execute([$sid, $tahun, $username]);
  $cuti_sisa = (int)($st->fetchColumn() ?? 12);

  $st = $pdo->prepare("SELECT id, tanggal, jenis, jumlah_hari, keterangan, bukti_url, status, validator_name, validator_note, validated_at FROM absensi_guru_ket WHERE school_id=? AND tanggal=? AND username=? LIMIT 1");
  $st->execute([$sid, $tanggal, $username]);
  $ket = $st->fetch(PDO::FETCH_ASSOC) ?: null;

  echo json_encode([
    'ok'=>true,
    'data'=>[
      'today'=>$ket,
      'cuti_sisa'=>$cuti_sisa,
      'user'=>['username'=>$username,'name'=>$nama]
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}