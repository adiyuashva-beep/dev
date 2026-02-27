<?php
// /adminKBM.php (Panel Tim Kurikulum)
require __DIR__ . '/auth/guard.php';
require_login(['super','admin','kurikulum']);
$user = $_SESSION['user'] ?? ['name'=>'Admin','role'=>'admin'];
$name = $user['name'] ?? 'Admin';
$role = $user['role'] ?? 'admin';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Panel Tim Kurikulum - EduGate</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
  <div class="max-w-7xl mx-auto p-4 md:p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <h1 class="text-2xl md:text-3xl font-black text-white">Panel Tim Kurikulum</h1>
        <p class="text-slate-400 text-sm">Login: <span class="text-white font-bold"><?= htmlspecialchars($name) ?></span> · Role: <span class="text-blue-400 font-bold uppercase"><?= htmlspecialchars($role) ?></span></p>
      </div>
      <div class="flex gap-2">
        <a href="/admin_full.php" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm">Kembali</a>
        <a href="/logout.php" class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white font-bold text-sm">Keluar</a>
      </div>
    </div>

    <div class="mt-5 bg-slate-900 border border-slate-800 rounded-2xl p-3">
      <div class="flex flex-wrap gap-2">
        <button id="tab1" onclick="showTab(1)" class="px-4 py-2 rounded-xl font-bold bg-blue-600 text-white">Monitoring KBM (Firebase)</button>
        <button id="tab2" onclick="showTab(2)" class="px-4 py-2 rounded-xl font-bold bg-slate-800 text-slate-200 hover:bg-slate-700">Panel Legacy (gabungan)</button>
      </div>
    </div>

    <div class="mt-4">
      <div id="frame1" class="rounded-2xl overflow-hidden border border-slate-800 bg-black">
        <iframe src="/adminKBM_ganteng.php" class="w-full" style="height: calc(100vh - 220px);"></iframe>
      </div>
      <div id="frame2" class="rounded-2xl overflow-hidden border border-slate-800 bg-black hidden">
        <iframe src="/adminKBM_legacy.php" class="w-full" style="height: calc(100vh - 220px);"></iframe>
      </div>
    </div>
  </div>

  <script>
    function showTab(n){
      document.getElementById('frame1').classList.toggle('hidden', n !== 1);
      document.getElementById('frame2').classList.toggle('hidden', n !== 2);

      const t1 = document.getElementById('tab1');
      const t2 = document.getElementById('tab2');

      if(n === 1){
        t1.className = 'px-4 py-2 rounded-xl font-bold bg-blue-600 text-white';
        t2.className = 'px-4 py-2 rounded-xl font-bold bg-slate-800 text-slate-200 hover:bg-slate-700';
      } else {
        t2.className = 'px-4 py-2 rounded-xl font-bold bg-blue-600 text-white';
        t1.className = 'px-4 py-2 rounded-xl font-bold bg-slate-800 text-slate-200 hover:bg-slate-700';
      }
    }
  </script>
</body>
</html>
