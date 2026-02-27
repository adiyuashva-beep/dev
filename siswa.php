<?php
// ================================
//  SISWA.PHP (FULL, 1 FILE)
//  - Self-contained UI + API endpoint via ?api=...
//  - Sesuai DB kamu: absensi pakai (tanggal, username)
// ================================

require __DIR__ . "/auth/guard.php";
require_login(['siswa','admin','super','bk','kesiswaan','kurikulum','guru']); // biar gampang test, boleh nanti dipersempit ke ['siswa'] aja

// DB
require __DIR__ . "/config/database.php"; // harus mendefinisikan $pdo (PDO)

// Tenant / sekolah aktif
$sid = (int)school_id();
if ($sid <= 0) {
  // fallback aman: kalau tenant belum resolve, kembali ke login
  header("Location: index.php");
  exit;
}

// Session user
$user = $_SESSION['user'] ?? [];
$username = $user['username'] ?? ($user['nisn'] ?? '');
$nama     = $user['name'] ?? ($user['nama'] ?? ($user['nama_lengkap'] ?? 'Siswa'));
$role     = $user['role'] ?? 'siswa';
$kelas    = $user['kelas'] ?? ($user['rombel'] ?? '-');

if (!$username) {
  // fallback kalau session belum kebentuk rapi
  header("Location: index.php");
  exit;
}

function json_out($arr, $code=200){
  http_response_code($code);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function indo_hari($n){ // date('N') => 1..7
  $map = [1=>"Senin",2=>"Selasa",3=>"Rabu",4=>"Kamis",5=>"Jumat",6=>"Sabtu",7=>"Minggu"];
  return $map[(int)$n] ?? "Senin";
}

function ensure_dir($path){
  if (!is_dir($path)) @mkdir($path, 0775, true);
}

function save_base64_jpg($dataUrl, $destDir, $prefix){
  // Accept "data:image/jpeg;base64,..." atau base64 mentah
  if (!$dataUrl) return null;

  $base64 = $dataUrl;
  if (preg_match('#^data:image/\w+;base64,#i', $dataUrl)) {
    $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $dataUrl);
  }
  $bin = base64_decode($base64);
  if ($bin === false) return null;

  ensure_dir($destDir);
  $name = $prefix . "_" . date("His") . "_" . bin2hex(random_bytes(4)) . ".jpg";
  $full = rtrim($destDir,'/') . "/" . $name;
  file_put_contents($full, $bin);
  return $name; // return filename
}

function col_exists(PDO $pdo, $table, $col){
  $st = $pdo->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
  $st->execute([$table, $col]);
  return ((int)$st->fetchColumn()) > 0;
}

function get_settings(PDO $pdo, int $sid){
  // pengaturan_sekolah per sekolah (school_id)
  $cfg = [
    "mode_bebas_pulang" => 0,
    "pesan_bebas_pulang" => "",
    "mode_gps" => 1,
    "radius_m" => 150,
    "lokasi_lat" => -7.6739830,
    "lokasi_lng" => 109.6319560,
    "akses_siswa" => 0,
    "akses_guru" => 0,
    "akses_ortu" => 0,
    "akses_pejabat" => 0,
    "refleksi_ortu" => 0,
    "refleksi_guru" => 0,
  ];

  $pdo->prepare("INSERT IGNORE INTO pengaturan_sekolah (school_id) VALUES (?)")->execute([$sid]);

  $stCfg = $pdo->prepare("SELECT * FROM pengaturan_sekolah WHERE school_id=? LIMIT 1");
  $stCfg->execute([$sid]);
  $row = $stCfg->fetch(PDO::FETCH_ASSOC);
  if ($row) {
    foreach ($cfg as $k=>$v) {
      if (array_key_exists($k, $row)) $cfg[$k] = $row[$k];
    }
  }

  // jam_operasional untuk hari ini
  $hari = indo_hari(date('N'));
  $today = [
    "hari" => $hari,
    "is_libur" => 0,
    "masuk" => "07:00",
    "telat" => "07:15",
    "pulang" => "15:30",
  ];

  $st = $pdo->prepare("SELECT * FROM jam_operasional WHERE school_id=? AND hari=? LIMIT 1");
  $st->execute([$sid, $hari]);
  $jr = $st->fetch(PDO::FETCH_ASSOC);
  if ($jr) {
    if (isset($jr['is_libur'])) $today['is_libur'] = (int)$jr['is_libur'];
    if (isset($jr['masuk']))    $today['masuk']    = substr((string)$jr['masuk'], 0, 5);
    if (isset($jr['telat']))    $today['telat']    = substr((string)$jr['telat'], 0, 5);
    if (isset($jr['pulang']))   $today['pulang']   = substr((string)$jr['pulang'], 0, 5);
  }

  return ["ok"=>true, "data"=>$cfg, "today"=>$today];
}

function get_absensi_today(PDO $pdo, int $sid, $username){
  $st = $pdo->prepare("SELECT * FROM absensi WHERE school_id = ? AND tanggal = CURDATE() AND username = ? LIMIT 1");
  $st->execute([$sid, $username]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ?: null;
}

function upsert_absensi(PDO $pdo, int $sid, $payload){
  // payload keys: username,nama,kelas,status_terakhir,jam_masuk,jam_pulang,foto_masuk,foto_pulang,lokasi_masuk
  $sql = "
    INSERT INTO absensi (school_id, tanggal, username, nama, kelas, status_terakhir, jam_masuk, jam_pulang, foto_masuk, foto_pulang, lokasi_masuk)
    VALUES (:sid, CURDATE(), :username, :nama, :kelas, :status_terakhir, :jam_masuk, :jam_pulang, :foto_masuk, :foto_pulang, :lokasi_masuk)
    ON DUPLICATE KEY UPDATE
      nama = VALUES(nama),
      kelas = VALUES(kelas),
      status_terakhir = VALUES(status_terakhir),
      jam_masuk = COALESCE(VALUES(jam_masuk), jam_masuk),
      jam_pulang = COALESCE(VALUES(jam_pulang), jam_pulang),
      foto_masuk = COALESCE(VALUES(foto_masuk), foto_masuk),
      foto_pulang = COALESCE(VALUES(foto_pulang), foto_pulang),
      lokasi_masuk = COALESCE(VALUES(lokasi_masuk), lokasi_masuk),
      updated_at = CURRENT_TIMESTAMP
  ";
  $st = $pdo->prepare($sql);
  $st->execute([
    ":sid" => $sid,
    ":username" => $payload['username'],
    ":nama" => $payload['nama'],
    ":kelas" => $payload['kelas'],
    ":status_terakhir" => $payload['status_terakhir'],
    ":jam_masuk" => $payload['jam_masuk'],
    ":jam_pulang" => $payload['jam_pulang'],
    ":foto_masuk" => $payload['foto_masuk'],
    ":foto_pulang" => $payload['foto_pulang'],
    ":lokasi_masuk" => $payload['lokasi_masuk'],
  ]);
}

// ================================
//  API ROUTER (?api=...)
// ================================
if (isset($_GET['api'])) {
  $api = $_GET['api'];

  if ($api === 'settings') {
    json_out(get_settings($pdo, $sid));
  }

  if ($api === 'status') {
    $row = get_absensi_today($pdo, $sid, $username);
    json_out(["ok"=>true, "data"=>$row]);
  }

  if ($api === 'absen' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents("php://input");
    $body = json_decode($raw, true);
    if (!is_array($body)) json_out(["ok"=>false, "error"=>"Body JSON tidak valid"], 400);

    $tipe = strtolower(trim($body['tipe'] ?? ''));
    $ket  = trim($body['keterangan'] ?? 'Hadir');
    $foto = $body['foto'] ?? null; // dataUrl
    $lok  = trim($body['lokasi'] ?? ''); // "lat,lng"

    $allowed = ['masuk','pulang','izin_keluar','kembali','sakit','izin'];
    if (!in_array($tipe, $allowed, true)) json_out(["ok"=>false, "error"=>"Tipe absen tidak valid"], 400);

    // Save images (optional)
    $date = date("Y-m-d");
    $baseDir = __DIR__ . "/uploads/{$sid}/absen/$date";
    $publicUploads = (strpos($_SERVER['REQUEST_URI'] ?? '', '/app/') !== false) ? "/app/uploads" : "/uploads";
    $urlBase = $publicUploads . "/{$sid}/absen/$date";

    $fotoMasukUrl = null;
    $fotoPulangUrl = null;

    if ($foto && $foto !== '-') {
      if ($tipe === 'masuk' || $tipe === 'sakit' || $tipe === 'izin') {
        $fn = save_base64_jpg($foto, $baseDir, $username . "_MASUK");
        if ($fn) $fotoMasukUrl = $urlBase . "/" . $fn;
      } else if ($tipe === 'pulang') {
        $fn = save_base64_jpg($foto, $baseDir, $username . "_PULANG");
        if ($fn) $fotoPulangUrl = $urlBase . "/" . $fn;
      }
    }

    $statusMap = [
      "masuk" => "Masuk",
      "pulang" => "Pulang",
      "izin_keluar" => "Izin Keluar",
      "kembali" => "Kembali",
      "sakit" => "Sakit",
      "izin" => "Izin",
    ];
    $status = $statusMap[$tipe];

    $jamMasuk = null;
    $jamPulang = null;

    if ($tipe === 'masuk' || $tipe === 'sakit' || $tipe === 'izin') $jamMasuk = date("Y-m-d H:i:s");
    if ($tipe === 'pulang') $jamPulang = date("Y-m-d H:i:s");

    $payload = [
      "username" => $username,
      "nama" => $nama,
      "kelas" => $kelas,
      "status_terakhir" => $status . ($ket ? " - " . $ket : ""),
      "jam_masuk" => $jamMasuk,
      "jam_pulang" => $jamPulang,
      "foto_masuk" => $fotoMasukUrl,
      "foto_pulang" => $fotoPulangUrl,
      "lokasi_masuk" => $lok ?: null,
    ];

    try {
      upsert_absensi($pdo, $sid, $payload);
      $row = get_absensi_today($pdo, $sid, $username);
      json_out(["ok"=>true, "data"=>$row]);
    } catch (Throwable $e) {
      json_out(["ok"=>false, "error"=>$e->getMessage()], 500);
    }
  }

  if ($api === 'change_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents("php://input");
    $body = json_decode($raw, true);
    if (!is_array($body)) json_out(["ok"=>false, "error"=>"Body JSON tidak valid"], 400);

    $new = trim($body['password'] ?? '');
    if (strlen($new) < 6) json_out(["ok"=>false, "error"=>"Password minimal 6 karakter"], 400);

    // Update users.password (plain) — sesuai sistem kamu sekarang
    try {
      $hash = password_hash($new, PASSWORD_BCRYPT);
      $st = $pdo->prepare("UPDATE users SET password_hash=?, password=NULL WHERE school_id=? AND username=? LIMIT 1");
      $st->execute([$hash, $sid, $username]);
      $_SESSION['user']['password_hash'] = $hash;
      json_out(["ok"=>true]);
    } catch (Throwable $e) {
      json_out(["ok"=>false, "error"=>$e->getMessage()], 500);
    }
  }

  json_out(["ok"=>false, "error"=>"API tidak ditemukan"], 404);
}

// ================================
//  HTML UI
// ================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>EduGate Siswa</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; -webkit-tap-highlight-color: transparent; }
    .hidden-page { display: none !important; }
    .hide-scroll::-webkit-scrollbar { display:none; }
    .fade-in { animation: fadeIn .35s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes fadeIn { from { opacity:0; transform: translateY(18px);} to {opacity:1; transform: translateY(0);} }
    .story-ring { background: linear-gradient(45deg,#f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); padding:3px; border-radius:50%; }
    .camera-overlay { background: rgba(0,0,0,.95); }
  </style>
</head>

<body class="text-slate-800 h-screen flex flex-col overflow-hidden bg-slate-50">

  <main id="main-container" class="flex-1 overflow-y-auto hide-scroll relative">
    <!-- DASHBOARD -->
    <div id="page-dashboard" class="p-6 fade-in min-h-screen pb-32">
      <div class="flex flex-col items-center justify-center mb-6 mt-4">
        <div class="story-ring w-28 h-28 shadow-xl shadow-pink-500/20">
          <img id="dash-foto" src="https://ui-avatars.com/api/?background=random&color=fff&name=<?= urlencode($nama) ?>"
               class="w-full h-full rounded-full object-cover border-4 border-white bg-white">
        </div>
        <div class="text-center mt-3">
          <h2 class="text-2xl font-black text-slate-800 leading-tight" id="dash-nama"><?= htmlspecialchars($nama) ?></h2>
          <span id="dash-kelas" class="text-[10px] font-black bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full uppercase tracking-wider mt-1 inline-block">
            <?= htmlspecialchars($kelas) ?>
          </span>
        </div>
      </div>

      <div class="bg-slate-900 rounded-[2rem] p-6 text-white shadow-2xl mb-6 relative overflow-hidden">
        <div class="relative z-10">
          <p class="text-slate-400 text-[10px] font-bold uppercase tracking-[0.2em] mb-1">Status Hari Ini</p>
          <h1 class="text-3xl font-black tracking-tighter uppercase text-yellow-400" id="hero-status">LOADING...</h1>
          <div class="mt-4 flex gap-4 text-xs font-mono text-slate-300">
            <div>IN: <span id="info-jam-masuk" class="text-white font-bold">--:--</span></div>
            <div>OUT: <span id="info-jam-pulang" class="text-white font-bold">--:--</span></div>
          </div>
          <div class="mt-3 text-[10px] text-slate-400 font-bold uppercase tracking-widest">
            NISN: <span class="text-white font-mono"><?= htmlspecialchars($username) ?></span>
          </div>
        </div>
        <div class="absolute -top-6 -right-6 w-32 h-32 bg-blue-600 rounded-full blur-[50px] opacity-40"></div>
      </div>

      <div class="grid grid-cols-4 gap-2" id="wadah-menu"></div>
    </div>

    <!-- ABSEN -->
    <div id="page-absen" class="hidden-page p-6 fade-in h-full bg-white flex flex-col">
      <button onclick="kembali()" class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center mb-4">
        <i data-lucide="chevron-left" class="w-5 h-5 text-slate-600"></i>
      </button>

      <div class="text-center mb-6">
        <h1 class="font-black text-5xl text-slate-800 tracking-tighter" id="jamDigital">00:00</h1>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1" id="tanggalDigital">...</p>
        <p class="text-[10px] text-slate-400 font-bold mt-2" id="infoJadwal">Jadwal: -</p>
      </div>

      <div class="relative w-full h-48 rounded-[2rem] overflow-hidden border-4 border-slate-50 shadow-inner mb-6 shrink-0">
        <div id="mapMini" class="h-full w-full z-0"></div>
        <div class="absolute bottom-2 left-0 right-0 text-center">
          <span class="bg-black/60 text-white text-[10px] px-3 py-1 rounded-full backdrop-blur-sm">
            Radius Sekolah
          </span>
        </div>
      </div>

      <div class="flex-1 overflow-y-auto space-y-3">
        <button id="btnMasuk" onclick="klikMasuk()" class="w-full bg-blue-600 text-white p-5 rounded-[2rem] shadow-xl flex items-center gap-4 active:scale-95 transition">
          <div class="bg-white/20 p-3 rounded-2xl"><i data-lucide="scan-face" class="w-8 h-8"></i></div>
          <div class="text-left">
            <span class="block text-[10px] text-blue-200 uppercase font-bold">Sudah Sampai?</span>
            <span class="font-black text-2xl uppercase">ABSEN MASUK</span>
          </div>
        </button>

        <div id="groupDiSekolah" class="hidden space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <button id="btnIzinKeluar" onclick="klikIzinKeluar()" class="bg-orange-50 text-orange-600 p-4 rounded-[1.5rem] font-bold text-xs flex flex-col items-center gap-2 border border-orange-100 active:scale-95 transition">
              <i data-lucide="door-open" class="w-6 h-6"></i> Izin Keluar
            </button>
            <button id="btnKembali" onclick="klikKembali()" class="hidden bg-emerald-600 text-white p-4 rounded-[1.5rem] font-bold text-xs flex flex-col items-center gap-2 shadow-lg active:scale-95 transition">
              <i data-lucide="map-pin" class="w-6 h-6"></i> Saya Kembali
            </button>
          </div>

          <button id="btnPulang" onclick="klikPulang()" class="w-full bg-slate-900 text-white p-5 rounded-[2rem] shadow-xl flex items-center gap-4 active:scale-95 transition">
            <div class="bg-white/10 p-3 rounded-2xl"><i data-lucide="log-out" class="w-8 h-8"></i></div>
            <div class="text-left">
              <span class="block text-[10px] text-slate-400 uppercase font-bold">Selesai KBM?</span>
              <span class="font-black text-2xl uppercase tracking-tighter">ABSEN PULANG</span>
            </div>
          </button>
        </div>

        <button onclick="bukaKartu()" class="w-full mt-4 bg-white border border-slate-200 text-slate-500 p-4 rounded-[1.5rem] font-bold text-xs flex items-center justify-center gap-2 uppercase tracking-widest active:scale-95">
          <i data-lucide="qr-code" class="w-4 h-4"></i> Kartu Digital
        </button>
      </div>
    </div>

    <!-- IZIN/SAKIT -->
    <div id="page-sakit" class="hidden-page p-6 fade-in h-full bg-white flex flex-col overflow-y-auto">
      <button onclick="kembali()" class="mb-4 w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center">
        <i data-lucide="chevron-left" class="w-5 h-5 text-slate-600"></i>
      </button>

      <h2 class="text-2xl font-black text-slate-800 mb-6 uppercase tracking-tighter">Lapor Berhalangan</h2>

      <div class="grid grid-cols-2 gap-4 mb-6">
        <button onclick="pilihJenisIzin('sakit')" class="p-6 rounded-2xl border-2 border-slate-100 hover:border-blue-500 hover:bg-blue-50 transition group text-center">
          <i data-lucide="thermometer" class="w-8 h-8 mx-auto mb-2 text-slate-400 group-hover:text-blue-500"></i>
          <span class="font-bold text-slate-600 group-hover:text-blue-600">SAKIT</span>
        </button>
        <button onclick="pilihJenisIzin('izin')" class="p-6 rounded-2xl border-2 border-slate-100 hover:border-orange-500 hover:bg-orange-50 transition group text-center">
          <i data-lucide="mail" class="w-8 h-8 mx-auto mb-2 text-slate-400 group-hover:text-orange-500"></i>
          <span class="font-bold text-slate-600 group-hover:text-orange-600">IZIN</span>
        </button>
      </div>

      <div id="areaUploadFoto" class="hidden space-y-4">
        <div>
          <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Alasan Lengkap</label>
          <textarea id="ketIzin" rows="3" class="w-full p-4 rounded-2xl bg-slate-50 font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500" placeholder="Contoh: Demam tinggi..."></textarea>
        </div>

        <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center">
          <img id="previewBukti" src="" class="hidden w-full h-40 object-cover rounded-xl mb-4">
          <p class="text-xs text-slate-400 mb-3">Upload Bukti (opsional)</p>
          <div class="flex gap-2 justify-center">
            <button onclick="bukaCam('BUKTI')" class="bg-slate-900 text-white px-4 py-2 rounded-xl font-bold text-xs flex items-center gap-2">
              <i data-lucide="camera" class="w-4 h-4"></i> Foto
            </button>
          </div>
        </div>

        <button onclick="kirimLaporanIzin()" class="w-full bg-blue-600 text-white p-4 rounded-2xl font-bold text-sm uppercase tracking-widest shadow-lg shadow-blue-500/30 active:scale-95 transition">
          Kirim Laporan
        </button>
      </div>
    </div>

    <!-- PROFIL -->
    <div id="page-profil" class="hidden-page p-6 fade-in h-full bg-white overflow-y-auto">
      <button onclick="kembali()" class="mb-4 w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center">
        <i data-lucide="chevron-left" class="w-5 h-5 text-slate-600"></i>
      </button>

      <h2 class="text-2xl font-black text-slate-800 mb-6 uppercase">Profil & Akun</h2>

      <div class="bg-slate-50 p-6 rounded-[2rem] border border-slate-100 space-y-4 mb-6">
        <h4 class="font-bold text-slate-700 text-sm flex items-center gap-2 uppercase tracking-tighter">
          <i data-lucide="lock" class="w-4 h-4 text-rose-600"></i> Ganti Password
        </h4>
        <input type="password" id="inputPassBaru" class="w-full p-4 rounded-2xl border border-slate-200 font-bold text-slate-700 outline-none" placeholder="Password Baru...">
        <button onclick="gantiPassword()" class="w-full bg-rose-600 text-white py-3 rounded-xl font-bold text-sm shadow-lg active:scale-95 transition">
          Simpan Password
        </button>
      </div>

      <button onclick="logout()" class="w-full text-red-500 font-bold bg-white py-4 rounded-2xl border border-red-100 uppercase text-xs tracking-widest">
        Logout Akun
      </button>
    </div>
  </main>

  <!-- MODAL KAMERA -->
  <div id="modalKamera" class="hidden fixed inset-0 z-50 camera-overlay flex flex-col items-center justify-center fade-in">
    <div class="absolute top-0 w-full p-4 flex justify-between items-center z-20">
      <span class="bg-black/50 text-white px-3 py-1 rounded-full text-xs font-bold backdrop-blur">KAMERA</span>
      <button onclick="tutupKamera()" class="text-white"><i data-lucide="x" class="w-8 h-8"></i></button>
    </div>
    <div class="w-full h-full overflow-hidden">
      <video id="videoStream" autoplay playsinline muted class="w-full h-full object-cover transform -scale-x-100"></video>
      <canvas id="canvasFoto" class="hidden"></canvas>
    </div>
    <div class="absolute bottom-10 z-20">
      <button onclick="jepretFoto()" class="w-20 h-20 rounded-full border-[6px] border-white flex items-center justify-center active:scale-90 transition shadow-2xl bg-transparent">
        <div class="w-16 h-16 bg-white rounded-full"></div>
      </button>
    </div>
  </div>

  <!-- MODAL KARTU -->
  <div id="modalKartu" class="hidden fixed inset-0 z-50 bg-slate-900/95 backdrop-blur-md flex items-center justify-center p-6 fade-in">
    <div class="bg-white p-8 rounded-[2.5rem] w-full max-w-xs text-center shadow-2xl relative">
      <h3 class="font-black text-slate-800 text-xl mb-4 uppercase italic">Kartu Pelajar</h3>
      <div class="bg-slate-50 p-4 rounded-3xl border-2 border-dashed border-slate-200 mb-6 inline-block">
        <div id="qrcode"></div>
      </div>
      <h2 class="font-black text-slate-800 text-lg leading-tight mb-1 uppercase tracking-tighter"><?= htmlspecialchars($nama) ?></h2>
      <p class="font-mono text-slate-600 font-bold text-xs tracking-widest"><?= htmlspecialchars($username) ?></p>
      <button onclick="document.getElementById('modalKartu').classList.add('hidden')" class="mt-8 text-slate-400 font-bold text-xs uppercase hover:text-red-500">Tutup</button>
    </div>
  </div>

  <!-- LOADING -->
  <div id="loadingOverlay" class="hidden fixed inset-0 z-[60] bg-white/80 backdrop-blur-sm flex flex-col items-center justify-center">
    <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4"></div>
    <h5 class="font-black text-slate-800 text-xs tracking-[0.2em] animate-pulse uppercase">Memproses...</h5>
  </div>

<script>
  lucide.createIcons();

  const USER = {
    username: <?= json_encode($username) ?>,
    nama: <?= json_encode($nama) ?>,
    kelas: <?= json_encode($kelas) ?>,
    role: <?= json_encode($role) ?>
  };

  let SETTINGS = null;
  let STATUS = null;

  let SEKOLAH_LAT = -7.6739830;
  let SEKOLAH_LNG = 109.6319560;
  let RADIUS_M = 150;
  let MODE_GPS = 1;

  let lokasiUser = null;
  let map = null;
  let markerUser = null;
  let circleSekolah = null;

  let streamKamera = null;
  let tipeFoto = "";      // MASUK / PULANG / BUKTI
  let tempFoto = null;    // dataUrl foto terakhir
  let jenisIzinAktif = ""; // sakit / izin

  function showLoading(on){
    document.getElementById('loadingOverlay').classList.toggle('hidden', !on);
  }

  function formatJam(dtStr){
    if(!dtStr) return "--:--";
    const d = new Date(dtStr.replace(' ', 'T'));
    if(isNaN(d.getTime())) return "--:--";
    return d.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
  }

  async function apiGet(name){
    const res = await fetch(`siswa.php?api=${name}`, {credentials:'include'});
    return await res.json();
  }
  async function apiPost(name, body){
    const res = await fetch(`siswa.php?api=${name}`, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'include',
      body: JSON.stringify(body)
    });
    return await res.json();
  }

  function bukaHalaman(id){
    document.querySelectorAll('main > div').forEach(d => d.classList.add('hidden-page'));
    document.getElementById(id).classList.remove('hidden-page');
  }
  function kembali(){ bukaHalaman('page-dashboard'); }

  function renderMenu(){
    const items = [
      { id:'menu-absen', l:'Presensi', i:'scan-face', c:'blue', a:()=>{ bukaHalaman('page-absen'); setTimeout(initMap, 300); } },
      { id:'menu-sakit', l:'Izin/Sakit', i:'thermometer', c:'orange', a:()=>bukaHalaman('page-sakit') },
      { id:'menu-7hebat', l:'7 Hebat', i:'sparkles', c:'emerald', a:()=>window.location.href='tujuh_hebat.php' },
      { id:'menu-kartu', l:'Kartu', i:'qr-code', c:'pink', a:()=>bukaKartu() },
      { id:'menu-profil', l:'Profil', i:'user', c:'indigo', a:()=>bukaHalaman('page-profil') },
    ];
    const w = document.getElementById('wadah-menu');
    w.innerHTML = items.map(x => `
      <button id="${x.id}" class="flex flex-col items-center gap-2 p-3 bg-white rounded-2xl border border-slate-100 shadow-sm active:scale-95 transition">
        <div class="bg-${x.c}-50 text-${x.c}-500 p-2.5 rounded-xl"><i data-lucide="${x.i}" class="w-5 h-5"></i></div>
        <span class="text-[9px] font-bold text-slate-600 uppercase">${x.l}</span>
      </button>
    `).join('');
    lucide.createIcons();

    items.forEach(x => document.getElementById(x.id).onclick = x.a);
  }

  function mulaiJam(){
    setInterval(()=>{
      document.getElementById('jamDigital').innerText = new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
      document.getElementById('tanggalDigital').innerText = new Date().toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long'});
    },1000);
  }

  function initMap(){
    if(map) return;
    map = L.map('mapMini', {zoomControl:false}).setView([SEKOLAH_LAT, SEKOLAH_LNG], 17);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    circleSekolah = L.circle([SEKOLAH_LAT, SEKOLAH_LNG], {radius: RADIUS_M, color:'red'}).addTo(map);
  }

  function updateCircle(){
    if(circleSekolah){
      circleSekolah.setLatLng([SEKOLAH_LAT, SEKOLAH_LNG]);
      circleSekolah.setRadius(RADIUS_M);
    }
    if(map) map.setView([SEKOLAH_LAT, SEKOLAH_LNG], 17);
  }

  function cekGPS(){
    if(!navigator.geolocation){
      Swal.fire('GPS tidak tersedia','Browser tidak mendukung geolocation','error');
      return;
    }
    navigator.geolocation.watchPosition(
      p=>{
        lokasiUser = {lat:p.coords.latitude, lng:p.coords.longitude};
        if(map){
          if(!markerUser) markerUser = L.marker([lokasiUser.lat, lokasiUser.lng]).addTo(map);
          else markerUser.setLatLng([lokasiUser.lat, lokasiUser.lng]);
        }
      },
      e=>{
        console.error(e);
      },
      {enableHighAccuracy:true, maximumAge:3000, timeout:10000}
    );
  }

  function validasiGPS(){
    if(!MODE_GPS) return true;
    if(!lokasiUser) return false;

    // haversine
    const toRad = d => d * Math.PI/180;
    const R = 6371000;
    const dLat = toRad(SEKOLAH_LAT - lokasiUser.lat);
    const dLng = toRad(SEKOLAH_LNG - lokasiUser.lng);
    const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lokasiUser.lat))*Math.cos(toRad(SEKOLAH_LAT))*Math.sin(dLng/2)**2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    const dist = R*c;

    return dist <= RADIUS_M;
  }

  function getTodayHHMM(){
    const d = new Date();
    const hh = String(d.getHours()).padStart(2,'0');
    const mm = String(d.getMinutes()).padStart(2,'0');
    return `${hh}:${mm}`;
  }

  function timeToMin(hhmm){
    const [h,m] = hhmm.split(':').map(Number);
    return h*60 + m;
  }

  async function refreshSettings(){
    const js = await apiGet('settings');
    if(!js.ok) throw new Error(js.error || 'Gagal load settings');
    SETTINGS = js;

    const s = js.data;
    SEKOLAH_LAT = parseFloat(s.lokasi_lat);
    SEKOLAH_LNG = parseFloat(s.lokasi_lng);
    RADIUS_M    = parseInt(s.radius_m || 150);
    MODE_GPS    = parseInt(s.mode_gps || 1);

    const t = js.today || {};
    document.getElementById('infoJadwal').innerText = `Hari ${t.hari || '-'} | Buka: ${t.masuk || '-'} | Telat: ${t.telat || '-'} | Pulang: ${t.pulang || '-'}`;

    if(map) updateCircle();

    // kalau mode event aktif dan ada pesan, tampilkan sekali
    if(parseInt(s.mode_bebas_pulang||0) === 1 && (s.pesan_bebas_pulang||'').trim()){
      Swal.fire({icon:'info', title:'Mode Event Aktif', text: s.pesan_bebas_pulang, confirmButtonColor:'#3b82f6'});
    }
  }

  async function refreshStatus(){
    const js = await apiGet('status');
    if(!js.ok) throw new Error(js.error || 'Gagal load status');
    STATUS = js.data;

    const hero = document.getElementById('hero-status');
    const jamIn = document.getElementById('info-jam-masuk');
    const jamOut = document.getElementById('info-jam-pulang');

    if(!STATUS){
      hero.innerText = "BELUM ABSEN";
      jamIn.innerText = "--:--";
      jamOut.innerText = "--:--";
      setUIState("BELUM");
      return;
    }

    const st = STATUS.status_terakhir || "BELUM ABSEN";
    hero.innerText = st.toUpperCase();

    jamIn.innerText = formatJam(STATUS.jam_masuk);
    jamOut.innerText = formatJam(STATUS.jam_pulang);

    setUIState(st);
  }

  function setUIState(statusText){
    const btnMasuk = document.getElementById('btnMasuk');
    const grp = document.getElementById('groupDiSekolah');
    const btnIzinKeluar = document.getElementById('btnIzinKeluar');
    const btnKembali = document.getElementById('btnKembali');
    const btnPulang = document.getElementById('btnPulang');
    const menuSakit = document.getElementById('menu-sakit');

    const st = (statusText||'').toLowerCase();

    // default
    btnMasuk.classList.remove('hidden');
    grp.classList.add('hidden');
    btnIzinKeluar.classList.remove('hidden');
    btnKembali.classList.add('hidden');
    btnPulang.classList.remove('hidden');
    if(menuSakit) menuSakit.classList.remove('hidden');

    if(st.includes('pulang')){
      btnMasuk.classList.add('hidden');
      grp.classList.add('hidden');
      if(menuSakit) menuSakit.classList.add('hidden');
      return;
    }

    if(st.includes('izin keluar')){
      btnMasuk.classList.add('hidden');
      grp.classList.remove('hidden');
      btnIzinKeluar.classList.add('hidden');
      btnKembali.classList.remove('hidden');
      btnPulang.classList.add('hidden');
      if(menuSakit) menuSakit.classList.add('hidden');
      return;
    }

    if(st.includes('masuk') || st.includes('sakit') || st.includes('izin')){
      btnMasuk.classList.add('hidden');
      grp.classList.remove('hidden');
      if(menuSakit) menuSakit.classList.add('hidden');
      return;
    }
  }

  // =================== KAMERA ===================
  function bukaCam(tipe){
    tipeFoto = tipe;
    document.getElementById('modalKamera').classList.remove('hidden');

    navigator.mediaDevices.getUserMedia({video:{facingMode:'user', aspectRatio:3/4}})
      .then(s=>{
        streamKamera = s;
        document.getElementById('videoStream').srcObject = s;
      })
      .catch(e=>{
        Swal.fire('Kamera gagal', e.message, 'error');
      });
  }
  function tutupKamera(){
    document.getElementById('modalKamera').classList.add('hidden');
    if(streamKamera) streamKamera.getTracks().forEach(t=>t.stop());
    streamKamera = null;
  }
  function jepretFoto(){
    const c = document.getElementById('canvasFoto');
    c.width = 480; c.height = 640;
    const ctx = c.getContext('2d');
    ctx.drawImage(document.getElementById('videoStream'), 0, 0, c.width, c.height);
    const img = c.toDataURL('image/jpeg', 0.65);
    tempFoto = img;

    if(tipeFoto === 'BUKTI'){
      document.getElementById('previewBukti').src = img;
      document.getElementById('previewBukti').classList.remove('hidden');
      tutupKamera();
      return;
    }

    tutupKamera();
    if(tipeFoto === 'MASUK') kirimAbsen('masuk', 'Hadir', img);
    if(tipeFoto === 'PULANG') kirimAbsen('pulang', 'Pulang', img);
  }

  // =================== ABSEN LOGIC ===================
  async function kirimAbsen(tipe, keterangan, foto){
    showLoading(true);
    try{
      const lokasi = lokasiUser ? `${lokasiUser.lat},${lokasiUser.lng}` : '';
      const js = await apiPost('absen', {tipe, keterangan, foto: foto || '-', lokasi});
      if(!js.ok) throw new Error(js.error || 'Gagal simpan absen');
      await refreshStatus();
      Swal.fire('Sukses', 'Tercatat: ' + tipe.toUpperCase(), 'success');
    }catch(e){
      Swal.fire('Error', e.message, 'error');
    }finally{
      showLoading(false);
    }
  }

  async function klikMasuk(){
    if(!SETTINGS) await refreshSettings();

    // cek libur + jam buka
    const t = SETTINGS.today || {};
    const isLibur = parseInt(t.is_libur||0) === 1;
    const modeEvent = parseInt(SETTINGS.data.mode_bebas_pulang||0) === 1;

    if(isLibur && !modeEvent){
      return Swal.fire({icon:'info', title:'Hari Libur', text:'Hari ini libur. Presensi masuk ditutup.', confirmButtonColor:'#3b82f6'});
    }

    if(!modeEvent && t.masuk){
      const nowMin = timeToMin(getTodayHHMM());
      const openMin = timeToMin(t.masuk);
      if(nowMin < openMin){
        return Swal.fire({icon:'info', title:'Belum waktunya 😅', text:`Presensi masuk dibuka pukul ${t.masuk}`, confirmButtonColor:'#3b82f6'});
      }
    }

    if(!validasiGPS()){
      return Swal.fire({icon:'warning', title:'Di luar radius', text:`Kamu harus berada dalam radius ${RADIUS_M}m dari titik sekolah.`, confirmButtonColor:'#f59e0b'});
    }
    bukaCam('MASUK');
  }

  function klikPulang(){
    if(!validasiGPS()){
      return Swal.fire({icon:'warning', title:'Di luar radius', text:`Kamu harus berada dalam radius ${RADIUS_M}m.`, confirmButtonColor:'#f59e0b'});
    }
    Swal.fire({
      title:'Pulang sekarang?',
      text:'Jam pulang akan tercatat.',
      icon:'question',
      showCancelButton:true,
      confirmButtonText:'Ya',
      cancelButtonText:'Batal',
      confirmButtonColor:'#ef4444'
    }).then(r=>{
      if(r.isConfirmed) bukaCam('PULANG');
    });
  }

  function klikIzinKeluar(){
    Swal.fire({ title:'Keperluan?', input:'text', showCancelButton:true, confirmButtonText:'Kirim' })
      .then(r=>{
        if(r.isConfirmed && r.value){
          kirimAbsen('izin_keluar', r.value, '-');
        }
      });
  }

  function klikKembali(){
    if(!validasiGPS()){
      return Swal.fire({icon:'warning', title:'Di luar radius', text:`Kamu harus berada dalam radius ${RADIUS_M}m.`, confirmButtonColor:'#f59e0b'});
    }
    kirimAbsen('kembali', 'Kembali ke sekolah', '-');
  }

  // =================== IZIN/SAKIT ===================
  function pilihJenisIzin(j){
    jenisIzinAktif = j;
    document.getElementById('areaUploadFoto').classList.remove('hidden');
  }

  function kirimLaporanIzin(){
    const ket = document.getElementById('ketIzin').value.trim();
    if(!jenisIzinAktif) return Swal.fire('Pilih dulu','Pilih Sakit atau Izin','warning');
    if(!ket) return Swal.fire('Wajib diisi','Tulis alasan dulu','warning');

    // foto bukti optional
    kirimAbsen(jenisIzinAktif, ket, tempFoto || '-');
    document.getElementById('ketIzin').value = '';
    tempFoto = null;
    document.getElementById('previewBukti').classList.add('hidden');
    kembali();
  }

  // =================== LAINNYA ===================
  function bukaKartu(){
    document.getElementById('modalKartu').classList.remove('hidden');
    document.getElementById('qrcode').innerHTML = '';
    new QRCode(document.getElementById('qrcode'), {text: USER.username, width: 150, height: 150});
  }

  async function gantiPassword(){
    const p = document.getElementById('inputPassBaru').value.trim();
    if(p.length < 6) return Swal.fire('Gagal','Min 6 karakter','warning');
    showLoading(true);
    try{
      const js = await apiPost('change_password', {password:p});
      if(!js.ok) throw new Error(js.error || 'Gagal update password');
      Swal.fire('Sukses','Password diubah. Silakan login ulang.','success').then(()=>logout());
    }catch(e){
      Swal.fire('Error', e.message, 'error');
    }finally{
      showLoading(false);
    }
  }

  function logout(){
    // satu pintu: logout selalu ke root /logout.php
    window.location.href = '/logout.php';
  }

  // BOOT
  document.addEventListener('DOMContentLoaded', async ()=>{
    renderMenu();
    mulaiJam();
    cekGPS();

    try{
      await refreshSettings();
      await refreshStatus();
    }catch(e){
      Swal.fire('Error', e.message, 'error');
    }
  });
</script>
</body>
</html>
