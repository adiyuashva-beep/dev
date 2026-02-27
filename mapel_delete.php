<?php
// mapel_delete.php
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

$id = (int)($body["id"] ?? 0);
if ($id <= 0) bad("id tidak valid");

try {
  $sid = school_id(); // [FIX] Ambil school_id
  $stmt = $pdo->prepare("DELETE FROM mapel WHERE school_id = ? AND id = ?");
  $stmt->execute([$sid, $id]);
  echo json_encode(["ok"=>true, "deleted"=>$stmt->rowCount()]);
} catch (Throwable $e) {
  bad($e->getMessage(), 500);
}