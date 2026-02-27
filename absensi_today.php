<?php
require __DIR__ . "/../auth/guard.php";
require_login(['siswa']);

require __DIR__ . "/../config/database.php";
header('Content-Type: application/json; charset=utf-8');

$user = $_SESSION['user'] ?? null;
if(!$user) { echo json_encode(["ok"=>false,"error"=>"Belum login"]); exit; }

$username = $user['username'] ?? '';
$today = date('Y-m-d');
$sid = school_id(); // [FIX] Ambil school_id dari session

$st = $pdo->prepare("SELECT tanggal, status_terakhir, jam_masuk, jam_pulang FROM absensi WHERE school_id = :sid AND tanggal = :t AND username = :u LIMIT 1");
$st->execute([":sid"=>$sid, ":t"=>$today, ":u"=>$username]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if(!$row){
  echo json_encode(["ok"=>true, "data"=>null]);
  exit;
}

echo json_encode([
  "ok"=>true,
  "data"=>[
    "tanggal"=>$row["tanggal"],
    "status_terakhir"=>$row["status_terakhir"],
    "jam_masuk"=>$row["jam_masuk"],
    "jam_pulang"=>$row["jam_pulang"]
  ]
]);