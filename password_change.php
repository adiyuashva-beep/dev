<?php
require __DIR__ . "/../auth/guard.php";
require_login(['siswa']);

require __DIR__ . "/../config/database.php";
header('Content-Type: application/json; charset=utf-8');

function bad($m,$c=400){ http_response_code($c); echo json_encode(["ok"=>false,"error"=>$m]); exit; }

$user = $_SESSION['user'] ?? null;
if(!$user) bad("Belum login",401);

// [FIX] Ambil school_id
$sid = school_id();

$raw = file_get_contents("php://input");
$body = json_decode($raw,true);
if(!is_array($body)) bad("Body JSON tidak valid");

$newpass = (string)($body["new_password"] ?? "");
if(strlen($newpass) < 6) bad("Minimal 6 karakter");

$username = (string)($user["username"] ?? "");
$hash = password_hash($newpass, PASSWORD_BCRYPT);

// [FIX] Tambahkan school_id pada WHERE
$st = $pdo->prepare("UPDATE users SET password_hash=:h WHERE school_id=:sid AND username=:u");
$st->execute([":h"=>$hash, ":sid"=>$sid, ":u"=>$username]);

echo json_encode(["ok"=>true]);