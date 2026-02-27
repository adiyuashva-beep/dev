<?php
require __DIR__ . "/../auth/guard.php";
require_login(['siswa']);

require __DIR__ . "/../config/database.php";
require __DIR__ . "/_schema.php";

header('Content-Type: application/json; charset=utf-8');

function bad(string $msg, int $code=400): void {
  http_response_code($code);
  echo json_encode(["ok"=>false,"error"=>$msg], JSON_UNESCAPED_UNICODE);
  exit;
}

function indo_hari(int $n): string {
  $map = [1=>"Senin",2=>"Selasa",3=>"Rabu",4=>"Kamis",5=>"Jumat",6=>"Sabtu",7=>"Minggu"]; 
  return $map[$n] ?? 'Senin';
}

function haversineMeters(float $lat1,float $lon1,float $lat2,float $lon2): float {
  $R = 6371000;
  $phi1 = deg2rad($lat1); $phi2 = deg2rad($lat2);
  $dphi = deg2rad($lat2-$lat1);
  $dl = deg2rad($lon2-$lon1);
  $a = sin($dphi/2)**2 + cos($phi1)*cos($phi2)*sin($dl/2)**2;
  $c = 2*atan2(sqrt($a), sqrt(1-$a));
  return $R*$c;
}

function saveDataUrlJpg(string $dataUrl, string $dir, string $filenameBase): ?string {
  if($dataUrl === "" || $dataUrl === "-") return null;
  if(!preg_match('#^data:image/(png|jpeg|jpg);base64,#', $dataUrl)) return null;

  $parts = explode(',', $dataUrl, 2);
  if(count($parts) !== 2) return null;

  $bin = base64_decode($parts[1], true);
  if($bin === false) return null;

  if(!is_dir($dir)) @mkdir($dir, 0775, true);

  $path = rtrim($dir,'/') . "/" . $filenameBase . ".jpg";
  file_put_contents($path, $bin);

  return $path;
}

$user = $_SESSION['user'] ?? null;
if(!$user) bad("Belum login", 401);

// Pastikan schema (aman dipanggil berulang)
edugate_v5_ensure_tables($pdo);

// Tenant
$sid = (int)school_id();
if ($sid <= 0) bad('Tenant tidak valid', 400);

$raw = file_get_contents("php://input");
$body = json_decode($raw, true);
if(!is_array($body)) bad("Body JSON tidak valid");

$action = trim((string)($body["action"] ?? ""));
$ket = trim((string)($body["ket"] ?? ""));
$foto = (string)($body["foto"] ?? "-");
$lat = isset($body["lat"]) ? (float)$body["lat"] : null;
$lng = isset($body["lng"]) ? (float)$body["lng"] : null;

$allowed = ["Masuk","Pulang","Izin Keluar","Kembali","Sakit","Izin"];
if(!in_array($action, $allowed, true)) bad("action tidak valid");

$username = (string)($user["username"] ?? "");
$nama = (string)($user["name"] ?? $user["nama"] ?? "Siswa");
$kelas = (string)($user["kelas"] ?? null);

$today = date('Y-m-d');
$nowDT = date('Y-m-d H:i:s');

// Ambil setting sekolah (pengaturan_sekolah + jam_operasional)
$pdo->prepare("INSERT IGNORE INTO pengaturan_sekolah (school_id) VALUES (?)")->execute([$sid]);
$st = $pdo->prepare("SELECT mode_gps, radius_m, lokasi_lat, lokasi_lng FROM pengaturan_sekolah WHERE school_id=? LIMIT 1");
$st->execute([$sid]);
$cfg = $st->fetch(PDO::FETCH_ASSOC) ?: [];

$mode_gps = (int)($cfg['mode_gps'] ?? 1);
$SEK_LAT = (float)($cfg['lokasi_lat'] ?? -7.6739830);
$SEK_LNG = (float)($cfg['lokasi_lng'] ?? 109.6319560);
$RADIUS = (int)($cfg['radius_m'] ?? 50);

$hari = indo_hari((int)date('N'));
$OPEN = '05:55';
$is_libur = 0;
$st = $pdo->prepare("SELECT masuk, is_libur FROM jam_operasional WHERE school_id=? AND hari=? LIMIT 1");
$st->execute([$sid, $hari]);
if ($r = $st->fetch(PDO::FETCH_ASSOC)) {
  $is_libur = (int)($r['is_libur'] ?? 0);
  if (!empty($r['masuk'])) $OPEN = substr((string)$r['masuk'], 0, 5);
}

if ($is_libur === 1 && $action === 'Masuk') {
  bad("Hari ini ditandai libur. Presensi masuk ditutup.");
}

if($action === "Masuk"){
  $cur = (int)date('H')*60 + (int)date('i');
  [$oh,$om] = array_map('intval', explode(':', $OPEN));
  $openMin = $oh*60 + $om;
  if($cur < $openMin) bad("Presensi masuk baru dibuka pukul $OPEN");
}

$needGps = in_array($action, ["Masuk","Pulang","Kembali"], true);
if($needGps && $mode_gps === 1){
  if($lat===null || $lng===null) bad("GPS belum tersedia");
  $dist = haversineMeters((float)$lat,(float)$lng,$SEK_LAT,$SEK_LNG);
  if($dist > $RADIUS) bad("Di luar radius sekolah (".round($dist)."m > {$RADIUS}m)");
}

$st = $pdo->prepare("SELECT * FROM absensi WHERE school_id = :sid AND tanggal=:t AND username=:u LIMIT 1");
$st->execute([":sid"=>$sid, ":t"=>$today, ":u"=>$username]);
$absen = $st->fetch(PDO::FETCH_ASSOC);

if($absen && strpos((string)($absen["status_terakhir"] ?? ''), "Pulang") !== false){
  bad("Hari ini sudah Pulang. Tidak bisa input lagi.");
}

if(($action === "Izin Keluar" || $action === "Kembali" || $action === "Pulang") && (!$absen || empty($absen["jam_masuk"]))){
  bad("Belum absen masuk hari ini.");
}

if($action === "Kembali" && $absen && (($absen["status_terakhir"] ?? '') !== "Izin Keluar")){
  bad("Status tidak valid untuk Kembali.");
}

$fotoPath = null;
if($foto !== "-" && $foto !== ""){
  // Simpan per sekolah agar multi-tenant rapi
  $dir = __DIR__ . "/../uploads/{$sid}/absen/{$today}";
  $base = $username . "_" . preg_replace('/\s+/', '_', strtolower($action)) . "_" . time();
  $saved = saveDataUrlJpg($foto, $dir, $base);
  if($saved) {
    $fotoPath = $saved;
  }
}

$publicFotoUrl = null;
if($fotoPath){
  $publicBase = (strpos($_SERVER['REQUEST_URI'] ?? '', '/app/') !== false) ? '/app/uploads' : '/uploads';
  $publicFotoUrl = $publicBase . "/{$sid}/absen/{$today}/" . basename($fotoPath);
}

try{
  $pdo->beginTransaction();

  if(!$absen){
    $ins = $pdo->prepare("INSERT INTO absensi (school_id, tanggal, username, nama, kelas, status_terakhir) VALUES (:sid, :t, :u, :n, :k, 'BELUM ABSEN')");
    $ins->execute([":sid"=>$sid, ":t"=>$today, ":u"=>$username, ":n"=>$nama, ":k"=>$kelas]);
  }

  $statusTerakhir = $action;

  if($action === "Masuk"){
    $jam_masuk = $nowDT;
    $foto_masuk = $publicFotoUrl;
    $lokasi_masuk = ($lat !== null && $lng !== null) ? ($lat . "," . $lng) : null;

    $upd = $pdo->prepare("UPDATE absensi SET status_terakhir=:s, jam_masuk=:jm, foto_masuk=:fm, lokasi_masuk=:lm WHERE school_id=:sid AND tanggal=:t AND username=:u");
    $upd->execute([":s"=>$statusTerakhir, ":jm"=>$jam_masuk, ":fm"=>$foto_masuk, ":lm"=>$lokasi_masuk, ":sid"=>$sid, ":t"=>$today, ":u"=>$username]);
  }
  else if($action === "Pulang"){
    $jam_pulang = $nowDT;
    $foto_pulang = $publicFotoUrl;

    $upd = $pdo->prepare("UPDATE absensi SET status_terakhir=:s, jam_pulang=:jp, foto_pulang=:fp WHERE school_id=:sid AND tanggal=:t AND username=:u");
    $upd->execute([":s"=>"Pulang", ":jp"=>$jam_pulang, ":fp"=>$foto_pulang, ":sid"=>$sid, ":t"=>$today, ":u"=>$username]);
  }
  else{
    $upd = $pdo->prepare("UPDATE absensi SET status_terakhir=:s WHERE school_id=:sid AND tanggal=:t AND username=:u");
    $upd->execute([":s"=>$statusTerakhir, ":sid"=>$sid, ":t"=>$today, ":u"=>$username]);
  }

  $log = $pdo->prepare("INSERT INTO absensi_log (school_id, tanggal, username, waktu, status, ket, foto) VALUES (:sid, :t, :u, :w, :s, :k, :f)");
  $log->execute([
    ":sid"=>$sid,
    ":t"=>$today,
    ":u"=>$username,
    ":w"=>$nowDT,
    ":s"=>$statusTerakhir,
    ":k"=>($ket==="" ? "-" : $ket),
    ":f"=>$publicFotoUrl
  ]);

  $pdo->commit();

  $st2 = $pdo->prepare("SELECT status_terakhir, jam_masuk, jam_pulang FROM absensi WHERE school_id=:sid AND tanggal=:t AND username=:u LIMIT 1");
  $st2->execute([":sid"=>$sid, ":t"=>$today, ":u"=>$username]);
  $row = $st2->fetch(PDO::FETCH_ASSOC);

  echo json_encode(["ok"=>true,"data"=>$row], JSON_UNESCAPED_UNICODE);
  exit;

} catch(PDOException $e){
  if($pdo->inTransaction()) $pdo->rollBack();
  bad($e->getMessage(), 500);
}
