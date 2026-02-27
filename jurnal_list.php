<?php
require __DIR__ . "/../auth/guard.php";
require_login(['siswa']);

require __DIR__ . "/../config/database.php";
require __DIR__ . "/_schema.php";
header('Content-Type: application/json; charset=utf-8');
$sid = (int)($_SESSION['school_id'] ?? 0);
if ($sid <= 0) { echo json_encode(["ok"=>false,"error"=>"Tenant tidak valid"]); exit; }

edugate_v5_ensure_tables($pdo);

$user = $_SESSION['user'] ?? null;
if(!$user){ echo json_encode(["ok"=>false,"error"=>"Belum login"]); exit; }

$kelas = (string)($user["kelas"] ?? "");
$today = date('Y-m-d');

$st = $pdo->prepare("SELECT id, mapel, guru_nama AS nama_guru, topik AS materi, created_at AS waktu, jam_ke_mulai, jam_ke_selesai
  FROM jurnal_guru
  WHERE school_id=:sid AND kelas=:k AND tanggal=:t
  ORDER BY created_at DESC, id DESC");
$st->execute([":sid"=>$sid, ":k"=>$kelas, ":t"=>$today]);

echo json_encode(["ok"=>true,"data"=>$st->fetchAll(PDO::FETCH_ASSOC)]);
