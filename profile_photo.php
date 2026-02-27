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

$dataUrl = (string)($body["image"] ?? "");
if(!preg_match('#^data:image/(png|jpeg|jpg);base64,#', $dataUrl)) bad("Format gambar tidak valid");

$parts = explode(',', $dataUrl, 2);
$bin = base64_decode($parts[1], true);
if($bin === false) bad("decode gagal");

// [FIX] Batasi ukuran file (maks 2MB setelah decode)
if (strlen($bin) > 2_000_000) bad("Ukuran file terlalu besar (maks 2MB)");

$username = (string)($user["username"] ?? "");
$dir = __DIR__ . "/../uploads/profil";
if(!is_dir($dir)) mkdir($dir, 0775, true);

$fn = $username . "_" . time() . ".jpg";
$path = $dir . "/" . $fn;
file_put_contents($path, $bin);

$url = (strpos($_SERVER['REQUEST_URI'], '/app/') !== false) ? "/app/uploads/profil/$fn" : "/uploads/profil/$fn";

// [FIX] Tambahkan school_id pada WHERE
$st = $pdo->prepare("UPDATE users SET foto_profil=:f WHERE school_id=:sid AND username=:u");
$st->execute([":f"=>$url, ":sid"=>$sid, ":u"=>$username]);

$_SESSION['user']['foto_profil'] = $url;

echo json_encode(["ok"=>true,"url"=>$url]);