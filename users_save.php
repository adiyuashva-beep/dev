<?php
require __DIR__ . "/../auth/guard.php";
require_login(['super','admin']); // sementara yang boleh edit data user

require __DIR__ . "/../config/database.php";
require __DIR__ . "/_schema.php";

header('Content-Type: application/json; charset=utf-8');

function bad($msg, $code=400){
  http_response_code($code);
  echo json_encode(["ok"=>false,"error"=>$msg]);
  exit;
}

$raw = file_get_contents("php://input");
$body = json_decode($raw, true);
if (!is_array($body)) bad("Body JSON tidak valid");

$mode = $body["mode"] ?? "create"; // create|update
$username = trim((string)($body["username"] ?? ""));
$nama = trim((string)($body["nama"] ?? ""));
$role = trim((string)($body["role"] ?? ""));
$kelas = isset($body["kelas"]) ? trim((string)$body["kelas"]) : null;

$password = isset($body["password"]) ? (string)$body["password"] : "";

if ($username==="" || $nama==="" || $role==="") bad("username/nama/role wajib");
if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) bad("username harus 3-30 char (huruf/angka/._-)");

$allowed_roles = ['super','admin','guru','siswa','bk','kurikulum','staff','kesiswaan'];
if (!in_array($role, $allowed_roles, true)) bad("role tidak valid");

try {
  edugate_v5_ensure_tables($pdo);
  $sid = (int)($_SESSION['school_id'] ?? 0);
  if ($sid <= 0) bad('Tenant tidak valid', 400);

  if ($mode === "create") {
    // default password: kalau kosong pakai username (misal NISN/NIP)
    if ($password === "") $password = $username;
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO users (school_id, name, username, password_hash, role, kelas)
            VALUES (:sid,:name,:username,:hash,:role,:kelas)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ":sid"=>$sid,
      ":name"=>$nama,
      ":username"=>$username,
      ":hash"=>$hash,
      ":role"=>$role,
      ":kelas"=>$kelas
    ]);

    echo json_encode(["ok"=>true,"mode"=>"create"]);
    exit;
  }

  if ($mode === "update") {
    // update tanpa ganti password kalau password kosong
    if ($password !== "") {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $sql = "UPDATE users SET name=:name, role=:role, kelas=:kelas, password_hash=:hash WHERE school_id=:sid AND username=:username";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ":name"=>$nama, ":role"=>$role, ":kelas"=>$kelas, ":hash"=>$hash, ":sid"=>$sid, ":username"=>$username
      ]);
    } else {
      $sql = "UPDATE users SET name=:name, role=:role, kelas=:kelas WHERE school_id=:sid AND username=:username";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ":name"=>$nama, ":role"=>$role, ":kelas"=>$kelas, ":sid"=>$sid, ":username"=>$username
      ]);
    }

    echo json_encode(["ok"=>true,"mode"=>"update"]);
    exit;
  }

  bad("mode harus create/update");
} catch (PDOException $e) {
  // duplicate username
  if ((int)($e->errorInfo[1] ?? 0) === 1062) bad("username sudah ada", 409);
  bad($e->getMessage(), 500);
}
