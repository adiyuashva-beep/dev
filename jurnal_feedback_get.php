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

$username = (string)($user["username"] ?? "");
$jurnal_id = (int)($_GET["jurnal_id"] ?? 0);
if($jurnal_id<=0){ echo json_encode(["ok"=>false,"error"=>"jurnal_id invalid"]); exit; }

$chk = $pdo->prepare('SELECT COUNT(*) FROM jurnal_guru WHERE school_id=:sid AND id=:id');
$chk->execute([':sid'=>$sid, ':id'=>$jurnal_id]);
if ((int)$chk->fetchColumn() === 0) { echo json_encode(["ok"=>false,"error"=>"Jurnal tidak ditemukan"]); exit; }

$st = $pdo->prepare("SELECT id FROM feedback_kbm WHERE school_id=:sid AND jurnal_id=:j AND username=:u LIMIT 1");
$st->execute([":sid"=>$sid, ":j"=>$jurnal_id, ":u"=>$username]);

echo json_encode(["ok"=>true,"exists"=>($st->fetchColumn() ? true : false)]);
