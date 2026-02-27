<?php
/**
 * EduGate Setup
 *
 * Tujuan:
 * - Membuat / memastikan tabel DB (schema)
 * - Membuat user awal (super/admin/dinas) supaya bisa login
 *
 * Keamanan:
 * - CLI: selalu diizinkan
 * - Browser: wajib token (?token=...) yang disimpan di config/setup.local.php
 */

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/api/_schema.php';

// Load setup token (opsional)
$setupToken = '';
$setupLocal = __DIR__ . '/config/setup.local.php';
if (is_file($setupLocal)) {
  /** @noinspection PhpIncludeInspection */
  require $setupLocal;
  if (!empty($EDUGATE_SETUP_TOKEN) && is_string($EDUGATE_SETUP_TOKEN)) {
    $setupToken = $EDUGATE_SETUP_TOKEN;
  }
}

$isCli = (PHP_SAPI === 'cli');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function out_json(array $arr, int $code=200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function ensure_schema(PDO $pdo): void {
  edugate_v5_ensure_tables($pdo);
}

function create_user(PDO $pdo, int $school_id, string $name, string $username, string $password, string $role, ?string $kelas=null): array {
  $role = strtolower(trim($role));
  $allowed = ['super','admin','dinas','guru','bk','kesiswaan','kurikulum','staff','siswa','ortu'];
  if (!in_array($role, $allowed, true)) {
    return ['ok'=>false,'error'=>'Role tidak valid'];
  }
  $username = trim($username);
  if ($username === '') return ['ok'=>false,'error'=>'Username wajib diisi'];
  if (strlen($password) < 6) return ['ok'=>false,'error'=>'Password minimal 6 karakter'];

  $hash = password_hash($password, PASSWORD_BCRYPT);
  $st = $pdo->prepare("INSERT INTO users (school_id, name, username, password_hash, role, kelas)
                      VALUES (:sid, :name, :u, :h, :r, :k)");
  try {
    $st->execute([
      ':sid'=>$school_id,
      ':name'=>$name,
      ':u'=>$username,
      ':h'=>$hash,
      ':r'=>$role,
      ':k'=>$kelas,
    ]);
    return ['ok'=>true];
  } catch (Throwable $e) {
    // kemungkinan duplicate
    return ['ok'=>false,'error'=>$e->getMessage()];
  }
}

// ============================
// CLI MODE
// ============================
if ($isCli) {
  $argv = $_SERVER['argv'] ?? [];
  $opts = getopt('', ['create-user::', 'school-id::', 'name::', 'username::', 'password::', 'role::', 'kelas::']);

  echo "EduGate Setup (CLI)\n";
  echo "PHP: ".PHP_VERSION."\n\n";

  try {
    ensure_schema($pdo);
    echo "[OK] Schema ensured.\n";

    if (isset($opts['create-user'])) {
      $sid = (int)($opts['school-id'] ?? 1);
      $name = (string)($opts['name'] ?? 'Super Admin');
      $username = (string)($opts['username'] ?? 'super');
      $password = (string)($opts['password'] ?? 'admin123');
      $role = (string)($opts['role'] ?? 'super');
      $kelas = isset($opts['kelas']) ? (string)$opts['kelas'] : null;

      $res = create_user($pdo, $sid, $name, $username, $password, $role, $kelas);
      if ($res['ok'] ?? false) {
        echo "[OK] User created: {$username} ({$role}) for school_id={$sid}\n";
        echo "       IMPORTANT: segera ganti password setelah login.\n";
      } else {
        echo "[ERR] Gagal membuat user: ".$res['error']."\n";
      }
    } else {
      echo "\nTips: buat user awal dengan perintah:\n";
      echo "  php setup.php --create-user --role=super --username=super --password=PASSWORD_KAMU\n";
    }

  } catch (Throwable $e) {
    echo "[ERR] ".$e->getMessage()."\n";
    exit(1);
  }

  exit;
}

// ============================
// BROWSER MODE (token required)
// ============================
$token = (string)($_GET['token'] ?? '');
if ($setupToken === '' || $token !== $setupToken) {
  http_response_code(403);
  header('Content-Type: text/plain; charset=utf-8');
  echo "403 Forbidden\n\n";
  echo "Setup hanya bisa diakses via browser jika token benar.\n";
  echo "- Buat file: config/setup.local.php (copy dari config/setup.local.php.example)\n";
  echo "- Isi \$EDUGATE_SETUP_TOKEN\n";
  echo "- Akses: /setup.php?token=TOKEN_KAMU\n";
  exit;
}

// Ensure schema
$schemaOk = false;
$schemaErr = '';
try {
  ensure_schema($pdo);
  $schemaOk = true;
} catch (Throwable $e) {
  $schemaErr = $e->getMessage();
}

// Schools list
$schools = [];
try {
  $schools = $pdo->query("SELECT id, nama_sekolah, subdomain, jenjang, status FROM schools ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  // ignore
}

$msg = null;
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $sid = (int)($_POST['school_id'] ?? 1);
  $name = trim((string)($_POST['name'] ?? ''));
  $username = trim((string)($_POST['username'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $role = trim((string)($_POST['role'] ?? 'super'));
  $kelas = trim((string)($_POST['kelas'] ?? ''));
  $kelas = ($kelas === '') ? null : $kelas;

  if (!$schemaOk) {
    $msgType = 'error';
    $msg = 'Schema gagal dibuat. Periksa koneksi DB.';
  } else {
    $res = create_user($pdo, $sid, $name, $username, $password, $role, $kelas);
    if ($res['ok'] ?? false) {
      $msgType = 'success';
      $msg = "User berhasil dibuat: {$username} ({$role}) untuk school_id={$sid}.";
    } else {
      $msgType = 'error';
      $msg = $res['error'] ?? 'Gagal membuat user';
    }
  }
}

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EduGate Setup</title>
  <style>
    body{font-family:system-ui, -apple-system, Segoe UI, Roboto, Arial; background:#0b1220; color:#e5e7eb; padding:24px}
    .box{max-width:920px;margin:0 auto;background:#111827;border:1px solid #1f2937;border-radius:14px;padding:18px}
    h1{margin:0 0 12px 0;font-size:22px}
    .muted{color:#94a3b8}
    input,select,button{width:100%;padding:12px;border-radius:10px;border:1px solid #334155;background:#0b1220;color:#e5e7eb}
    label{font-size:12px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.08em}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .row{margin:10px 0}
    button{cursor:pointer;background:#2563eb;border-color:#1d4ed8;font-weight:800}
    button:hover{background:#1d4ed8}
    .pill{display:inline-block;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:800}
    .ok{background:#065f46}
    .bad{background:#7f1d1d}
    .info{background:#1e293b}
    .warn{background:#78350f}
    table{width:100%;border-collapse:collapse;margin-top:8px}
    th,td{border-bottom:1px solid #1f2937;padding:10px;font-size:13px;text-align:left}
    th{color:#94a3b8;font-size:12px;text-transform:uppercase;letter-spacing:.08em}
    .msg{padding:10px 12px;border-radius:10px;margin:10px 0}
    .msg.success{background:#052e16;border:1px solid #14532d}
    .msg.error{background:#450a0a;border:1px solid #7f1d1d}
  </style>
</head>
<body>
  <div class="box">
    <h1>EduGate Setup</h1>
    <div class="muted">Mode: Browser (token verified). PHP: <?=h(PHP_VERSION)?>.</div>

    <div style="margin-top:12px">
      <span class="pill <?= $schemaOk ? 'ok' : 'bad' ?>">Schema: <?= $schemaOk ? 'OK' : 'FAILED' ?></span>
      <?php if (!$schemaOk): ?>
        <div class="msg error" style="margin-top:10px">Error: <?=h($schemaErr)?></div>
      <?php endif; ?>
    </div>

    <?php if ($msg): ?>
      <div class="msg <?=$msgType?>"><?=h($msg)?></div>
    <?php endif; ?>

    <h2 style="margin-top:18px">1) Daftar Sekolah (Tenant)</h2>
    <div class="muted">Tabel <code>schools</code> otomatis dibuat. Default tenant: <b>subdomain</b> = <code>app</code>.</div>

    <table>
      <thead><tr><th>ID</th><th>Nama</th><th>Subdomain</th><th>Jenjang</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($schools as $s): ?>
        <tr>
          <td><?=h((string)$s['id'])?></td>
          <td><?=h((string)$s['nama_sekolah'])?></td>
          <td><?=h((string)$s['subdomain'])?></td>
          <td><?=h((string)$s['jenjang'])?></td>
          <td><?=h((string)$s['status'])?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <h2 style="margin-top:18px">2) Buat User Awal</h2>
    <div class="muted">Buat minimal 1 user role <b>super</b> (untuk membuat sekolah lain) atau role <b>admin</b> (untuk kelola sekolah).</div>

    <form method="post">
      <div class="grid">
        <div class="row">
          <label>School ID</label>
          <select name="school_id">
            <?php foreach ($schools as $s): ?>
              <option value="<?=h((string)$s['id'])?>"><?=h((string)$s['id'])?> - <?=h((string)$s['nama_sekolah'])?> (<?=h((string)$s['subdomain'])?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row">
          <label>Role</label>
          <select name="role">
            <option value="super">super</option>
            <option value="admin">admin</option>
            <option value="dinas">dinas</option>
            <option value="guru">guru</option>
            <option value="bk">bk</option>
            <option value="kesiswaan">kesiswaan</option>
            <option value="kurikulum">kurikulum</option>
            <option value="staff">staff</option>
            <option value="siswa">siswa</option>
            <option value="ortu">ortu</option>
          </select>
        </div>
      </div>

      <div class="grid">
        <div class="row">
          <label>Nama</label>
          <input name="name" placeholder="Nama lengkap" required />
        </div>
        <div class="row">
          <label>Username</label>
          <input name="username" placeholder="contoh: super / admin / 123456" required />
        </div>
      </div>

      <div class="grid">
        <div class="row">
          <label>Password</label>
          <input type="password" name="password" placeholder="minimal 6 karakter" required />
        </div>
        <div class="row">
          <label>Kelas (opsional)</label>
          <input name="kelas" placeholder="misal: X IPA 1 (untuk siswa)" />
        </div>
      </div>

      <div class="row">
        <button type="submit">Buat User</button>
      </div>
    </form>

    <div class="muted" style="margin-top:16px">
      <b>Catatan keamanan:</b> setelah setup selesai, sebaiknya hapus <code>setup.php</code> atau set token yang kuat.
    </div>
  </div>
</body>
</html>
