<?php
require __DIR__ . "/../auth/guard.php";
require_login(['super','admin']); // yang boleh import massal

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

$type = strtolower(trim((string)($body["type"] ?? ""))); // siswa | guru
$rows = $body["rows"] ?? null;

if (!in_array($type, ["siswa","guru"], true)) bad("type harus siswa/guru");
if (!is_array($rows) || count($rows) === 0) bad("rows kosong");

$allowed_roles = ['super','admin','guru','siswa','bk','kurikulum','staff','kesiswaan'];

function norm($s){
  $s = trim((string)$s);
  $s = preg_replace('/\s+/', ' ', $s);
  return $s;
}

function guessRoleGuru($s){
  $t = strtolower(norm($s));
  if ($t === "") return "guru";
  // fleksibel: "Guru", "Guru Mapel", "GTK", "Tenaga Pendidik"
  if (strpos($t, "guru") !== false || strpos($t, "pendidik") !== false || strpos($t, "gtk") !== false) return "guru";
  if (strpos($t, "bk") !== false) return "bk";
  if (strpos($t, "kurikulum") !== false) return "kurikulum";
  if (strpos($t, "kesiswaan") !== false) return "kesiswaan";
  if (strpos($t, "admin") !== false) return "admin";
  // default selain guru: staff
  return "staff";
}

function validUsername($u){
  // username: NISN / NIP biasanya angka, tapi biar aman huruf/angka/._- juga boleh
  return (bool)preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $u);
}

try {
  edugate_v5_ensure_tables($pdo);
  $sid = (int)($_SESSION['school_id'] ?? 0);
  if ($sid <= 0) bad('Tenant tidak valid', 400);

  $pdo->beginTransaction();

  $created = 0;
  $updated = 0;
  $skipped = 0;

  // prepared statements
  $stmtFind = $pdo->prepare("SELECT id, username FROM users WHERE school_id=:sid AND username=:username LIMIT 1");
  $stmtInsert = $pdo->prepare("
    INSERT INTO users (school_id, name, username, password_hash, role, kelas)
    VALUES (:sid,:name,:username,:hash,:role,:kelas)
  ");
  $stmtUpdateNoPass = $pdo->prepare("
    UPDATE users SET name=:name, role=:role, kelas=:kelas
    WHERE school_id=:sid AND username=:username
  ");
  $stmtUpdateWithPass = $pdo->prepare("
    UPDATE users SET name=:name, role=:role, kelas=:kelas, password_hash=:hash
    WHERE school_id=:sid AND username=:username
  ");

  foreach ($rows as $idx => $r) {
    if (!is_array($r)) { $skipped++; continue; }

    if ($type === "siswa") {
      // ambil dari template dapodik siswa:
      // Nama, NISN, Rombel
      $nama  = norm($r["Nama"] ?? $r["nama"] ?? "");
      $nisn  = norm($r["NISN"] ?? $r["nisn"] ?? "");
      $rombel= norm($r["Rombel"] ?? $r["rombel"] ?? $r["Kelas"] ?? $r["kelas"] ?? "");

      if ($nama==="" || $nisn==="" || $rombel==="") { $skipped++; continue; }
      $username = $nisn;
      if (!validUsername($username)) { $skipped++; continue; }

      $role = "siswa";
      $kelas = $rombel;

      // cek exists
      $stmtFind->execute([":sid"=>$sid, ":username"=>$username]);
      $ex = $stmtFind->fetch(PDO::FETCH_ASSOC);

      if (!$ex) {
        // create: password default = username (nisn)
        $hash = password_hash($username, PASSWORD_BCRYPT);
        $stmtInsert->execute([
          ":sid"=>$sid,
          ":name"=>$nama,
          ":username"=>$username,
          ":hash"=>$hash,
          ":role"=>$role,
          ":kelas"=>$kelas
        ]);
        $created++;
      } else {
        // update: tidak ubah password
        $stmtUpdateNoPass->execute([
          ":name"=>$nama,
          ":role"=>$role,
          ":kelas"=>$kelas,
          ":sid"=>$sid,
          ":username"=>$username
        ]);
        $updated++;
      }

    } else {
      // type guru: template kamu
      // NAMA, NIP, Nama Kelas (Jika Wali Kelas), Guru/Pegawai Karyawan, Password
      $nama = norm($r["NAMA"] ?? $r["Nama"] ?? $r["nama"] ?? "");
      $nip  = norm($r["NIP"] ?? $r["nip"] ?? "");
      $wali = norm($r["Nama Kelas (Jika Wali Kelas)"] ?? $r["wali_kelas"] ?? $r["Wali Kelas"] ?? "");
      $jenis= norm($r["Guru/Pegawai Karyawan"] ?? $r["jenis"] ?? "");
      $pass = (string)($r["Password"] ?? $r["password"] ?? "");

      if ($nama==="" || $nip==="") { $skipped++; continue; }
      $username = $nip;
      if (!validUsername($username)) { $skipped++; continue; }

      $role = guessRoleGuru($jenis);
      if (!in_array($role, $allowed_roles, true)) $role = "guru";

      // kelas: hanya kalau wali kelas diisi (kalau kosong -> null)
      $kelas = ($wali !== "") ? $wali : null;

      // cek exists
      $stmtFind->execute([":sid"=>$sid, ":username"=>$username]);
      $ex = $stmtFind->fetch(PDO::FETCH_ASSOC);

      if (!$ex) {
        // create: password default = username, kecuali kolom Password diisi
        $password = trim($pass) !== "" ? (string)$pass : $username;
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmtInsert->execute([
          ":sid"=>$sid,
          ":name"=>$nama,
          ":username"=>$username,
          ":hash"=>$hash,
          ":role"=>$role,
          ":kelas"=>$kelas
        ]);
        $created++;
      } else {
        // update: kalau password kolom diisi -> update password, kalau kosong -> tidak
        if (trim($pass) !== "") {
          $hash = password_hash((string)$pass, PASSWORD_BCRYPT);
          $stmtUpdateWithPass->execute([
            ":name"=>$nama,
            ":role"=>$role,
            ":kelas"=>$kelas,
            ":hash"=>$hash,
            ":sid"=>$sid,
            ":username"=>$username
          ]);
        } else {
          $stmtUpdateNoPass->execute([
            ":name"=>$nama,
            ":role"=>$role,
            ":kelas"=>$kelas,
            ":sid"=>$sid,
            ":username"=>$username
          ]);
        }
        $updated++;
      }
    }
  }

  $pdo->commit();

  echo json_encode([
    "ok"=>true,
    "type"=>$type,
    "created"=>$created,
    "updated"=>$updated,
    "skipped"=>$skipped
  ]);
  exit;

} catch (PDOException $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  bad($e->getMessage(), 500);
}
