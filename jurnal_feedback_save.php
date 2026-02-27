<?php
require __DIR__ . "/../auth/guard.php";
require_login(['siswa']);

require __DIR__ . "/../config/database.php";
require __DIR__ . "/_schema.php";
header('Content-Type: application/json; charset=utf-8');
$sid = (int)($_SESSION['school_id'] ?? 0);
if ($sid <= 0) bad('Tenant tidak valid', 400);

edugate_v5_ensure_tables($pdo);

function bad($m,$c=400){ http_response_code($c); echo json_encode(["ok"=>false,"error"=>$m]); exit; }

$user = $_SESSION['user'] ?? null;
if(!$user) bad("Belum login",401);

$raw = file_get_contents("php://input");
$body = json_decode($raw,true);
if(!is_array($body)) bad("Body JSON tidak valid");

$jurnal_id = (int)($body["jurnal_id"] ?? 0);
$emosi = trim((string)($body["emosi"] ?? ""));
$pesan = trim((string)($body["pesan"] ?? ""));

if($jurnal_id<=0) bad("jurnal_id invalid");
if($emosi==="") bad("emosi wajib");

$username = (string)($user["username"] ?? "");
$nama = (string)($user["name"] ?? $user["nama"] ?? "Siswa");
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

try{
  // pastikan jurnal milik sekolah ini
  $chk = $pdo->prepare('SELECT COUNT(*) FROM jurnal_guru WHERE school_id=:sid AND id=:id');
  $chk->execute([':sid'=>$sid, ':id'=>$jurnal_id]);
  if ((int)$chk->fetchColumn() === 0) bad('Jurnal tidak ditemukan', 404);

  $sql = "INSERT INTO feedback_kbm (school_id, jurnal_id, username, nama, emosi, pesan, tanggal, waktu)
          VALUES (:sid,:j,:u,:n,:e,:p,:t,:w)
          ON DUPLICATE KEY UPDATE emosi=VALUES(emosi), pesan=VALUES(pesan), waktu=VALUES(waktu)";
  $st=$pdo->prepare($sql);
  $st->execute([
    ":sid"=>$sid, ":j"=>$jurnal_id, ":u"=>$username, ":n"=>$nama,
    ":e"=>$emosi, ":p"=>($pesan===""? "-" : $pesan),
    ":t"=>$today, ":w"=>$now
  ]);

  echo json_encode(["ok"=>true]);
  exit;
}catch(PDOException $e){
  bad($e->getMessage(),500);
}
