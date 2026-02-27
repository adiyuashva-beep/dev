<?php
require __DIR__ . "/auth/guard.php";
require_login(['admin','super','bk','kesiswaan','kurikulum','dinas']);

$user = $_SESSION['user'] ?? ['role'=>'admin','name'=>'Admin'];
$role = $user['role'] ?? 'admin';
$name = $user['name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Panel V2.0 - EduGate</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

  <style>
    body { font-family: 'Outfit', sans-serif; background-color: #0f172a; color: #e2e8f0; }
    .hide-scroll::-webkit-scrollbar { display: none; }
    .nav-item.active { background: rgba(59,130,246,.10); border-right: 3px solid #3b82f6; color: #60a5fa; }
    .fade-in { animation: fadeIn .3s ease-in-out; }
    @keyframes fadeIn { from { opacity:0; transform: translateY(10px);} to {opacity:1; transform: translateY(0);} }
    .modal { transition: opacity .2s ease; }
    .toggle-checkbox:checked { right: 0; border-color: #10b981; }
    .toggle-checkbox:checked + .toggle-label { background-color: #10b981; }
  </style>

  <!-- Backup/Migrasi libs -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-firestore-compat.js"></script>

</head>

<body class="flex h-screen overflow-hidden text-sm">
  <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col hidden md:flex">
    <div class="p-6">
      <h1 class="font-black text-2xl tracking-tighter text-blue-500">
        EduGate <span class="text-white text-xs align-top">v2.0</span>
      </h1>
      <p class="text-xs text-slate-500 tracking-widest uppercase mt-1">Admin Panel</p>

      <div id="badge-role" class="mt-2 inline-block px-2 py-1 rounded bg-slate-800 text-xs font-bold text-slate-300">
        <?= htmlspecialchars(strtoupper($role)) ?>
      </div>

      <div class="mt-3 text-xs text-slate-400">
        Login: <span class="text-white font-bold"><?= htmlspecialchars($name) ?></span>
      </div>
    </div>

    <nav class="flex-1 space-y-1 px-3 mt-4 overflow-y-auto hide-scroll">
      <p class="px-4 text-[10px] font-bold text-slate-600 uppercase mb-2">Menu Utama</p>

      <button onclick="nav('dashboard')" id="nav-dashboard"
        class="nav-item active w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition">
        <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard Sistem
      </button>

      <button onclick="nav('jadwal')" id="nav-jadwal"
        class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition">
        <i data-lucide="calendar-clock" class="w-5 h-5 text-yellow-500"></i> Jadwal & Aturan
      </button>

      <button onclick="nav('control')" id="nav-control"
        class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition">
        <i data-lucide="radar" class="w-5 h-5 text-emerald-400"></i> Control Room
      </button>

      <button onclick="nav('data')" id="nav-data"
        class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition">
        <i data-lucide="database" class="w-5 h-5"></i> Database User
      </button>
    <button onclick="window.location.href='admin_ortu.php'" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition">
    <i data-lucide="users" class="w-5 h-5"></i> Manajemen Orang Tua
    </button>
    <button onclick="window.location.href='profil_sekolah.php'" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition">
    <i data-lucide="school" class="w-5 h-5"></i> Profil Sekolah
</button>
      <button onclick="nav('mapel')" id="nav-mapel"
        class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition">
        <i data-lucide="library" class="w-5 h-5"></i> Database Mapel
      </button>
      <button onclick="nav('migrasi')" id="nav-migrasi"
        class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition">
        <i data-lucide="archive" class="w-5 h-5 text-indigo-300"></i> Backup & Migrasi
      </button>


      <p class="px-4 text-[10px] font-bold text-slate-600 uppercase mb-2 mt-4">Akses Panel Lain</p>
      <?php if (strtolower((string)$role) === 'super'): ?>
      <button onclick="window.open((location.pathname.includes('/app/')) ? '/app/super_admin_schools.php' : '/super_admin_schools.php','_blank')"
        class="w-full flex items-center gap-3 px-4 py-3 text-pink-300 hover:text-white hover:bg-pink-900/30 rounded-lg transition font-bold">
        <i data-lucide="shield" class="w-5 h-5"></i> Super Admin • Schools
      </button>
      <?php endif; ?>
      <button onclick="window.open((location.pathname.includes('/app/')) ? '/app/adminBK.php' : '/adminBK.php','_blank')"
        class="w-full flex items-center gap-3 px-4 py-3 text-emerald-400 hover:text-white hover:bg-emerald-900/30 rounded-lg transition font-bold">
        <i data-lucide="user-check" class="w-5 h-5"></i> Panel Tim Kesiswaan
      </button>
      <button onclick="window.open((location.pathname.includes('/app/')) ? '/app/admin_kurikulum_jurnal.php' : '/admin_kurikulum_jurnal.php','_blank')"
        class="w-full flex items-center gap-3 px-4 py-3 text-yellow-400 hover:text-white hover:bg-yellow-900/30 rounded-lg transition font-bold">
        <i data-lucide="book-open-check" class="w-5 h-5"></i> Monitoring Jurnal (Kurikulum)
      </button>

      <button onclick="window.open((location.pathname.includes('/app/')) ? '/app/admin_rekap_7hebat.php' : '/admin_rekap_7hebat.php','_blank')"
        class="w-full flex items-center gap-3 px-4 py-3 text-indigo-400 hover:text-white hover:bg-indigo-900/30 rounded-lg transition font-bold">
        <i data-lucide="sparkles" class="w-5 h-5"></i> Monitoring 7 Hebat
      </button>
      <button onclick="window.open((location.pathname.includes('/app/')) ? '/app/admin_rekap_presensi_guru.php' : '/admin_rekap_presensi_guru.php','_blank')"
        class="w-full flex items-center gap-3 px-4 py-3 text-blue-400 hover:text-white hover:bg-blue-900/30 rounded-lg transition font-bold">
        <i data-lucide="clipboard-check" class="w-5 h-5"></i> Rekap Presensi Guru
      </button>
      <button onclick="window.open((location.pathname.includes('/app/')) ? '/app/admin_persetujuan_izin_guru.php' : '/admin_persetujuan_izin_guru.php','_blank')"
        class="w-full flex items-center gap-3 px-4 py-3 text-emerald-300 hover:text-white hover:bg-emerald-900/30 rounded-lg transition font-bold">
        <i data-lucide="stamp" class="w-5 h-5"></i> Persetujuan Izin/Cuti Guru
      </button>
      <button onclick="window.open((location.pathname.includes('/app/')) ? '/app/admin_tugas_guru.php' : '/admin_tugas_guru.php','_blank')"
        class="w-full flex items-center gap-3 px-4 py-3 text-emerald-400 hover:text-white hover:bg-emerald-900/30 rounded-lg transition font-bold">
        <i data-lucide="list-todo" class="w-5 h-5"></i> Tugas Tambahan Guru
      </button>
    </nav>

    <div class="p-4 border-t border-slate-800">
      <button onclick="logout()"
        class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-red-500/10 text-red-500 rounded-lg hover:bg-red-500 hover:text-white transition font-bold">
        <i data-lucide="log-out" class="w-4 h-4"></i> Keluar
      </button>
    </div>
  </aside>

  <main class="flex-1 overflow-y-auto bg-slate-950 relative p-4 md:p-8">
    <div class="md:hidden flex justify-between items-center mb-6">
      <h1 class="font-bold text-lg text-blue-500">EduGate Admin V2</h1>
      <button onclick="logout()" class="text-red-500"><i data-lucide="log-out"></i></button>
    </div>

    <!-- DASHBOARD -->
    <div id="view-dashboard" class="space-y-6 fade-in">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
          <h2 class="text-3xl font-bold text-white">System Monitor</h2>
          <p class="text-slate-400 text-sm">
            Status Server: <span class="text-emerald-400 font-bold">ONLINE (EduGate)</span>
          </p>
        </div>
        <div class="flex gap-2">
          <button onclick="tarikDataUser(true)"
            class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-lg font-bold flex items-center gap-2 text-xs transition border border-slate-700">
            <i data-lucide="refresh-cw" class="w-4 h-4"></i> Sync Database User
          </button>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800">
          <p class="text-slate-500 text-xs font-bold uppercase">Total Siswa Aktif</p>
          <h3 class="text-3xl font-black text-white mt-1" id="stat-total">0</h3>
        </div>
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800">
          <p class="text-slate-500 text-xs font-bold uppercase">Guru/Tendik</p>
          <h3 class="text-3xl font-black text-orange-400 mt-1" id="stat-guru">0</h3>
        </div>
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800">
          <p class="text-slate-500 text-xs font-bold uppercase">Login</p>
          <h3 class="text-xl font-black text-blue-400 mt-2"><?= htmlspecialchars($role) ?></h3>
        </div>
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800">
          <p class="text-slate-500 text-xs font-bold uppercase">DB Status</p>
          <h3 class="text-xl font-black text-emerald-400 mt-2" id="stat-db">OK</h3>
        </div>
      </div>

      <div class="bg-slate-900 rounded-2xl border border-slate-800 p-6">
        <h3 class="text-white font-black text-xl mb-1">Quick Notes</h3>
        <p class="text-slate-400 text-sm">
          Pengaturan Jam Operasional + GPS sekarang <b>ada di menu Jadwal & Aturan</b> dan <b>Control Room</b>.
        </p>
      </div>
    </div>

    <!-- JADWAL & ATURAN -->
    <div id="view-jadwal" class="hidden space-y-8 fade-in">
      <h2 class="text-3xl font-bold text-white flex items-center gap-3">
        <i data-lucide="calendar-clock" class="w-8 h-8 text-yellow-500"></i>
        Jadwal & Aturan
      </h2>

      <!-- MODE EVENT -->
      <div class="bg-gradient-to-r from-rose-900 to-slate-900 rounded-[2rem] border border-rose-800/50 p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-6 opacity-20">
          <i data-lucide="siren" class="w-32 h-32 text-rose-500"></i>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
          <div class="flex-1">
            <h3 class="font-black text-white text-2xl mb-1 flex items-center gap-2">
              <i data-lucide="zap" class="text-yellow-400"></i> SAKLAR PAMUNGKAS (MODE EVENT)
            </h3>
            <p class="text-rose-200 text-sm">
              Jika <b>ON</b>, jadwal mingguan bisa “dianggap longgar”. Kamu bisa pakai ini buat event / lomba / dispensasi.
            </p>

            <div class="mt-4">
              <label class="text-xs text-rose-300 uppercase font-bold">Pesan Peringatan Popup (opsional)</label>
              <textarea id="pesanBebasPulang" class="w-full bg-black/30 text-white p-3 rounded-xl border border-rose-700/50 mt-1 h-20 outline-none"
                placeholder="Contoh: Karena Classmeeting, siswa boleh pulang setelah lomba..."></textarea>
            </div>
          </div>

          <div class="flex flex-col items-center gap-2 bg-black/20 p-4 rounded-2xl border border-rose-500/30">
            <div class="relative inline-block w-16 align-middle select-none">
              <input type="checkbox" id="toggleBebasPulang"
                class="toggle-checkbox absolute block w-8 h-8 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300" />
              <label for="toggleBebasPulang"
                class="toggle-label block overflow-hidden h-8 rounded-full bg-slate-700 cursor-pointer border border-slate-600"></label>
            </div>
            <span class="text-xs font-bold text-white uppercase tracking-widest mt-2">STATUS MODE</span>
          </div>
        </div>

        <div class="mt-6 border-t border-rose-800/50 pt-4 text-right">
          <button onclick="saveModeEvent()"
            class="bg-rose-600 hover:bg-rose-500 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-rose-900/50 active:scale-95 transition">
            SIMPAN STATUS MODE
          </button>
        </div>
      </div>

      <!-- JADWAL MINGGUAN -->
      <div class="bg-slate-900 rounded-[2rem] border border-slate-800 p-8 shadow-2xl relative">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h3 class="font-bold text-white text-xl mb-1 flex items-center gap-2">
              <i data-lucide="calendar" class="text-blue-500"></i> Jadwal Mingguan (Reguler)
            </h3>
            <p class="text-slate-400 text-xs">Atur jam buka absen, batas terlambat, dan jam pulang.</p>
          </div>
          <button onclick="saveJadwal()"
            class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl shadow-lg flex items-center gap-2 active:scale-95 transition">
            <i data-lucide="save" class="w-4 h-4"></i> Simpan Jadwal
          </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-800">
          <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950 text-xs uppercase font-bold text-slate-500">
              <tr>
                <th class="p-4">Hari</th>
                <th class="p-4">Status</th>
                <th class="p-4">Jam Buka Absen</th>
                <th class="p-4 text-red-400">Batas Terlambat</th>
                <th class="p-4 text-emerald-400">Jam Pulang</th>
              </tr>
            </thead>
            <tbody id="tbody-jadwal" class="divide-y divide-slate-800 bg-slate-900/50">
              <tr><td colspan="5" class="p-4 text-center italic text-slate-500">Memuat jadwal...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- CONTROL ROOM -->
    <div id="view-control" class="hidden space-y-6 fade-in">
      <h2 class="text-3xl font-bold text-white flex items-center gap-3">
        <i data-lucide="radar" class="w-8 h-8 text-emerald-400"></i>
        Control Room
      </h2>

      <!-- GERBANG AKSES -->
      <div class="bg-slate-900 rounded-[2rem] border border-slate-800 p-8 shadow-2xl">
        <h3 class="font-black text-white text-xl mb-2 flex items-center gap-2">
          <i data-lucide="lock" class="text-yellow-400"></i> Gerbang Akses (Jam Operasional)
        </h3>
        <p class="text-slate-400 text-sm mb-6">Kamu bisa ON/OFF akses login per kelompok. (Kalau mau benar-benar “mengunci”, nanti login_process juga kita kaitkan ke flag ini).</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-slate-950 border border-slate-800 p-5 rounded-2xl">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-white font-black">Akses Siswa</p>
                <p class="text-slate-500 text-xs">Login & Dashboard</p>
              </div>
              <button id="btn-akses-siswa" onclick="toggleAccess('akses_siswa')" class="px-4 py-2 rounded-xl font-black text-xs bg-emerald-600 text-white">BUKA / ON</button>
            </div>
          </div>

          <div class="bg-slate-950 border border-slate-800 p-5 rounded-2xl">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-white font-black">Akses Guru</p>
                <p class="text-slate-500 text-xs">Jurnal & Absen</p>
              </div>
              <button id="btn-akses-guru" onclick="toggleAccess('akses_guru')" class="px-4 py-2 rounded-xl font-black text-xs bg-emerald-600 text-white">BUKA / ON</button>
            </div>
          </div>

          <div class="bg-slate-950 border border-slate-800 p-5 rounded-2xl">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-white font-black">Akses Ortu</p>
                <p class="text-slate-500 text-xs">Pantau Wali Murid</p>
              </div>
              <button id="btn-akses-ortu" onclick="toggleAccess('akses_ortu')" class="px-4 py-2 rounded-xl font-black text-xs bg-slate-700 text-slate-200">TUTUP / OFF</button>
            </div>
          </div>

          <div class="bg-slate-950 border border-slate-800 p-5 rounded-2xl">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-white font-black">Panel Pejabat</p>
                <p class="text-slate-500 text-xs">Admin/BK/Kurikulum</p>
              </div>
              <button id="btn-akses-pejabat" onclick="toggleAccess('akses_pejabat')" class="px-4 py-2 rounded-xl font-black text-xs bg-emerald-600 text-white">BUKA / ON</button>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end">
          <button onclick="saveAccess()"
            class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl shadow-lg active:scale-95 transition">
            Simpan Gerbang Akses
          </button>
        </div>
      </div>

      <!-- GPS & RADIUS -->
      <div class="bg-slate-900 rounded-[2rem] border border-slate-800 p-8 shadow-2xl">
        <h3 class="font-black text-white text-xl mb-2 flex items-center gap-2">
          <i data-lucide="map-pin" class="text-emerald-400"></i> Logika GPS & Radius
        </h3>
        <p class="text-slate-400 text-sm mb-6">Kalau Mode GPS OFF → siswa boleh absen dari mana saja (mode darurat).</p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-slate-950 border border-slate-800 p-6 rounded-2xl">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-white font-black">Mode Disiplin GPS</p>
                <p class="text-slate-500 text-xs">ON: wajib di radius sekolah</p>
              </div>
              <button id="btn-mode-gps" onclick="toggleGpsMode()" class="px-4 py-2 rounded-xl font-black text-xs bg-emerald-600 text-white">AKTIF</button>
            </div>
          </div>

          <div class="bg-slate-950 border border-slate-800 p-6 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
              <p class="text-white font-black">Radius Toleransi</p>
              <p class="text-blue-400 font-black text-xl"><span id="txt-radius">150</span>m</p>
            </div>
            <input id="range-radius" type="range" min="10" max="2000" step="10" value="150" class="w-full">
          </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-slate-950 border border-slate-800 p-4 rounded-2xl">
            <label class="text-slate-500 text-xs font-bold uppercase">LATITUDE</label>
            <input id="inp-lat" class="w-full mt-1 bg-slate-900 text-white p-3 rounded-xl border border-slate-700" placeholder="-7.xxxxxxx">
          </div>
          <div class="bg-slate-950 border border-slate-800 p-4 rounded-2xl">
            <label class="text-slate-500 text-xs font-bold uppercase">LONGITUDE</label>
            <input id="inp-lng" class="w-full mt-1 bg-slate-900 text-white p-3 rounded-xl border border-slate-700" placeholder="109.xxxxxxx">
          </div>
        </div>

        <div class="mt-6 flex justify-end">
          <button onclick="saveGps()"
            class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 px-6 rounded-xl shadow-lg active:scale-95 transition">
            Simpan GPS & Radius
          </button>
        </div>
      </div>
    </div>

    <!-- USERS -->
    <div id="view-data" class="hidden space-y-6 fade-in">
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
          <h2 class="text-3xl font-bold text-white">Database User</h2>
          <p class="text-slate-400 text-sm">Kelola user manual + massal via Excel (Dapodik-style)</p>
        </div>

        <div class="flex flex-wrap gap-2">
          <a href="<?= (strpos($_SERVER['REQUEST_URI'], '/app/') !== false) ? '/app/templates/Tamplate Siswa Edugate.xlsx' : '/templates/Tamplate Siswa Edugate.xlsx' ?>"
             class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-lg font-bold text-xs border border-slate-700">
            Download Template Siswa
          </a>
          <a href="<?= (strpos($_SERVER['REQUEST_URI'], '/app/') !== false) ? '/app/templates/Tamplate Guru Edugate.xlsx' : '/templates/Tamplate Guru Edugate.xlsx' ?>"
             class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-lg font-bold text-xs border border-slate-700">
            Download Template Guru
          </a>

          <button onclick="exportExcel('siswa')"
            class="bg-emerald-700 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold text-xs">
            Export Siswa
          </button>

          <button onclick="exportExcel('guru')"
            class="bg-orange-700 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-bold text-xs">
            Export Guru
          </button>
        </div>
      </div>

      <div class="flex gap-2 mb-2">
        <button onclick="gantiTab('manual')" id="btn-manual"
          class="px-4 py-2 rounded-lg font-bold bg-blue-600 text-white transition">
          Input Manual
        </button>
        <button onclick="gantiTab('manage')" id="btn-manage"
          class="px-4 py-2 rounded-lg font-bold bg-slate-800 text-slate-300 transition hover:text-white">
          Cari / Edit / Hapus
        </button>
        <button onclick="gantiTab('excel')" id="btn-excel"
          class="px-4 py-2 rounded-lg font-bold bg-slate-800 text-slate-300 transition hover:text-white">
          Import Excel (Dapodik)
        </button>
      </div>

      <!-- TAB MANUAL -->
      <div id="tab-manual" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800">
          <h3 class="font-bold text-white text-lg mb-4">Tambah Siswa</h3>
          <div class="space-y-3">
            <input id="s_nama" class="w-full bg-slate-950 text-white p-3 rounded-lg border border-slate-700" placeholder="Nama siswa">
            <input id="s_user" class="w-full bg-slate-950 text-white p-3 rounded-lg border border-slate-700" placeholder="NISN (username)">
            <input id="s_kelas" class="w-full bg-slate-950 text-white p-3 rounded-lg border border-slate-700" placeholder="Rombel/Kelas (cth: X IPA 1)">
            <input id="s_pass" class="w-full bg-slate-950 text-white p-3 rounded-lg border border-slate-700" value="123456" placeholder="Password">
            <button onclick="tambahSiswa()" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg">Simpan Siswa</button>
          </div>
        </div>

        <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800">
          <h3 class="font-bold text-white text-lg mb-4">Tambah Pegawai</h3>
          <div class="space-y-3">
            <input id="g_nama" class="w-full bg-slate-950 text-white p-3 rounded-lg border border-slate-700" placeholder="Nama pegawai">
            <input id="g_user" class="w-full bg-slate-950 text-white p-3 rounded-lg border border-slate-700" placeholder="NIP (username)">
            <select id="g_role" class="w-full bg-slate-950 text-white p-3 rounded-lg border border-slate-700 outline-none">
              <option value="guru">Guru</option>
              <option value="bk">BK / Kesiswaan</option>
              <option value="kurikulum">Kurikulum</option>
              <option value="staff">Staff TU</option>
              <option value="admin">Admin IT</option>
            </select>
            <input id="g_pass" class="w-full bg-slate-950 text-white p-3 rounded-lg border border-slate-700" value="123456" placeholder="Password">
            <button onclick="tambahGuru()" class="w-full bg-orange-600 hover:bg-orange-500 text-white font-bold py-3 rounded-lg">Simpan Pegawai</button>
          </div>
        </div>
      </div>

      <!-- TAB MANAGE -->
      <div id="tab-manage" class="hidden bg-slate-900 p-6 rounded-2xl border border-slate-800">
        <div class="mb-4 flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
          <input type="text" id="cariUserBox" onkeyup="renderTable()"
            class="w-full md:w-1/2 bg-slate-950 text-white p-3 rounded-lg border border-slate-700 focus:ring-2 focus:ring-blue-500 outline-none"
            placeholder="Cari nama/username...">

          <button onclick="tarikDataUser(true)"
            class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-3 rounded-lg font-bold text-xs transition border border-slate-700 flex items-center gap-2">
            <i data-lucide="refresh-cw" class="w-4 h-4"></i> Sync
          </button>
        </div>

        <div class="overflow-x-auto max-h-[520px] border border-slate-800 rounded-lg">
          <table class="w-full text-left text-slate-300">
            <thead class="bg-slate-950 text-xs uppercase font-bold text-slate-500 sticky top-0">
              <tr>
                <th class="p-3">Nama</th>
                <th class="p-3">Role</th>
                <th class="p-3">Username</th>
                <th class="p-3">Kelas</th>
                <th class="p-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody id="tbody-users" class="divide-y divide-slate-800">
              <tr><td colspan="5" class="p-4 text-center italic text-slate-500">Memuat data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB EXCEL -->
      <div id="tab-excel" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800">
          <h3 class="font-black text-white text-lg mb-3">Import Excel Siswa (Dapodik-style)</h3>
          <p class="text-xs text-slate-400 mb-4">
            Header wajib: <span class="font-mono text-slate-200">Nama, NISN, Rombel</span>.
          </p>
          <input type="file" id="fileSiswa" accept=".xlsx,.xls" class="block w-full text-sm text-slate-500 mb-3"/>
          <button onclick="importExcel('siswa')" class="w-full bg-emerald-700 hover:bg-emerald-600 text-white font-bold py-3 rounded-lg">
            Import Siswa
          </button>
        </div>

        <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800">
          <h3 class="font-black text-white text-lg mb-3">Import Excel Guru/Tendik</h3>
          <p class="text-xs text-slate-400 mb-4">
            Header wajib: <span class="font-mono text-slate-200">NAMA, NIP</span>.
          </p>
          <input type="file" id="fileGuru" accept=".xlsx,.xls" class="block w-full text-sm text-slate-500 mb-3"/>
          <button onclick="importExcel('guru')" class="w-full bg-orange-700 hover:bg-orange-600 text-white font-bold py-3 rounded-lg">
            Import Guru
          </button>
        </div>

        <div class="md:col-span-2 bg-slate-900 p-6 rounded-2xl border border-slate-800">
          <h3 class="font-bold text-white mb-2">Catatan penting</h3>
          <ul class="text-sm text-slate-400 list-disc pl-5 space-y-1">
            <li>Import ini sistemnya <b>UPSERT</b>: kalau NISN/NIP sudah ada → update.</li>
            <li>Kalau baris invalid → diskip.</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- MAPEL -->
    <div id="view-mapel" class="hidden space-y-6 fade-in">
      <h2 class="text-3xl font-bold text-white">Database Mata Pelajaran</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800">
          <h3 class="font-bold text-white mb-4">Tambah Mapel</h3>
          <div class="space-y-4">
            <input type="text" id="inputNamaMapel" class="w-full bg-slate-800 text-white p-3 rounded-lg border border-slate-700" placeholder="Nama Mapel (Cth: Matematika)">
            <button onclick="tambahMapel()" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 rounded-lg">Simpan</button>
          </div>
        </div>
        <div class="md:col-span-2 bg-slate-900 p-6 rounded-2xl border border-slate-800">
          <h3 class="font-bold text-white mb-4">Daftar Mapel</h3>
          <div class="max-h-96 overflow-y-auto">
            <ul id="list-mapel" class="space-y-2">
              <li class="text-center text-slate-500 italic">Memuat...</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

  </main>

  <!-- MODAL EDIT USER -->
  <div id="modalEdit" class="modal opacity-0 pointer-events-none fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/80" onclick="tutupModal()"></div>
    <div class="relative w-[92%] md:max-w-md bg-slate-800 border border-slate-700 rounded-2xl p-5">
      <div class="flex justify-between items-center">
        <h3 class="text-white font-black text-lg">Edit User</h3>
        <button class="text-slate-300 hover:text-white" onclick="tutupModal()">
          <i data-lucide="x" class="w-6 h-6"></i>
        </button>
      </div>

      <div class="mt-4 space-y-3">
        <div>
          <label class="text-xs text-slate-400 font-bold uppercase">Username (tidak bisa diubah)</label>
          <input id="e_user" disabled class="w-full bg-slate-950 text-slate-400 p-3 rounded-lg border border-slate-700 mt-1" />
        </div>

        <div>
          <label class="text-xs text-slate-400 font-bold uppercase">Nama</label>
          <input id="e_nama" class="w-full bg-slate-950 text-white p-3 rounded-lg border border-slate-700 mt-1" />
        </div>

        <div>
          <label class="text-xs text-slate-400 font-bold uppercase">Role</label>
          <select id="e_role" class="w-full bg-slate-950 text-white p-3 rounded-lg border border-slate-700 mt-1 outline-none">
            <option value="siswa">siswa</option>
            <option value="guru">guru</option>
            <option value="admin">admin</option>
            <option value="bk">bk</option>
            <option value="kurikulum">kurikulum</option>
            <option value="staff">staff</option>
            <option value="kesiswaan">kesiswaan</option>
            <option value="super">super</option>
          </select>
        </div>

        <div>
          <label class="text-xs text-slate-400 font-bold uppercase">Kelas/Rombel</label>
          <input id="e_kelas" class="w-full bg-slate-950 text-white p-3 rounded-lg border border-slate-700 mt-1" placeholder="cth: X IPA 1" />
        </div>

        <div>
          <label class="text-xs text-slate-400 font-bold uppercase">Password (kosongkan jika tidak ganti)</label>
          <input id="e_pass" class="w-full bg-slate-950 text-white p-3 rounded-lg border border-slate-700 mt-1" placeholder="••••••" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button onclick="tutupModal()" class="px-4 py-2 rounded-lg bg-slate-700 text-white hover:bg-slate-600">Batal</button>
          <button onclick="simpanEdit()" class="px-4 py-2 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-500">Simpan</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    lucide.createIcons();

    const BASE_API = (location.pathname.includes('/app/')) ? '/app/api' : '/api';
    const CURRENT_ROLE = <?= json_encode($role) ?>;

    // ---------- NAV ----------
    window.nav = (v) => {
      document.querySelectorAll('[id^="view-"]').forEach(e => e.classList.add('hidden'));
      document.querySelectorAll('.nav-item').forEach(e => e.classList.remove('active'));
      const view = document.getElementById('view-' + v);
      const btn  = document.getElementById('nav-' + v);
      if (view) view.classList.remove('hidden');
      if (btn)  btn.classList.add('active');
      lucide.createIcons();
    };

    // ---------- TAB USER ----------
    window.gantiTab = (tab) => {
      ['manual','manage','excel'].forEach(t => document.getElementById('tab-' + t)?.classList.add('hidden'));
      ['manual','manage','excel'].forEach(t => {
        const b = document.getElementById('btn-' + t);
        if (!b) return;
        b.classList.remove('bg-blue-600','text-white');
        b.classList.add('bg-slate-800','text-slate-300');
      });
      document.getElementById('tab-' + tab)?.classList.remove('hidden');
      const btn = document.getElementById('btn-' + tab);
      if(btn){
        btn.classList.add('bg-blue-600','text-white');
        btn.classList.remove('bg-slate-800','text-slate-300');
      }
    };

    function escapeHtml(s){
      return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }
    function escapeJs(s){
      return String(s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'");
    }

    // ================== SETTINGS (JADWAL + CONTROL) ==================
    let SETTINGS = null;
    let JADWAL = {};
    let ACCESS = {
      akses_siswa:1, akses_guru:1, akses_ortu:0, akses_pejabat:1,
      refleksi_ortu:0, refleksi_guru:1
    };
    let GPS = { mode_gps: 1, radius_m: 150, lokasi_lat: null, lokasi_lng: null };
    let MODE = { mode_bebas_pulang: 0, pesan_bebas_pulang: "" };

    async function loadSettings(){
      const res = await fetch(`${BASE_API}/settings_get.php`, { credentials:'include' });
      const js = await res.json();
      if(!js.ok) throw new Error(js.error || 'Gagal load settings');

      SETTINGS = js.data || {};
      JADWAL = js.jadwal || {};

      MODE.mode_bebas_pulang = Number(SETTINGS.mode_bebas_pulang||0);
      MODE.pesan_bebas_pulang = SETTINGS.pesan_bebas_pulang || "";

      GPS.mode_gps = Number(SETTINGS.mode_gps||1);
      GPS.radius_m = Number(SETTINGS.radius_m||150);
      GPS.lokasi_lat = (SETTINGS.lokasi_lat ?? null);
      GPS.lokasi_lng = (SETTINGS.lokasi_lng ?? null);

      ACCESS.akses_siswa = Number(SETTINGS.akses_siswa||1);
      ACCESS.akses_guru = Number(SETTINGS.akses_guru||1);
      ACCESS.akses_ortu = Number(SETTINGS.akses_ortu||0);
      ACCESS.akses_pejabat = Number(SETTINGS.akses_pejabat||1);
      ACCESS.refleksi_ortu = Number(SETTINGS.refleksi_ortu||0);
      ACCESS.refleksi_guru = Number(SETTINGS.refleksi_guru||1);

      // apply UI
      document.getElementById('pesanBebasPulang').value = MODE.pesan_bebas_pulang;
      document.getElementById('toggleBebasPulang').checked = MODE.mode_bebas_pulang === 1;

      // gps ui
      setBtnState('btn-mode-gps', GPS.mode_gps === 1, 'AKTIF', 'OFF');
      document.getElementById('range-radius').value = String(GPS.radius_m);
      document.getElementById('txt-radius').textContent = String(GPS.radius_m);
      document.getElementById('inp-lat').value = (GPS.lokasi_lat ?? '');
      document.getElementById('inp-lng').value = (GPS.lokasi_lng ?? '');

      // access ui
      applyAccessButtons();

      // jadwal table
      renderJadwalTable();
      lucide.createIcons();
    }

    function setBtnState(id, isOn, textOn='ON', textOff='OFF'){
      const b = document.getElementById(id);
      if(!b) return;
      if(isOn){
        b.textContent = textOn;
        b.className = 'px-4 py-2 rounded-xl font-black text-xs bg-emerald-600 text-white';
      } else {
        b.textContent = textOff;
        b.className = 'px-4 py-2 rounded-xl font-black text-xs bg-slate-700 text-slate-200';
      }
    }

    function applyAccessButtons(){
      setBtnState('btn-akses-siswa', ACCESS.akses_siswa===1, 'BUKA / ON', 'TUTUP / OFF');
      setBtnState('btn-akses-guru', ACCESS.akses_guru===1, 'BUKA / ON', 'TUTUP / OFF');
      setBtnState('btn-akses-ortu', ACCESS.akses_ortu===1, 'BUKA / ON', 'TUTUP / OFF');
      setBtnState('btn-akses-pejabat', ACCESS.akses_pejabat===1, 'BUKA / ON', 'TUTUP / OFF');
    }

    window.toggleAccess = (key) => {
      ACCESS[key] = ACCESS[key] ? 0 : 1;
      applyAccessButtons();
    };

    window.saveAccess = async () => {
      const ok = await Swal.fire({title:'Simpan gerbang akses?', icon:'question', showCancelButton:true, confirmButtonText:'Simpan', cancelButtonText:'Batal'});
      if(!ok.isConfirmed) return;

      const res = await fetch(`${BASE_API}/settings_save.php`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'include',
        body: JSON.stringify({ section:'akses', ...ACCESS })
      });
      const js = await res.json();
      if(!js.ok) return Swal.fire('Gagal', js.error || 'Error', 'error');
      Swal.fire('Sukses', 'Gerbang akses tersimpan', 'success');
    };

    window.toggleGpsMode = () => {
      GPS.mode_gps = GPS.mode_gps ? 0 : 1;
      setBtnState('btn-mode-gps', GPS.mode_gps===1, 'AKTIF', 'OFF');
    };

    document.getElementById('range-radius').addEventListener('input', (e)=>{
      GPS.radius_m = Number(e.target.value||150);
      document.getElementById('txt-radius').textContent = String(GPS.radius_m);
    });

    window.saveGps = async () => {
      GPS.lokasi_lat = document.getElementById('inp-lat').value.trim() || null;
      GPS.lokasi_lng = document.getElementById('inp-lng').value.trim() || null;

      const res = await fetch(`${BASE_API}/settings_save.php`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'include',
        body: JSON.stringify({
          section:'gps',
          mode_gps: GPS.mode_gps,
          radius_m: GPS.radius_m,
          lokasi_lat: GPS.lokasi_lat,
          lokasi_lng: GPS.lokasi_lng
        })
      });
      const js = await res.json();
      if(!js.ok) return Swal.fire('Gagal', js.error || 'Error', 'error');
      Swal.fire('Sukses', 'GPS & Radius tersimpan', 'success');
    };

    window.saveModeEvent = async () => {
      const mode = document.getElementById('toggleBebasPulang').checked ? 1 : 0;
      const pesan = document.getElementById('pesanBebasPulang').value || '';

      const res = await fetch(`${BASE_API}/settings_save.php`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'include',
        body: JSON.stringify({ section:'mode', mode_bebas_pulang: mode, pesan_bebas_pulang: pesan })
      });
      const js = await res.json();
      if(!js.ok) return Swal.fire('Gagal', js.error || 'Error', 'error');
      Swal.fire('Sukses', 'Mode event tersimpan', 'success');
    };

    function renderJadwalTable(){
      const tb = document.getElementById('tbody-jadwal');
      const hariList = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];

      tb.innerHTML = hariList.map(h=>{
        const d = JADWAL[h] || {is_libur: (h==='Minggu'?1:0), masuk:'06:30', telat:'07:00', pulang:'15:30'};
        const rowClass = d.is_libur ? 'bg-red-900/10 opacity-90' : '';
        return `
          <tr class="${rowClass}" id="row-${h}">
            <td class="p-4 font-bold text-white">${h}</td>
            <td class="p-4">
              <select id="st-${h}" onchange="onStatusHari('${h}')" class="bg-slate-950 text-white p-2 rounded border border-slate-700 text-xs">
                <option value="masuk" ${d.is_libur ? '' : 'selected'}>Masuk KBM</option>
                <option value="libur" ${d.is_libur ? 'selected' : ''}>Libur</option>
              </select>
            </td>
            <td class="p-4">
              <input type="time" id="in-${h}" value="${d.masuk}" class="bg-slate-950 text-white p-2 rounded border border-slate-700 w-28">
            </td>
            <td class="p-4">
              <input type="time" id="tl-${h}" value="${d.telat}" class="bg-slate-950 text-red-300 p-2 rounded border border-slate-700 w-28">
            </td>
            <td class="p-4">
              <input type="time" id="pl-${h}" value="${d.pulang}" class="bg-slate-950 text-emerald-300 p-2 rounded border border-slate-700 w-28">
            </td>
          </tr>
        `;
      }).join('');
    }

    window.onStatusHari = (hari) => {
      const st = document.getElementById(`st-${hari}`).value;
      const row = document.getElementById(`row-${hari}`);
      if(st === 'libur') row.classList.add('bg-red-900/10','opacity-90');
      else row.classList.remove('bg-red-900/10','opacity-90');
    };

    window.saveJadwal = async () => {
      const hariList = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
      const jadwal = {};
      hariList.forEach(h=>{
        const is_libur = document.getElementById(`st-${h}`).value === 'libur' ? 1 : 0;
        jadwal[h] = {
          is_libur,
          masuk: document.getElementById(`in-${h}`).value,
          telat: document.getElementById(`tl-${h}`).value,
          pulang: document.getElementById(`pl-${h}`).value,
        };
      });

      const res = await fetch(`${BASE_API}/settings_save.php`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'include',
        body: JSON.stringify({ section:'jadwal', jadwal })
      });
      const js = await res.json();
      if(!js.ok) return Swal.fire('Gagal', js.error || 'Error', 'error');

      Swal.fire('Sukses', `Jadwal tersimpan (${js.updated||0} hari)`, 'success');
      await loadSettings();
    };

    // ================== USERS (existing API) ==================
    let listUsers = [];

    window.tarikDataUser = async (showToast=false) => {
      try {
        const res = await fetch(`${BASE_API}/users_list.php`, { credentials:'include' });
        const js = await res.json();
        if(!js.ok) throw new Error(js.error || "Gagal load users");

        listUsers = js.data || [];

        document.getElementById('stat-total').textContent = listUsers.filter(u => u.role === 'siswa').length;
        document.getElementById('stat-guru').textContent  = listUsers.filter(u => u.role !== 'siswa').length;

        renderTable();
        if(showToast) Swal.fire('Sip!', 'Data user berhasil di-sync', 'success');
      } catch(e){
        console.error(e);
        Swal.fire('Error', e.message, 'error');
      }
    };

    function renderTable(){
      const tb = document.getElementById('tbody-users');
      if(!tb) return;

      const key = (document.getElementById('cariUserBox')?.value || '').toLowerCase().trim();

      const filtered = listUsers.filter(u => {
        const n = String(u.nama || '').toLowerCase();
        const un = String(u.username || '').toLowerCase();
        return !key || n.includes(key) || un.includes(key);
      }).slice(0, 500);

      if(filtered.length === 0){
        tb.innerHTML = `<tr><td colspan="5" class="p-4 text-center italic text-slate-500">Tidak ada data.</td></tr>`;
        return;
      }

      tb.innerHTML = filtered.map(u => {
        const isSuper = (u.role === 'super');
        const disableDelete = isSuper && CURRENT_ROLE !== 'super';
        return `
          <tr class="hover:bg-slate-800">
            <td class="p-3 text-white font-bold">${escapeHtml(u.nama || '-')}</td>
            <td class="p-3"><span class="text-xs font-bold uppercase text-blue-400">${escapeHtml(u.role || '-')}</span></td>
            <td class="p-3 font-mono text-xs text-emerald-300">${escapeHtml(u.username || '-')}</td>
            <td class="p-3 text-xs text-slate-400">${escapeHtml(u.kelas || '-')}</td>
            <td class="p-3 text-right flex justify-end gap-2">
              <button onclick="editUser('${escapeJs(u.username)}')" class="bg-yellow-600 hover:bg-yellow-500 text-white px-3 py-1 rounded text-xs font-bold">
                Edit
              </button>
              <button ${disableDelete ? 'disabled' : ''} onclick="hapusUser('${escapeJs(u.username)}')"
                class="${disableDelete ? 'bg-slate-700 text-slate-400 cursor-not-allowed' : 'bg-red-600 hover:bg-red-500 text-white'} px-3 py-1 rounded text-xs font-bold">
                Hapus
              </button>
            </td>
          </tr>
        `;
      }).join('');
      lucide.createIcons();
    }

    window.editUser = (username) => {
      const u = listUsers.find(x => String(x.username) === String(username));
      if(!u) return Swal.fire('Tidak ditemukan', 'User tidak ada', 'error');

      document.getElementById('e_user').value = u.username || '';
      document.getElementById('e_nama').value = u.nama || '';
      document.getElementById('e_role').value = u.role || 'siswa';
      document.getElementById('e_kelas').value = u.kelas || '';
      document.getElementById('e_pass').value = '';

      document.getElementById('modalEdit').classList.remove('opacity-0','pointer-events-none');
    };

    window.tutupModal = () => {
      document.getElementById('modalEdit').classList.add('opacity-0','pointer-events-none');
    };

    window.simpanEdit = async () => {
      const username = document.getElementById('e_user').value.trim();
      const nama = document.getElementById('e_nama').value.trim();
      const role = document.getElementById('e_role').value;
      const kelas = document.getElementById('e_kelas').value.trim() || null;
      const password = document.getElementById('e_pass').value;

      if(!username || !nama || !role) return Swal.fire('Wajib diisi', 'Username/nama/role wajib', 'warning');

      const res = await fetch(`${BASE_API}/users_save.php`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'include',
        body: JSON.stringify({ mode:'update', username, nama, role, kelas, password })
      });
      const js = await res.json();
      if(!js.ok) return Swal.fire('Gagal', js.error || 'Error', 'error');

      Swal.fire('Sukses', 'User diperbarui', 'success');
      tutupModal();
      await tarikDataUser(false);
    };

    window.tambahSiswa = async () => {
      const nama = document.getElementById('s_nama').value.trim();
      const username = document.getElementById('s_user').value.trim();
      const kelas = document.getElementById('s_kelas').value.trim();
      const password = document.getElementById('s_pass').value.trim() || username;

      if(!nama || !username || !kelas) return Swal.fire('Wajib diisi', 'Nama, NISN, Kelas wajib', 'warning');

      const res = await fetch(`${BASE_API}/users_save.php`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'include',
        body: JSON.stringify({ mode:'create', username, nama, role:'siswa', kelas, password })
      });
      const js = await res.json();
      if(!js.ok) return Swal.fire('Gagal', js.error || 'Error', 'error');

      Swal.fire('Sukses', 'Siswa ditambahkan', 'success');
      document.getElementById('s_nama').value='';
      document.getElementById('s_user').value='';
      document.getElementById('s_kelas').value='';
      await tarikDataUser(false);
      gantiTab('manage');
    };

    window.tambahGuru = async () => {
      const nama = document.getElementById('g_nama').value.trim();
      const username = document.getElementById('g_user').value.trim();
      const role = document.getElementById('g_role').value;
      const password = document.getElementById('g_pass').value.trim() || username;

      if(!nama || !username) return Swal.fire('Wajib diisi', 'Nama & NIP wajib', 'warning');

      const res = await fetch(`${BASE_API}/users_save.php`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'include',
        body: JSON.stringify({ mode:'create', username, nama, role, kelas:null, password })
      });
      const js = await res.json();
      if(!js.ok) return Swal.fire('Gagal', js.error || 'Error', 'error');

      Swal.fire('Sukses', 'Pegawai ditambahkan', 'success');
      document.getElementById('g_nama').value='';
      document.getElementById('g_user').value='';
      await tarikDataUser(false);
      gantiTab('manage');
    };

    window.hapusUser = async (username) => {
      const ok = await Swal.fire({
        title: 'Hapus user?',
        text: username,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
      });
      if(!ok.isConfirmed) return;

      const res = await fetch(`${BASE_API}/users_delete.php`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'include',
        body: JSON.stringify({ username })
      });
      const js = await res.json();
      if(!js.ok) return Swal.fire('Gagal', js.error || 'Error', 'error');

      Swal.fire('Terhapus', 'User dihapus', 'success');
      await tarikDataUser(false);
    };

    // ===================== EXCEL EXPORT/IMPORT (tetap) =====================
    window.exportExcel = (type) => {
      const wb = XLSX.utils.book_new();

      if (type === 'siswa') {
        const rows = listUsers
          .filter(u => u.role === 'siswa')
          .map(u => ({
            "Nama": u.nama || "",
            "Jenis Kelamin": "",
            "NISN": u.username || "",
            "Tempat lahir": "",
            "Tanggal Lahir": "",
            "Agama": "",
            "Rombel": u.kelas || ""
          }));

        const ws = XLSX.utils.json_to_sheet(rows, { header: ["Nama","Jenis Kelamin","NISN","Tempat lahir","Tanggal Lahir","Agama","Rombel"] });
        XLSX.utils.book_append_sheet(wb, ws, "Siswa");
        XLSX.writeFile(wb, `Export_Siswa_${new Date().toISOString().slice(0,10)}.xlsx`);
        return;
      }

      const rows = listUsers
        .filter(u => u.role !== 'siswa')
        .map(u => ({
          "NAMA": u.nama || "",
          "NIP": u.username || "",
          "Nama Kelas (Jika Wali Kelas)": u.kelas || "",
          "Guru/Pegawai Karyawan": u.role || "guru",
          "Password": ""
        }));

      const ws = XLSX.utils.json_to_sheet(rows, { header: ["NAMA","NIP","Nama Kelas (Jika Wali Kelas)","Guru/Pegawai Karyawan","Password"] });
      XLSX.utils.book_append_sheet(wb, ws, "Guru");
      XLSX.writeFile(wb, `Export_Guru_${new Date().toISOString().slice(0,10)}.xlsx`);
    };

    async function readXlsx(file){
      const buf = await file.arrayBuffer();
      const wb = XLSX.read(buf, { type: 'array' });
      const ws = wb.Sheets[wb.SheetNames[0]];
      const data = XLSX.utils.sheet_to_json(ws, { defval: "" });
      return data;
    }

    window.importExcel = async (type) => {
      const input = document.getElementById(type === 'siswa' ? 'fileSiswa' : 'fileGuru');
      const file = input?.files?.[0];
      if (!file) return Swal.fire('Pilih file dulu', 'Silakan pilih file Excel', 'warning');

      try {
        const rows = await readXlsx(file);
        if (!rows || rows.length === 0) return Swal.fire('Kosong', 'File tidak berisi data', 'warning');

        const ok = await Swal.fire({
          title: `Import ${type.toUpperCase()}?`,
          text: `Data terbaca: ${rows.length} baris. (UPSERT: create/update)`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Gas Import',
          cancelButtonText: 'Batal'
        });
        if (!ok.isConfirmed) return;

        const res = await fetch(`${BASE_API}/users_bulk_upsert.php`, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          credentials:'include',
          body: JSON.stringify({ type, rows })
        });
        const js = await res.json();
        if (!js.ok) throw new Error(js.error || 'Import gagal');

        await tarikDataUser(false);

        Swal.fire('Sukses', `Created: ${js.created} | Updated: ${js.updated} | Skipped: ${js.skipped}`, 'success');
      } catch (e) {
        console.error(e);
        Swal.fire('Error', e.message, 'error');
      } finally {
        input.value = '';
      }
    };

    // ================== MAPEL API ==================
    async function loadMapel() {
      const list = document.getElementById('list-mapel');
      if (!list) return;
      list.innerHTML = '<li class="text-center text-slate-500 italic">Memuat...</li>';

      try {
        const res = await fetch(`${BASE_API}/mapel_list.php`, { credentials: 'include' });
        const js = await res.json();
        if (!js.ok) throw new Error(js.error || 'Gagal load mapel');

        const data = js.data || [];
        if (data.length === 0) {
          list.innerHTML = '<li class="text-center text-slate-500 italic">Belum ada mapel</li>';
          return;
        }

        list.innerHTML = data.map(m => `
          <li class="flex justify-between bg-slate-800 p-2 rounded border border-slate-700">
            <span class="text-white font-bold">${escapeHtml(m.nama_mapel || '')}</span>
            <button onclick="hapusMapel(${Number(m.id)})" class="text-red-500 hover:text-red-400">
              <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
          </li>
        `).join('');
        lucide.createIcons();
      } catch (e) {
        console.error(e);
        list.innerHTML = '<li class="text-center text-red-400">Gagal memuat data mapel</li>';
      }
    }

    window.tambahMapel = async () => {
      const inp = document.getElementById('inputNamaMapel');
      const nama_mapel = (inp?.value || '').trim();
      if (!nama_mapel) return Swal.fire('Kosong', 'Nama mapel tidak boleh kosong', 'warning');

      try {
        const res = await fetch(`${BASE_API}/mapel_save.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ nama_mapel })
        });
        const js = await res.json();
        if (!js.ok) return Swal.fire('Gagal', js.error || 'Error', 'error');

        inp.value = '';
        Swal.fire('Sukses', 'Mapel ditambahkan', 'success');
        loadMapel();
      } catch (e) {
        Swal.fire('Error', e.message, 'error');
      }
    };

    window.hapusMapel = async (id) => {
      const ok = await Swal.fire({
        title: 'Hapus mapel?',
        text: 'ID: ' + id,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
      });
      if (!ok.isConfirmed) return;

      try {
        const res = await fetch(`${BASE_API}/mapel_delete.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ id })
        });
        const js = await res.json();
        if (!js.ok) return Swal.fire('Gagal', js.error || 'Error', 'error');

        Swal.fire('Terhapus', 'Mapel dihapus', 'success');
        loadMapel();
      } catch (e) {
        Swal.fire('Error', e.message, 'error');
      }
    };

    

// ---------- BACKUP & MIGRASI (Firebase → ZIP → MySQL) ----------
function migLog(msg){
  const el = document.getElementById('mig-log');
  if(!el) return;
  const now = new Date().toLocaleTimeString();
  el.textContent = `[${now}] ${msg}\n` + el.textContent;
}

function migDownload(filename, blob){
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(()=>URL.revokeObjectURL(url), 1500);
}

function migCleanValue(v){
  if(v === null || v === undefined) return null;

  // Firestore Timestamp (compat)
  if (typeof firebase !== 'undefined' && firebase.firestore && v instanceof firebase.firestore.Timestamp) {
    return { __type: 'timestamp', iso: v.toDate().toISOString() };
  }

  if (Array.isArray(v)) return v.map(migCleanValue);
  if (typeof v === 'object') {
    const out = {};
    for (const k of Object.keys(v)) out[k] = migCleanValue(v[k]);
    return out;
  }
  return v;
}

async function migFetchCollection(db, collectionName, fromDate, toDate){
  let ref = db.collection(collectionName);

  // sebagian koleksi pakai field tanggal string (YYYY-MM-DD) → bisa di-range
  const canFilterTanggal = ['absensi','jurnal_guru','refleksi_siswa'].includes(collectionName);
  if (canFilterTanggal && fromDate && toDate) {
    ref = ref.where('tanggal','>=',fromDate).where('tanggal','<=',toDate);
  }

  const snap = await ref.get();
  const arr = [];
  snap.forEach(doc => {
    arr.push({ id: doc.id, data: migCleanValue(doc.data()) });
  });
  return arr;
}

async function migMakeZip(){
  if (typeof JSZip === 'undefined') throw new Error('JSZip belum termuat.');
  if (typeof firebase === 'undefined' || !firebase.initializeApp) throw new Error('Firebase compat belum termuat.');

  const school = (document.getElementById('mig-school')?.value || 'DEFAULT').trim().toUpperCase();
  const fromDate = document.getElementById('mig-from')?.value || '';
  const toDate = document.getElementById('mig-to')?.value || '';

  const useSmansakra = !!document.getElementById('src-smansakra')?.checked;
  const useSiganteng = !!document.getElementById('src-siganteng')?.checked;

  if(!useSmansakra && !useSiganteng) throw new Error('Pilih minimal 1 sumber Firebase.');

  const firebaseConfigSmansakra = {
    apiKey: "AIzaSyCzFQ0tRxjB4hG8w2YOT0rr3p1yRZKjnnE",
    authDomain: "edugate-smansakra.firebaseapp.com",
    projectId: "edugate-smansakra",
    storageBucket: "edugate-smansakra.appspot.com",
    messagingSenderId: "181623417434",
    appId: "1:181623417434:web:fcac951e8a8d6ed0f1b0d7"
  };

  const firebaseConfigSiganteng = {
    apiKey: "AIzaSyA7Sg8bqj6R9k0H0mSxF0HcG3rH2oWwV6o",
    authDomain: "siganteng-absensi.firebaseapp.com",
    projectId: "siganteng-absensi",
    storageBucket: "siganteng-absensi.appspot.com",
    messagingSenderId: "1079081854492",
    appId: "1:1079081854492:web:79a0372dd97eb2d1328c55",
    measurementId: "G-MEC5XLRL3C",
    databaseURL: "https://siganteng-absensi-default-rtdb.firebaseio.com"
  };

  const sources = [];
  if (useSmansakra) {
    sources.push({
      label: 'edugate-smansakra',
      config: firebaseConfigSmansakra,
      projectId: 'edugate-smansakra',
      collections: ['users','absensi','dispensasi','hari_libur']
    });
  }
  if (useSiganteng) {
    sources.push({
      label: 'siganteng-absensi',
      config: firebaseConfigSiganteng,
      projectId: 'siganteng-absensi',
      collections: ['jurnal_guru','refleksi_siswa']
    });
  }

  migLog('Mulai backup...');

  const zip = new JSZip();
  const manifest = {
    school_code: school,
    exported_at: new Date().toISOString(),
    filters: { from: fromDate || null, to: toDate || null },
    sources: sources.map(s => ({ projectId: s.projectId, collections: s.collections }))
  };
  zip.file('manifest.json', JSON.stringify(manifest, null, 2));

  for (const src of sources) {
    migLog(`Connect Firebase: ${src.projectId}`);

    // gunakan nama app unik biar tidak tabrakan
    const appName = 'mig_' + src.projectId;
    let app;
    try {
      app = firebase.app(appName);
    } catch(e){
      app = firebase.initializeApp(src.config, appName);
    }
    const db = firebase.firestore(app);

    for (const col of src.collections) {
      migLog(`Ambil koleksi: ${src.projectId}/${col} ...`);
      const docs = await migFetchCollection(db, col, fromDate, toDate);
      zip.file(`data/${src.projectId}/${col}.json`, JSON.stringify(docs));
      migLog(`✓ ${col}: ${docs.length} dokumen`);
    }
  }

  migLog('Menyusun ZIP...');
  const blob = await zip.generateAsync({ type: 'blob', compression: 'DEFLATE', compressionOptions: { level: 6 } });
  migLog('ZIP siap.');

  return { blob, school_code: school };
}

async function migrasiDownloadZip(){
  try {
    const { blob, school_code } = await migMakeZip();
    const fname = `backup_${school_code}_${new Date().toISOString().slice(0,19).replace(/[:T]/g,'-')}.zip`;
    migDownload(fname, blob);
  } catch(e){
    migLog('ERROR: ' + (e?.message || e));
    alert(e?.message || e);
  }
}

async function migUploadZip(blob, school_code){
  const fd = new FormData();
  fd.append('school_code', school_code);
  fd.append('zipfile', blob, 'backup.zip');

  migLog('Upload ZIP ke server...');

  const res = await fetch(`${BASE_API}/migrate_import.php`, {
    method: 'POST',
    body: fd,
    credentials: 'same-origin'
  });
  const data = await res.json().catch(()=>null);
  if (!res.ok || !data || data.ok !== true) {
    const msg = (data && data.error) ? data.error : `HTTP ${res.status}`;
    throw new Error(msg);
  }
  return data;
}

async function migrasiBuatDanImport(){
  try {
    const { blob, school_code } = await migMakeZip();
    const result = await migUploadZip(blob, school_code);
    migLog('IMPORT OK! batch_id=' + result.batch_id);
    migLog('Ringkasan: ' + JSON.stringify(result.counts, null, 2));
    alert('Import selesai! Cek log.');
  } catch(e){
    migLog('ERROR: ' + (e?.message || e));
    alert(e?.message || e);
  }
}

async function migrasiImportZip(){
  try {
    const input = document.getElementById('mig-zip');
    const file = input?.files?.[0];
    if(!file) throw new Error('Pilih file ZIP dulu.');

    const school = (document.getElementById('mig-school')?.value || 'DEFAULT').trim().toUpperCase();

    const fd = new FormData();
    fd.append('school_code', school);
    fd.append('zipfile', file);

    migLog('Upload & import...');

    const res = await fetch(`${BASE_API}/migrate_import.php`, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    });

    const data = await res.json().catch(()=>null);
    if (!res.ok || !data || data.ok !== true) {
      const msg = (data && data.error) ? data.error : `HTTP ${res.status}`;
      throw new Error(msg);
    }

    migLog('IMPORT OK! batch_id=' + data.batch_id);
    migLog('Ringkasan: ' + JSON.stringify(data.counts, null, 2));
    alert('Import selesai! Cek log.');

  } catch(e){
    migLog('ERROR: ' + (e?.message || e));
    alert(e?.message || e);
  }
}

// ---------- LOGOUT ----------
    window.logout = () => {
      window.location.href = (location.pathname.includes('/app/')) ? '/logout.php' : '/logout.php';
    };

    // auto load
    document.addEventListener('DOMContentLoaded', async () => {
      try{
        await loadSettings();
      }catch(e){
        console.error(e);
        Swal.fire('Settings Error', e.message, 'error');
      }
      tarikDataUser(false);
      loadMapel();
    });
  </script>
</body>
</html>

