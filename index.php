<?php
declare(strict_types=1);
session_start();

// Resolve tenant (subdomain -> school_id)
require_once __DIR__ . '/config/tenant.php';

// Ambil role dari session (kompatibel lama/baru)
$user = $_SESSION['user'] ?? null;
$role = $_SESSION['role'] ?? ($user['role'] ?? null);

// Kalau sudah login: arahkan sesuai role
if ($user && $role) {
  $role = strtolower(trim((string)$role));

  // CEK DINAS PALING ATAS
  if ($role === 'dinas') {
    header("Location: /dinas.php");
    exit;
  }

  // CEK GURU
  if ($role === 'guru') {
    header("Location: /guru.php");
    exit;
  }

  // CEK ORTU
  if ($role === 'ortu') {
    header("Location: /ortu.php");
    exit;
  }

  // CEK SISWA (default)
  if ($role === 'siswa') {
    header("Location: /siswa.php");
    exit;
  }

  // CEK ADMIN ROLES (super, admin, bk, kesiswaan, kurikulum, pejabat, dan sisa lainnya)
  $adminRoles = ['super','admin','bk','kesiswaan','kurikulum','pejabat'];
  if (in_array($role, $adminRoles, true)) {
    header("Location: /admin_full.php");
    exit;
  }

  // Fallback (kalau role nggak dikenal)
  header("Location: /index.php");
  exit;
}

// Flash error dari login_process
$flash = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

$schoolName = $_SESSION['school']['nama'] ?? 'EduGate';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Login <?= htmlspecialchars((string)$schoolName) ?></title>
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-950 text-white">
  <form method="post" action="/auth/login_process.php"
        class="bg-slate-900 p-6 rounded-2xl border border-slate-800 w-full max-w-sm space-y-3">
    <h1 class="text-xl font-black">Login <?= htmlspecialchars((string)$schoolName) ?></h1>

    <?php if ($flash): ?>
      <div class="bg-red-500/10 border border-red-500/30 text-red-300 text-sm p-3 rounded-lg">
        <?= htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>

    <input name="username" class="w-full p-3 rounded bg-slate-950 border border-slate-700"
           placeholder="Username / NIP / NISN" required>

    <input name="password" type="password" class="w-full p-3 rounded bg-slate-950 border border-slate-700"
           placeholder="Password" required>

    <button class="w-full bg-blue-600 hover:bg-blue-500 font-bold py-3 rounded">
      Masuk
    </button>
  </form>
</body>
</html>