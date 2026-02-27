<?php
require __DIR__ . "/../auth/guard.php";
require_login(['super','admin']);

require __DIR__ . "/../config/database.php";
require __DIR__ . "/_schema.php";

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents("php://input");
$body = json_decode($raw, true);

$username = trim((string)($body["username"] ?? ""));
if ($username === "") {
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>"username wajib"]);
  exit;
}

try{
  edugate_v5_ensure_tables($pdo);
  $sid = (int)($_SESSION['school_id'] ?? 0);
  if ($sid <= 0) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Tenant tidak valid"]); exit; }

  $stmt = $pdo->prepare("DELETE FROM users WHERE school_id=:sid AND username=:u LIMIT 1");
  $stmt->execute([":sid"=>$sid, ":u"=>$username]);
  echo json_encode(["ok"=>true]);
} catch(Throwable $e){
  http_response_code(500);
  echo json_encode(["ok"=>false,"error"=>$e->getMessage()]);
}
