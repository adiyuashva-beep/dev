<?php
// mapel_save.php
require __DIR__ . "/../auth/guard.php";
require_login(['super','admin']);

require __DIR__ . "/../config/database.php";
header('Content-Type: application/json; charset=utf-8');

function bad($msg, $code=400){
  http_response_code($code);
  echo json_encode(["ok"=>false,"error"=>$msg]);
  exit;
}

$raw = file_get_contents("php://input");
$body = json_decode($raw, true);
if (!is_array($body)) bad("Body JSON tidak valid");

$nama = trim((string)($body["nama_mapel"] ?? ""));
if ($nama === "") bad("nama_mapel wajib diisi");
if (mb_strlen($nama) > 100) bad("nama_mapel maksimal 100 karakter");

try {
  $sid = school_id(); // [FIX] Ambil school_id
  $stmt = $pdo->prepare("INSERT INTO mapel (school_id, nama_mapel) VALUES (?, ?)");
  $stmt->execute([$sid, $nama]);
  echo json_encode(["ok"=>true, "id"=>$pdo->lastInsertId()]);
} catch (PDOException $e) {
  if ((int)($e->errorInfo[1] ?? 0) === 1062) bad("Mapel sudah ada", 409);
  bad($e->getMessage(), 500);
}