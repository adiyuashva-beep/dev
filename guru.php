<?php
require __DIR__ . '/auth/guard.php';
require_login(['guru','bk','kesiswaan','kurikulum','admin','super','staff']);

$user = $_SESSION['user'] ?? ['username'=>'','name'=>'Guru','role'=>'guru','kelas'=>null];
$name = $user['name'] ?? 'Guru';
$role = $user['role'] ?? 'guru';
$waliKelas = $user['kelas'] ?? null;

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Panel Guru - EduGate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Outfit',sans-serif;background:#0b1220;color:#e5e7eb}
    .card{background:#0f172a;border:1px solid #1f2937;border-radius:18px}
    .btn{padding:.75rem 1rem;border-radius:14px;font-weight:900}
    .inp{background:#0b1220;border:1px solid #24324a;border-radius:12px;padding:.75rem;color:#fff;width:100%}
    .label{font-size:.75rem;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;font-weight:800}
    .badge{display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .6rem;border-radius:999px;font-size:.75rem;font-weight:900;border:1px solid #334155;background:#0b1220;color:#e2e8f0}
  </style>
</head>
<body class="min-h-screen">
  <header class="max-w-6xl mx-auto px-4 py-6 flex items-center justify-between">
    <div>
      <div class="text-2xl font-black text-white tracking-tight">EduGate <span class="text-blue-400">Panel Guru</span></div>
      <div class="text-slate-400 text-sm mt-1">Login: <b class="text-white"><?= htmlspecialchars($name) ?></b> • Role: <b class="text-white"><?= htmlspecialchars($role) ?></b><?php if($waliKelas): ?> • Wali: <b class="text-white"><?= htmlspecialchars($waliKelas) ?></b><?php endif; ?></div>
    </div>
    <div class="flex gap-2">
      <a href="/" class="btn bg-slate-800 hover:bg-slate-700">Home</a>
      <a href="/logout.php" class="btn bg-red-600 hover:bg-red-500 text-white">Logout</a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 pb-12 space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <section class="card p-6 lg:col-span-1">
        <h2 class="text-lg font-black text-white flex items-center gap-2"><i data-lucide="clock" class="w-5 h-5 text-emerald-400"></i> Presensi Guru</h2>
        <p class="text-sm text-slate-400 mt-1">Masuk & pulang pakai <b>kamera</b> + <b>radius GPS sekolah</b>. Jika tidak hadir, ajukan <b>izin/dinas/cuti/sakit</b> + bukti.</p>

        <div class="mt-4">
          <div class="text-sm text-slate-300">Status hari ini:</div>
          <div id="guruStatus" class="mt-1 text-xl font-black text-white">-</div>
          <div id="guruJam" class="text-slate-400 text-sm mt-1">-</div>

          <div class="mt-3 flex flex-wrap gap-2 items-center">
            <span class="badge" id="badgeKet">Keterangan: -</span>
            <span class="badge" id="badgeCuti">Sisa cuti tahunan: -</span>
            <button id="btnLihatBuktiKet" class="btn bg-slate-800 hover:bg-slate-700 hidden">Lihat Bukti</button>
          </div>
        </div>

        <div class="mt-4">
          <div class="label">Lokasi & Radius</div>
          <div id="mapGuru" class="mt-2 w-full h-40 rounded-2xl overflow-hidden border border-slate-800"></div>
          <div id="gpsInfo" class="mt-2 text-xs text-slate-400">GPS: menunggu…</div>
        </div>

        <div class="mt-4 flex gap-2">
          <button id="btnMasuk" class="btn bg-emerald-600 hover:bg-emerald-500 text-white flex-1">Absen Masuk</button>
          <button id="btnPulang" class="btn bg-orange-600 hover:bg-orange-500 text-white flex-1">Absen Pulang</button>
        </div>

        <div class="mt-3">
          <button id="btnAjukanKet" class="btn bg-indigo-600 hover:bg-indigo-500 text-white w-full">Ajukan Izin / Dinas / Cuti / Sakit</button>
        </div>

        <div class="mt-5">
          <a href="/kiosk_webcam.php" class="btn bg-slate-800 hover:bg-slate-700 w-full block text-center">Buka Kiosk Webcam</a>
        </div>
      </section>

      <section class="card p-6 lg:col-span-2">
        <h2 class="text-lg font-black text-white flex items-center gap-2"><i data-lucide="notebook-pen" class="w-5 h-5 text-blue-400"></i> Jurnal Mengajar</h2>
        <p class="text-sm text-slate-400 mt-1">Pakai <b>JP (Jam Pelajaran)</b> mulai–selesai. Bisa lampirkan bukti foto (depan/belakang/galeri).</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <div class="label">Kelas</div>
            <input id="j_kelas" list="dlKelas" class="inp mt-1" placeholder="Pilih / ketik kelas..." />
            <datalist id="dlKelas"></datalist>
          </div>
          <div>
            <div class="label">Mapel</div>
            <input id="j_mapel" list="dlMapel" class="inp mt-1" placeholder="Pilih / ketik mapel..." />
            <datalist id="dlMapel"></datalist>
          </div>

          <div>
            <div class="label">JP Mulai (Jam ke-)</div>
            <select id="j_jp_mulai" class="inp mt-1"></select>
          </div>
          <div>
            <div class="label">JP Selesai (Jam ke-)</div>
            <select id="j_jp_selesai" class="inp mt-1"></select>
          </div>

          <div class="md:col-span-2">
            <div class="label">Materi / Kegiatan</div>
            <textarea id="j_materi" class="inp mt-1" rows="4" placeholder="Materi yang diajarkan..."></textarea>
          </div>
          <div class="md:col-span-2">
            <div class="label">Catatan (opsional)</div>
            <textarea id="j_catatan" class="inp mt-1" rows="3" placeholder="Catatan tambahan..."></textarea>
          </div>

          <div class="md:col-span-2">
            <div class="label">Bukti Foto Jurnal (opsional)</div>
            <div class="mt-2 flex flex-wrap gap-2">
              <button id="btnJFotoFront" class="btn bg-slate-800 hover:bg-slate-700">Kamera Depan</button>
              <button id="btnJFotoBack" class="btn bg-slate-800 hover:bg-slate-700">Kamera Belakang</button>
              <button id="btnJFotoGallery" class="btn bg-slate-800 hover:bg-slate-700">Ambil dari Galeri</button>
              <input id="j_foto_file" type="file" accept="image/*" multiple class="hidden" />
              <div class="text-xs text-slate-400 self-center">Max 2 foto • dikompres.</div>
            </div>
            <div id="j_fotoList" class="mt-3 grid grid-cols-2 gap-3"></div>
          </div>
        </div>

        <div class="mt-4 flex justify-end">
          <button id="btnSaveJurnal" class="btn bg-blue-600 hover:bg-blue-500 text-white">Simpan Jurnal</button>
        </div>

        <div class="mt-6 border-t border-slate-800 pt-4">
          <div class="flex items-center justify-between">
            <h3 class="font-black text-white">Riwayat Jurnal (7 hari)</h3>
            <button id="btnReloadJurnal" class="btn bg-slate-800 hover:bg-slate-700">Muat</button>
          </div>
          <div class="mt-3 overflow-x-auto border border-slate-800 rounded-xl">
            <table class="w-full text-left text-sm">
              <thead class="bg-slate-950 text-slate-400 text-xs uppercase font-black">
                <tr>
                  <th class="p-3">Tanggal</th>
                  <th class="p-3">JP</th>
                  <th class="p-3">Kelas</th>
                  <th class="p-3">Mapel</th>
                  <th class="p-3">Materi</th>
                  <th class="p-3 text-right">Foto</th>
                </tr>
              </thead>
              <tbody id="tbJurnal" class="divide-y divide-slate-800">
                <tr><td colspan="6" class="p-4 text-center text-slate-500 italic">Memuat...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <section class="card p-6">
        <h2 class="text-lg font-black text-white flex items-center gap-2"><i data-lucide="clipboard-check" class="w-5 h-5 text-emerald-400"></i> Validasi 7 Kebiasaan</h2>
        <p class="text-sm text-slate-400 mt-1">Untuk wali kelas: validasi laporan 7 kebiasaan siswa.</p>
        <div class="mt-4 flex flex-col md:flex-row gap-3 md:items-end">
          <div class="flex-1">
            <div class="label">Tanggal</div>
            <input id="v_tanggal" type="date" class="inp mt-1" />
          </div>
          <div class="flex-1">
            <div class="label">Kelas</div>
            <input id="v_kelas" class="inp mt-1" placeholder="Kelas" value="<?= htmlspecialchars($waliKelas ?: '') ?>" />
          </div>
          <button id="btnLoad7" class="btn bg-slate-800 hover:bg-slate-700">Muat</button>
        </div>
        <div class="mt-4 overflow-x-auto border border-slate-800 rounded-xl">
          <table class="w-full text-sm text-left">
            <thead class="bg-slate-950 text-slate-400 text-xs uppercase font-black">
              <tr>
                <th class="p-3">Siswa</th>
                <th class="p-3">Status</th>
                <th class="p-3">Ringkas</th>
                <th class="p-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody id="tb7" class="divide-y divide-slate-800">
              <tr><td colspan="4" class="p-4 text-center text-slate-500 italic">Silakan muat data.</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="card p-6">
        <h2 class="text-lg font-black text-white flex items-center gap-2"><i data-lucide="layers" class="w-5 h-5 text-yellow-400"></i> Tugas Tambahan</h2>
        <p class="text-sm text-slate-400 mt-1">Tugas tambahan (ekstra/KSN/dll) yang diberikan admin.</p>
        <div class="mt-4 flex justify-end">
          <button id="btnLoadTugas" class="btn bg-slate-800 hover:bg-slate-700">Muat</button>
        </div>
        <div class="mt-3 overflow-x-auto border border-slate-800 rounded-xl">
          <table class="w-full text-sm text-left">
            <thead class="bg-slate-950 text-slate-400 text-xs uppercase font-black">
              <tr>
                <th class="p-3">Tugas</th>
                <th class="p-3">Tipe</th>
                <th class="p-3">Mulai</th>
                <th class="p-3 text-right">Anggota</th>
              </tr>
            </thead>
            <tbody id="tbTugas" class="divide-y divide-slate-800">
              <tr><td colspan="4" class="p-4 text-center text-slate-500 italic">Silakan muat data.</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </main>

  <!-- MODAL KAMERA PRESENSI -->
  <div id="modalCam" class="fixed inset-0 hidden items-center justify-center bg-black/80 backdrop-blur-sm z-50">
    <div class="w-full max-w-md mx-4 card p-5 relative">
      <div class="flex items-center justify-between">
        <div class="font-black text-white" id="modalTitle">Presensi</div>
        <button id="btnCloseModal" class="btn bg-slate-800 hover:bg-slate-700">Tutup</button>
      </div>
      <div class="mt-4">
        <div class="text-xs text-slate-400">Pastikan wajah jelas & berada di area sekolah.</div>
        <div class="mt-3 rounded-2xl overflow-hidden border border-slate-800">
          <video id="video" class="w-full h-64 object-cover" autoplay playsinline></video>
          <canvas id="canvas" class="hidden"></canvas>
          <img id="preview" class="hidden w-full h-64 object-cover" alt="preview" />
        </div>
        <div class="mt-2 text-xs text-slate-400" id="modalGps">GPS: -</div>
      </div>

      <div class="mt-4 flex gap-2">
        <button id="btnSnap" class="btn bg-emerald-600 hover:bg-emerald-500 text-white flex-1">Ambil Foto</button>
        <button id="btnSubmit" class="btn bg-blue-600 hover:bg-blue-500 text-white flex-1 hidden">Kirim Presensi</button>
      </div>
    </div>
  </div>

  <!-- MODAL KAMERA JURNAL -->
  <div id="modalJurnalCam" class="fixed inset-0 hidden items-center justify-center bg-black/80 backdrop-blur-sm z-50">
    <div class="w-full max-w-md mx-4 card p-5 relative">
      <div class="flex items-center justify-between">
        <div class="font-black text-white" id="jcamTitle">Bukti Foto</div>
        <button id="btnCloseJCam" class="btn bg-slate-800 hover:bg-slate-700">Tutup</button>
      </div>
      <div class="mt-4">
        <div class="text-xs text-slate-400">Arahkan kamera ke bukti mengajar.</div>
        <div class="mt-3 rounded-2xl overflow-hidden border border-slate-800">
          <video id="jVideo" class="w-full h-64 object-cover" autoplay playsinline></video>
          <canvas id="jCanvas" class="hidden"></canvas>
        </div>
      </div>

      <div class="mt-4 flex gap-2">
        <button id="btnJCamSnap" class="btn bg-emerald-600 hover:bg-emerald-500 text-white flex-1">Ambil Foto</button>
      </div>
    </div>
  </div>

  <!-- MODAL KETERANGAN (IZIN/DINAS/CUTI/SAKIT) -->
  <div id="modalKet" class="fixed inset-0 hidden items-center justify-center bg-black/80 backdrop-blur-sm z-50">
    <div class="w-full max-w-lg mx-4 card p-5 relative">
      <div class="flex items-center justify-between">
        <div class="font-black text-white">Ajukan Izin / Dinas / Cuti / Sakit</div>
        <button id="btnCloseKet" class="btn bg-slate-800 hover:bg-slate-700">Tutup</button>
      </div>

      <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <div class="label">Jenis</div>
          <select id="k_jenis" class="inp mt-1">
            <option value="izin">Izin</option>
            <option value="sakit">Sakit</option>
            <option value="dinas_dalam_kota">Dinas (Dalam Kota)</option>
            <option value="dinas_luar_kota">Dinas (Luar Kota)</option>
            <option value="cuti_tahunan">Cuti Tahunan</option>
            <option value="cuti_sakit">Cuti Sakit</option>
            <option value="cuti_alasan_penting">Cuti Alasan Penting</option>
            <option value="cuti_besar">Cuti Besar</option>
            <option value="cuti_melahirkan">Cuti Melahirkan</option>
            <option value="lainnya">Lainnya</option>
          </select>
          <div class="text-xs text-slate-400 mt-2">
            Untuk <b>cuti tahunan</b> akan mengurangi saldo (default 12 hari/tahun).
          </div>
        </div>

        <div id="boxJumlahHari" class="hidden">
          <div class="label">Jumlah Hari (untuk CUTI)</div>
          <input id="k_jumlah_hari" type="number" min="1" value="1" class="inp mt-1" />
        </div>

        <div class="md:col-span-2">
          <div class="label">Keterangan (opsional)</div>
          <textarea id="k_keterangan" class="inp mt-1" rows="3" placeholder="Contoh: ada acara dinas, surat dokter, dll"></textarea>
        </div>

        <div class="md:col-span-2">
          <div class="label">Bukti Pendukung (PDF/JPG/PNG)</div>
          <div class="mt-2 flex flex-wrap gap-2 items-center">
            <button id="btnKetCamFront" class="btn bg-slate-800 hover:bg-slate-700">Kamera Depan</button>
            <button id="btnKetCamBack" class="btn bg-slate-800 hover:bg-slate-700">Kamera Belakang</button>
            <button id="btnKetPickFile" class="btn bg-slate-800 hover:bg-slate-700">Pilih File</button>
            <input id="k_file" type="file" accept="image/*,application/pdf" class="hidden" />
            <div class="text-xs text-slate-400">Maks 6MB</div>
          </div>

          <div id="ketPreviewWrap" class="mt-3 hidden">
            <div class="text-xs text-slate-400">Preview bukti:</div>
            <div class="mt-2 rounded-2xl overflow-hidden border border-slate-800">
              <img id="ketImgPreview" class="hidden w-full h-56 object-cover" alt="preview bukti" />
              <div id="ketPdfPreview" class="hidden p-4 text-slate-200">
                <b>PDF dipilih</b> – siap diupload.
              </div>
            </div>
            <button id="btnKetClear" class="btn bg-slate-800 hover:bg-slate-700 mt-2">Hapus Bukti</button>
          </div>
        </div>
      </div>

      <div class="mt-4 flex justify-end gap-2">
        <button id="btnSubmitKet" class="btn bg-indigo-600 hover:bg-indigo-500 text-white">Kirim Pengajuan</button>
      </div>
    </div>
  </div>

<script>
  lucide.createIcons();
  const BASE_API = '/api';
  const $ = (id)=>document.getElementById(id);

  // === UBAH MAX JP DI SINI (SMA: 12, SMP/SD: 8) ===
  const MAX_JP = 12;

  function isoToday(){
    const d = new Date();
    const pad = (n)=>String(n).padStart(2,'0');
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
  }

  async function apiGet(path){
    const res = await fetch(`${BASE_API}/${path}`, {credentials:'include'});
    const js = await res.json();
    if(!js.ok) throw new Error(js.error || 'Error');
    return js;
  }
  async function apiPost(path, body){
    const res = await fetch(`${BASE_API}/${path}`, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'include',
      body: JSON.stringify(body||{})
    });
    const js = await res.json();
    if(!js.ok) throw new Error(js.error || 'Error');
    return js;
  }
  async function apiPostForm(path, formData){
    const res = await fetch(`${BASE_API}/${path}`, {
      method:'POST',
      credentials:'include',
      body: formData
    });
    const js = await res.json();
    if(!js.ok) throw new Error(js.error || 'Error');
    return js;
  }

  function esc(s){
    return String(s??'').replace(/[&<>"']/g, m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"}[m]));
  }

  // ---------- PRESENSI GURU ----------
  let SETTINGS = null;
  let map = null, marker = null, circle = null;
  let stream = null;
  let pendingTipe = null;
  let lastPos = null;
  let lastFoto = null;

  function haversine_m(lat1,lng1,lat2,lng2){
    const R=6371000;
    const toRad=(x)=>x*Math.PI/180;
    const dLat=toRad(lat2-lat1);
    const dLng=toRad(lng2-lng1);
    const a=Math.sin(dLat/2)**2 + Math.cos(toRad(lat1))*Math.cos(toRad(lat2))*Math.sin(dLng/2)**2;
    return 2*R*Math.asin(Math.min(1, Math.sqrt(a)));
  }

  async function loadSettings(){
    const js = await apiGet('settings_public.php');
    SETTINGS = js.data || {};
    return SETTINGS;
  }

  function initMap(){
    if(map) return;
    map = L.map('mapGuru', {zoomControl:false}).setView([0,0], 17);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19}).addTo(map);
  }

  function renderSchoolCircle(){
    if(!SETTINGS) return;
    const lat = Number(SETTINGS.lokasi_lat);
    const lng = Number(SETTINGS.lokasi_lng);
    const rad = Number(SETTINGS.radius_m||150);
    if(!isFinite(lat) || !isFinite(lng)) return;
    map.setView([lat,lng], 16);
    if(circle) circle.remove();
    circle = L.circle([lat,lng], {radius: rad}).addTo(map);
  }

  function updateMarker(pos){
    if(!pos) return;
    const {lat,lng} = pos;
    if(marker) marker.remove();
    marker = L.marker([lat,lng]).addTo(map);
    map.setView([lat,lng], 17);
  }

  function getLocation(){
    return new Promise((resolve, reject)=>{
      if(!navigator.geolocation) return reject(new Error('Browser tidak mendukung GPS.'));
      navigator.geolocation.getCurrentPosition(
        (p)=>resolve({
          lat: p.coords.latitude,
          lng: p.coords.longitude,
          accuracy: p.coords.accuracy
        }),
        ()=>reject(new Error('GPS ditolak / tidak tersedia. Aktifkan lokasi & izinkan akses.')),
        {enableHighAccuracy:true, timeout:15000, maximumAge:0}
      );
    });
  }

  function humanStatus(s){
    s = String(s||'').toLowerCase();
    if(!s) return 'Belum absen';
    if(s==='masuk') return 'Masuk';
    if(s==='pulang') return 'Pulang';
    if(s==='izin') return 'Izin';
    if(s==='sakit') return 'Sakit';
    if(s==='dinas') return 'Dinas';
    if(s==='cuti') return 'Cuti';
    return s;
  }

  async function loadGuruStatus(){
    const js = await apiGet('absensi_guru_status.php');
    const d = js.data || {};
    const status = d.status_terakhir ? humanStatus(d.status_terakhir) : (d.jam_masuk ? 'Masuk' : 'Belum absen');
    $('guruStatus').textContent = status;
    const jm = d.jam_masuk ? `Masuk: ${d.jam_masuk}` : 'Masuk: -';
    const jp = d.jam_pulang ? `Pulang: ${d.jam_pulang}` : 'Pulang: -';
    $('guruJam').textContent = `${jm} • ${jp}`;
  }

  function openModal(tipe){
    pendingTipe = tipe;
    lastFoto = null;
    $('modalTitle').textContent = `Presensi ${tipe.toUpperCase()}`;
    $('preview').classList.add('hidden');
    $('btnSubmit').classList.add('hidden');
    $('btnSnap').classList.remove('hidden');
    $('modalCam').classList.remove('hidden');
    $('modalCam').classList.add('flex');
  }

  async function startCamera(){
    if(stream) stopCamera();
    stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'user'}, audio:false});
    $('video').srcObject = stream;
  }
  function stopCamera(){
    try{ stream?.getTracks()?.forEach(t=>t.stop()); }catch(e){}
    stream = null;
    $('video').srcObject = null;
  }
  function closeModal(){
    stopCamera();
    $('modalCam').classList.add('hidden');
    $('modalCam').classList.remove('flex');
  }

  function snap(){
    const video = $('video');
    const canvas = $('canvas');
    const w = video.videoWidth || 640;
    const h = video.videoHeight || 480;
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, w, h);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
    lastFoto = dataUrl;
    $('preview').src = dataUrl;
    $('preview').classList.remove('hidden');
    $('btnSubmit').classList.remove('hidden');
    $('btnSnap').classList.add('hidden');
    stopCamera();
  }

  async function doPresensi(){
    if(!pendingTipe) return;
    if(!lastPos) throw new Error('GPS belum siap.');
    if(!lastFoto) throw new Error('Foto belum diambil.');

    // cek radius client
    if(SETTINGS && Number(SETTINGS.mode_gps||1) === 1){
      const latS = Number(SETTINGS.lokasi_lat);
      const lngS = Number(SETTINGS.lokasi_lng);
      const rad = Number(SETTINGS.radius_m||150);
      const d = haversine_m(lastPos.lat, lastPos.lng, latS, lngS);
      if(isFinite(d) && d > rad){
        throw new Error(`Di luar radius sekolah (≈${Math.round(d)}m > ${rad}m).`);
      }
    }

    await apiPost('absensi_guru_action.php', {
      tipe: pendingTipe,
      foto: lastFoto,
      lat: lastPos.lat,
      lng: lastPos.lng,
      akurasi: lastPos.accuracy
    });
    closeModal();
    await loadGuruStatus();
    await loadKetStatus();
    Swal.fire('OK', 'Presensi tercatat', 'success');
  }

  async function prepareGps(){
    try{
      const pos = await getLocation();
      lastPos = pos;
      const t = `GPS: lat=${pos.lat.toFixed(6)}, lng=${pos.lng.toFixed(6)} • acc≈${Math.round(pos.accuracy||0)}m`;
      $('gpsInfo').textContent = t;
      $('modalGps').textContent = t;
      initMap();
      renderSchoolCircle();
      updateMarker(pos);
    }catch(e){
      $('gpsInfo').textContent = 'GPS: gagal (cek izin lokasi)';
      $('modalGps').textContent = 'GPS: gagal (cek izin lokasi)';
    }
  }

  // ---------- KETERANGAN ABSENSI (IZIN/DINAS/CUTI/SAKIT) ----------
  let KET_TODAY = null;
  let CUTI_SISA = null;
  let ketFile = null;        // File (pdf/image) chosen
  let ketCamDataUrl = null;  // captured image

  function jenisLabel(j){
    const m = {
      izin: 'Izin',
      sakit: 'Sakit',
      dinas_dalam_kota: 'Dinas Dalam Kota',
      dinas_luar_kota: 'Dinas Luar Kota',
      cuti_tahunan: 'Cuti Tahunan',
      cuti_sakit: 'Cuti Sakit',
      cuti_alasan_penting: 'Cuti Alasan Penting',
      cuti_besar: 'Cuti Besar',
      cuti_melahirkan: 'Cuti Melahirkan',
      lainnya: 'Lainnya',
    };
    return m[j] || j;
  }

  async function loadKetStatus(){
    try{
      const js = await apiGet('absensi_guru_ket_status.php');
      const d = js.data || {};
      KET_TODAY = d.today || null;
      CUTI_SISA = d.cuti_sisa ?? null;

      $('badgeCuti').textContent = `Sisa cuti tahunan: ${Number.isFinite(Number(CUTI_SISA)) ? CUTI_SISA : '-'}`;

      if(KET_TODAY){
        $('badgeKet').textContent = `Keterangan: ${jenisLabel(KET_TODAY.jenis)} (${KET_TODAY.status||'submitted'})`;
        if(KET_TODAY.bukti_url){
          $('btnLihatBuktiKet').classList.remove('hidden');
        }else{
          $('btnLihatBuktiKet').classList.add('hidden');
        }
        $('btnAjukanKet').textContent = 'Pengajuan sudah ada (hari ini)';
        $('btnAjukanKet').disabled = true;
        $('btnAjukanKet').classList.add('opacity-60');
      }else{
        $('badgeKet').textContent = 'Keterangan: -';
        $('btnLihatBuktiKet').classList.add('hidden');
        $('btnAjukanKet').textContent = 'Ajukan Izin / Dinas / Cuti / Sakit';
        $('btnAjukanKet').disabled = false;
        $('btnAjukanKet').classList.remove('opacity-60');
      }
    }catch(e){
      $('badgeKet').textContent = 'Keterangan: -';
      $('badgeCuti').textContent = 'Sisa cuti tahunan: -';
      $('btnLihatBuktiKet').classList.add('hidden');
    }
  }

  function openKetModal(){
    ketFile = null;
    ketCamDataUrl = null;
    $('k_keterangan').value = '';
    $('k_jumlah_hari').value = 1;
    $('ketPreviewWrap').classList.add('hidden');
    $('ketImgPreview').classList.add('hidden');
    $('ketPdfPreview').classList.add('hidden');
    $('modalKet').classList.remove('hidden');
    $('modalKet').classList.add('flex');
    toggleJumlahHari();
  }
  function closeKetModal(){
    $('modalKet').classList.add('hidden');
    $('modalKet').classList.remove('flex');
  }
  function toggleJumlahHari(){
    const j = $('k_jenis').value;
    const isCuti = j.startsWith('cuti_');
    $('boxJumlahHari').classList.toggle('hidden', !isCuti);
  }

  function showKetPreviewForFile(file){
    $('ketPreviewWrap').classList.remove('hidden');
    if(file.type === 'application/pdf'){
      $('ketPdfPreview').classList.remove('hidden');
      $('ketImgPreview').classList.add('hidden');
    }else{
      const url = URL.createObjectURL(file);
      $('ketImgPreview').src = url;
      $('ketImgPreview').classList.remove('hidden');
      $('ketPdfPreview').classList.add('hidden');
    }
  }
  function showKetPreviewForDataUrl(dataUrl){
    $('ketPreviewWrap').classList.remove('hidden');
    $('ketImgPreview').src = dataUrl;
    $('ketImgPreview').classList.remove('hidden');
    $('ketPdfPreview').classList.add('hidden');
  }
  function clearKetEvidence(){
    ketFile = null;
    ketCamDataUrl = null;
    $('ketPreviewWrap').classList.add('hidden');
    $('ketImgPreview').classList.add('hidden');
    $('ketPdfPreview').classList.add('hidden');
  }

  // camera for ket
  let ketCamStream = null;
  let ketFacing = 'user';

  function dataUrlToBlob(dataUrl){
    const [meta, b64] = dataUrl.split(',', 2);
    const mime = (meta.match(/data:(.*?);base64/)||[])[1] || 'image/jpeg';
    const bin = atob(b64 || '');
    const arr = new Uint8Array(bin.length);
    for(let i=0;i<bin.length;i++) arr[i] = bin.charCodeAt(i);
    return new Blob([arr], {type:mime});
  }

  async function openKetCamera(facing){
    ketFacing = facing || 'user';
    $('jcamTitle').textContent = `Ambil Bukti (Keterangan) • ${ketFacing==='environment' ? 'Belakang' : 'Depan'}`;
    $('modalJurnalCam').classList.remove('hidden');
    $('modalJurnalCam').classList.add('flex');
    try{
      stopKetCam();
      const constraints = { video: { facingMode: (ketFacing==='environment' ? {ideal:'environment'} : 'user') }, audio:false };
      ketCamStream = await navigator.mediaDevices.getUserMedia(constraints);
      $('jVideo').srcObject = ketCamStream;
    }catch(e){
      closeJCam(); // reuse same modal close
      throw e;
    }
  }
  function stopKetCam(){
    try{ ketCamStream?.getTracks()?.forEach(t=>t.stop()); }catch(e){}
    ketCamStream = null;
    if($('jVideo')) $('jVideo').srcObject = null;
  }
  function snapKetCam(){
    const video = $('jVideo');
    const canvas = $('jCanvas');
    const w = video.videoWidth || 640;
    const h = video.videoHeight || 480;
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, w, h);
    ketCamDataUrl = canvas.toDataURL('image/jpeg', 0.85);
    ketFile = null;
    showKetPreviewForDataUrl(ketCamDataUrl);
    stopKetCam();
    closeJCam();
  }

  async function submitKet(){
    const jenis = $('k_jenis').value;
    const ket = $('k_keterangan').value.trim();
    const isCuti = jenis.startsWith('cuti_');
    const jumlah_hari = isCuti ? Math.max(1, parseInt($('k_jumlah_hari').value || '1', 10)) : '';

    const fd = new FormData();
    fd.append('jenis', jenis);
    fd.append('keterangan', ket);
    if(isCuti) fd.append('jumlah_hari', String(jumlah_hari));

    // pilih bukti: file (pdf/image) atau hasil kamera (image)
    if(ketFile){
      fd.append('bukti', ketFile, ketFile.name || 'bukti');
    }else if(ketCamDataUrl){
      const blob = dataUrlToBlob(ketCamDataUrl);
      fd.append('bukti', blob, `bukti_${jenis}.jpg`);
    }

    Swal.fire({title:'Mengirim...', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
    try{
      await apiPostForm('absensi_guru_ket_submit.php', fd);
      Swal.close();
      closeKetModal();
      await loadGuruStatus();
      await loadKetStatus();
      Swal.fire('OK','Pengajuan tersimpan','success');
    }catch(e){
      Swal.fire('Gagal', e.message, 'error');
    }
  }

  // ---------- JURNAL ----------
  async function loadKelasDatalist(){
    try{
      const js = await apiGet('kelas_distinct.php');
      const rows = js.data || [];
      $('dlKelas').innerHTML = rows.map(k=>`<option value="${esc(k)}"></option>`).join('');
    }catch(e){
      $('dlKelas').innerHTML = '';
    }
  }
  async function loadMapelDatalist(){
    try{
      const js = await apiGet('mapel_list.php');
      const rows = js.data || [];
      $('dlMapel').innerHTML = rows.map(r=>`<option value="${esc(r.nama_mapel||'')}"></option>`).join('');
    }catch(e){
      $('dlMapel').innerHTML = '';
    }
  }

  function fillJPOptions(){
    const s1 = $('j_jp_mulai');
    const s2 = $('j_jp_selesai');
    const opts = [`<option value="">(Kosong)</option>`].concat(
      Array.from({length: MAX_JP}, (_,i)=>`<option value="${i+1}">Jam ${i+1}</option>`)
    ).join('');
    s1.innerHTML = opts;
    s2.innerHTML = opts;
  }

  // foto jurnal (max 2)
  const JURNAL_MAX_FOTO = 2;
  let jurnalFotos = [];

  function renderJurnalFotos(){
    const wrap = $('j_fotoList');
    if(jurnalFotos.length === 0){
      wrap.innerHTML = `<div class="text-slate-500 italic text-sm col-span-2">Belum ada foto.</div>`;
      return;
    }
    wrap.innerHTML = jurnalFotos.map((src, idx)=>`
      <div class="relative rounded-2xl overflow-hidden border border-slate-800">
        <img src="${src}" class="w-full h-36 object-cover" />
        <button class="absolute top-2 right-2 px-2 py-1 rounded-lg bg-black/70 hover:bg-black text-white text-xs font-black"
                onclick="hapusFotoJurnal(${idx})">Hapus</button>
      </div>
    `).join('');
  }
  window.hapusFotoJurnal = (idx)=>{
    jurnalFotos.splice(idx,1);
    renderJurnalFotos();
  };
  function addFotoJurnal(dataUrl){
    if(!dataUrl) return;
    if(jurnalFotos.length >= JURNAL_MAX_FOTO){
      Swal.fire('Info', `Maksimal ${JURNAL_MAX_FOTO} foto.`, 'info');
      return;
    }
    jurnalFotos.push(dataUrl);
    renderJurnalFotos();
  }

  // kamera jurnal
  let jCamStream = null;
  let jCamFacing = 'user';

  function openJCam(facing){
    jCamFacing = facing || 'user';
    $('jcamTitle').textContent = `Bukti Foto • ${jCamFacing==='environment' ? 'Belakang' : 'Depan'}`;
    $('modalJurnalCam').classList.remove('hidden');
    $('modalJurnalCam').classList.add('flex');
  }
  function closeJCam(){
    stopJCam();
    $('modalJurnalCam').classList.add('hidden');
    $('modalJurnalCam').classList.remove('flex');
  }
  async function startJCam(){
    stopJCam();
    const constraints = { video: { facingMode: (jCamFacing==='environment' ? {ideal:'environment'} : 'user') }, audio:false };
    jCamStream = await navigator.mediaDevices.getUserMedia(constraints);
    $('jVideo').srcObject = jCamStream;
  }
  function stopJCam(){
    try{ jCamStream?.getTracks()?.forEach(t=>t.stop()); }catch(e){}
    jCamStream = null;
    if($('jVideo')) $('jVideo').srcObject = null;
  }
  function snapJCam(){
    const video = $('jVideo');
    const canvas = $('jCanvas');
    const w = video.videoWidth || 640;
    const h = video.videoHeight || 480;
    canvas.width = w; canvas.height = h;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, w, h);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
    addFotoJurnal(dataUrl);
    closeJCam();
  }

  // galeri -> kompres
  function fileToJpegDataUrl(file, maxSide=1280, quality=0.85){
    return new Promise((resolve, reject)=>{
      const fr = new FileReader();
      fr.onerror = ()=>reject(new Error('Gagal membaca file.'));
      fr.onload = ()=>{
        const img = new Image();
        img.onerror = ()=>reject(new Error('File gambar tidak valid.'));
        img.onload = ()=>{
          let w = img.width, h = img.height;
          const scale = Math.min(1, maxSide / Math.max(w,h));
          w = Math.round(w * scale);
          h = Math.round(h * scale);
          const c = document.createElement('canvas');
          c.width = w; c.height = h;
          c.getContext('2d').drawImage(img, 0, 0, w, h);
          resolve(c.toDataURL('image/jpeg', quality));
        };
        img.src = String(fr.result || '');
      };
      fr.readAsDataURL(file);
    });
  }

  async function onPickGallery(files){
    const arr = Array.from(files || []);
    if(arr.length === 0) return;
    const sisa = JURNAL_MAX_FOTO - jurnalFotos.length;
    if(sisa <= 0){
      Swal.fire('Info', `Maksimal ${JURNAL_MAX_FOTO} foto.`, 'info');
      return;
    }
    const pick = arr.slice(0, sisa);
    Swal.fire({title:'Memproses foto...', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
    try{
      for(const f of pick){
        const du = await fileToJpegDataUrl(f);
        addFotoJurnal(du);
      }
      Swal.close();
    }catch(e){
      Swal.fire('Error', e.message, 'error');
    }
  }

  async function saveJurnal(){
    const kelas = $('j_kelas').value.trim();
    const mapel = $('j_mapel').value.trim();
    const materi = $('j_materi').value.trim();
    const catatan = $('j_catatan').value.trim();

    const jam_ke_mulai = ($('j_jp_mulai').value || '').trim();
    const jam_ke_selesai = ($('j_jp_selesai').value || '').trim();

    if(!kelas || !mapel || !materi) return Swal.fire('Wajib','Kelas, mapel, materi wajib','warning');

    if((jam_ke_mulai && !jam_ke_selesai) || (!jam_ke_mulai && jam_ke_selesai)){
      return Swal.fire('Wajib','JP mulai & JP selesai harus diisi lengkap (atau kosongkan).','warning');
    }
    if(jam_ke_mulai && jam_ke_selesai && Number(jam_ke_selesai) < Number(jam_ke_mulai)){
      return Swal.fire('Wajib','JP selesai tidak boleh lebih kecil dari mulai.','warning');
    }

    await apiPost('jurnal_guru_save.php', {
      kelas,
      mapel,
      materi,
      topik: materi,
      jam_ke_mulai: jam_ke_mulai ? Number(jam_ke_mulai) : null,
      jam_ke_selesai: jam_ke_selesai ? Number(jam_ke_selesai) : null,
      fotos: jurnalFotos,
      catatan
    });

    $('j_materi').value = '';
    $('j_catatan').value = '';
    jurnalFotos = [];
    renderJurnalFotos();
    await loadJurnal();
    Swal.fire('Sukses','Jurnal tersimpan','success');
  }

  function fmtJP(r){
    const a = Number(r.jam_ke_mulai||0);
    const b = Number(r.jam_ke_selesai||0);
    if(a>0 && b>0) return `Jam ${a}-${b}`;
    if(a>0) return `Jam ${a}`;
    return '-';
  }

  window.lihatJurnalFoto = (encoded)=>{
    try{
      const raw = decodeURIComponent(encoded || '');
      const arr = raw ? JSON.parse(raw) : [];
      const imgs = Array.isArray(arr) ? arr : [];
      const html = imgs.length
        ? `<div class="space-y-3">${imgs.map(u=>`<img src="${esc(u)}" class="w-full rounded-xl border border-slate-700" />`).join('')}</div>`
        : `<div class="text-slate-500 italic">Tidak ada foto</div>`;
      Swal.fire({title:'Bukti Foto Jurnal', html, width: 520});
    }catch(e){
      Swal.fire('Error','Gagal memuat foto','error');
    }
  };

  async function loadJurnal(){
    const js = await apiGet('jurnal_guru_list.php');
    const rows = js.data || [];
    if(rows.length===0){
      $('tbJurnal').innerHTML = `<tr><td colspan="6" class="p-4 text-center italic text-slate-500">Belum ada jurnal.</td></tr>`;
      return;
    }
    $('tbJurnal').innerHTML = rows.slice(0,200).map(r=>{
      const topik = r.topik ?? r.materi ?? '';
      const hasFoto = !!(r.foto_json && String(r.foto_json).trim() !== '');
      const btnFoto = hasFoto
        ? `<button class="px-3 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 text-white font-black text-xs"
                  onclick="lihatJurnalFoto('${encodeURIComponent(String(r.foto_json))}')">Lihat</button>`
        : `<span class="text-slate-500">-</span>`;
      return `
      <tr class="hover:bg-slate-800">
        <td class="p-3 text-slate-300">${esc(r.tanggal)}</td>
        <td class="p-3 text-slate-300">${esc(fmtJP(r))}</td>
        <td class="p-3 text-white font-bold">${esc(r.kelas)}</td>
        <td class="p-3 text-emerald-300">${esc(r.mapel)}</td>
        <td class="p-3 text-slate-200">${esc(topik).slice(0,70)}${String(topik||'').length>70?'…':''}</td>
        <td class="p-3 text-right">${btnFoto}</td>
      </tr>`;
    }).join('');
  }

  // ---------- 7 HEBAT VALIDASI ----------
  function ringkas7(data){
    try{ data = (typeof data==='string') ? JSON.parse(data) : (data||{});}catch(e){ data={}; }
    const n = ['k1','k2','k3','k4','k5','k6','k7'].reduce((a,k)=>a+(data[k]?1:0),0);
    return `${n}/7`;
  }
  async function load7(){
    const tanggal = $('v_tanggal').value || isoToday();
    const kelas = $('v_kelas').value.trim();
    if(!kelas) return Swal.fire('Info','Kelas kosong. Isi dulu (wali kelas).','warning');
    const js = await apiGet(`7hebat_list_kelas.php?tanggal=${encodeURIComponent(tanggal)}&kelas=${encodeURIComponent(kelas)}`);
    const rows = js.data || [];
    if(rows.length===0){
      $('tb7').innerHTML = `<tr><td colspan="4" class="p-4 text-center italic text-slate-500">Belum ada data.</td></tr>`;
      return;
    }
    $('tb7').innerHTML = rows.map(r=>`
      <tr class="hover:bg-slate-800">
        <td class="p-3 text-white font-bold">${esc(r.nama||r.username)}</td>
        <td class="p-3"><span class="px-2 py-1 rounded bg-slate-900 border border-slate-700 text-xs font-black">${esc(r.status||'submitted')}</span></td>
        <td class="p-3 text-slate-300">${ringkas7(r.data_json)}</td>
        <td class="p-3 text-right">
          <button class="px-3 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 text-white font-black text-xs" onclick="lihat7('${esc(r.username)}','${esc(r.tanggal)}')">Detail</button>
        </td>
      </tr>
    `).join('');
  }

  window.lihat7 = async (username, tanggal) => {
    try{
      const js = await apiGet(`7hebat_detail.php?username=${encodeURIComponent(username)}&tanggal=${encodeURIComponent(tanggal)}`);
      const d = js.data;
      const data = d?.data || {};
      const lines = [
        `Bangun Pagi: ${data.k1? 'Ya':'-'} ${data.jam_bangun? '('+data.jam_bangun+')':''}`,
        `Ibadah: ${data.k2? 'Ya':'-'} ${data.jenis_ibadah? '('+data.jenis_ibadah+')':''}`,
        `Olahraga: ${data.k3? 'Ya':'-'} ${data.olahraga? '('+data.olahraga+')':''}`,
        `Makan Sehat: ${data.k4? 'Ya':'-'} ${data.menu_makan? '('+data.menu_makan+')':''}`,
        `Belajar: ${data.k5? 'Ya':'-'} ${data.mapel? '('+data.mapel+')':''}`,
        `Sosial: ${data.k6? 'Ya':'-'} ${data.sosial? '('+data.sosial+')':''}`,
        `Tidur Cepat: ${data.k7? 'Ya':'-'} ${data.jam_tidur? '('+data.jam_tidur+')':''}`,
      ];

      const html = `<div class="text-left text-sm text-slate-200 space-y-1">${lines.map(x=>`<div>• ${esc(x)}</div>`).join('')}<div class="mt-2 text-slate-400">Catatan: ${esc(d.catatan||data.catatan||'-')}</div></div>`;
      const res = await Swal.fire({
        title: `Validasi 7 Hebat`,
        html,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'VALID',
        denyButtonText: 'REJECT',
        cancelButtonText: 'Tutup',
      });
      if(res.isConfirmed){
        await apiPost('7hebat_validate.php', {username, tanggal, action:'valid'});
        await load7();
        Swal.fire('OK','Tervalidasi','success');
      } else if(res.isDenied){
        const { value: note } = await Swal.fire({
          title:'Alasan reject (opsional)',
          input:'text',
          inputPlaceholder:'Contoh: fotonya belum jelas',
          showCancelButton:true
        });
        await apiPost('7hebat_validate.php', {username, tanggal, action:'reject', note: note||''});
        await load7();
        Swal.fire('OK','Direject','success');
      }
    }catch(e){
      Swal.fire('Error', e.message, 'error');
    }
  }

  // ---------- TUGAS TAMBAHAN ----------
  async function loadTugas(){
    const js = await apiGet('tugas_guru_my.php');
    const rows = js.data || [];
    if(rows.length===0){
      $('tbTugas').innerHTML = `<tr><td colspan="4" class="p-4 text-center italic text-slate-500">Belum ada tugas.</td></tr>`;
      return;
    }
    $('tbTugas').innerHTML = rows.map(r=>`
      <tr class="hover:bg-slate-800">
        <td class="p-3 text-white font-black">${esc(r.nama_tugas||'')}</td>
        <td class="p-3 text-slate-300">${esc(r.tipe||'-')}</td>
        <td class="p-3 text-slate-300">${esc(r.tanggal_mulai||'-')}</td>
        <td class="p-3 text-right">
          <button class="px-3 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 text-white font-black text-xs" onclick="lihatAnggota(${Number(r.id)})">Lihat</button>
        </td>
      </tr>
    `).join('');
  }
  window.lihatAnggota = async (tugas_id) => {
    try{
      const js = await apiGet(`tugas_guru_members.php?tugas_id=${encodeURIComponent(tugas_id)}`);
      const rows = js.data || [];
      const html = rows.length ? `<div class="text-left text-sm">${rows.map(x=>`<div>• <b>${esc(x.nama||x.username)}</b> <span class='text-slate-400'>(${esc(x.kelas||'-')})</span></div>`).join('')}</div>`
        : `<div class="text-slate-500 italic">Belum ada anggota</div>`;
      Swal.fire({title:'Anggota Tugas', html});
    }catch(e){
      Swal.fire('Error', e.message, 'error');
    }
  }

  // ---------- DOM READY ----------
  document.addEventListener('DOMContentLoaded', async ()=>{
    $('v_tanggal').value = isoToday();

    // JP dropdown
    fillJPOptions();
    renderJurnalFotos();

    // Presensi map
    await loadSettings().catch(()=>{});
    initMap();
    renderSchoolCircle();
    await prepareGps().catch(()=>{});

    // status
    await loadGuruStatus().catch(()=>{});
    await loadKetStatus().catch(()=>{});

    // datalist kelas/mapel
    await loadKelasDatalist().catch(()=>{});
    await loadMapelDatalist().catch(()=>{});
    await loadJurnal().catch(()=>{});

    // Presensi actions
    $('btnMasuk').addEventListener('click', async ()=>{
      try{
        await prepareGps();
        openModal('masuk');
        await startCamera();
      }catch(e){
        Swal.fire('Error', e.message, 'error');
      }
    });
    $('btnPulang').addEventListener('click', async ()=>{
      try{
        await prepareGps();
        openModal('pulang');
        await startCamera();
      }catch(e){
        Swal.fire('Error', e.message, 'error');
      }
    });
    $('btnCloseModal').addEventListener('click', closeModal);
    $('btnSnap').addEventListener('click', ()=>{ try{ snap(); }catch(e){ Swal.fire('Error', e.message, 'error'); }});
    $('btnSubmit').addEventListener('click', ()=>doPresensi().catch(e=>Swal.fire('Error', e.message, 'error')));

    // Ket modal actions
    $('btnAjukanKet').addEventListener('click', ()=>openKetModal());
    $('btnCloseKet').addEventListener('click', closeKetModal);
    $('k_jenis').addEventListener('change', toggleJumlahHari);
    $('btnKetPickFile').addEventListener('click', ()=>$('k_file').click());
    $('k_file').addEventListener('change', (e)=>{
      const f = e.target.files && e.target.files[0];
      if(f){
        ketFile = f;
        ketCamDataUrl = null;
        showKetPreviewForFile(f);
      }
      e.target.value = '';
    });
    $('btnKetClear').addEventListener('click', clearKetEvidence);
    $('btnSubmitKet').addEventListener('click', ()=>submitKet().catch(()=>{}));

    // Ket camera uses same camera modal (modalJurnalCam)
    $('btnKetCamFront').addEventListener('click', async ()=>{
      try{ await openKetCamera('user'); }catch(e){ Swal.fire('Error', e.message, 'error'); }
    });
    $('btnKetCamBack').addEventListener('click', async ()=>{
      try{ await openKetCamera('environment'); }catch(e){ Swal.fire('Error', e.message, 'error'); }
    });

    $('btnLihatBuktiKet').addEventListener('click', ()=>{
      if(KET_TODAY && KET_TODAY.bukti_url){
        window.open(KET_TODAY.bukti_url, '_blank');
      }
    });

    // Jurnal foto camera
    $('btnJFotoFront').addEventListener('click', async ()=>{
      try{
        openJCam('user');
        await startJCam();
      }catch(e){
        closeJCam();
        Swal.fire('Error', e.message, 'error');
      }
    });
    $('btnJFotoBack').addEventListener('click', async ()=>{
      try{
        openJCam('environment');
        await startJCam();
      }catch(e){
        closeJCam();
        Swal.fire('Error', e.message, 'error');
      }
    });
    $('btnCloseJCam').addEventListener('click', ()=>{
      stopKetCam();
      closeJCam();
    });

    $('btnJCamSnap').addEventListener('click', ()=>{
      // kalau ketCamStream aktif -> snapKetCam, else snapJCam
      if(ketCamStream){
        try{ snapKetCam(); }catch(e){ Swal.fire('Error', e.message, 'error'); }
      }else{
        try{ snapJCam(); }catch(e){ Swal.fire('Error', e.message, 'error'); }
      }
    });

    $('btnJFotoGallery').addEventListener('click', ()=>$('j_foto_file').click());
    $('j_foto_file').addEventListener('change', async (e)=>{
      try{
        await onPickGallery(e.target.files);
      }finally{
        e.target.value = '';
      }
    });

    // Save/Reload jurnal
    $('btnSaveJurnal').addEventListener('click', ()=>saveJurnal().catch(e=>Swal.fire('Error',e.message,'error')));
    $('btnReloadJurnal').addEventListener('click', ()=>loadJurnal().catch(e=>Swal.fire('Error',e.message,'error')));

    // 7 Hebat, tugas
    $('btnLoad7').addEventListener('click', ()=>load7().catch(e=>Swal.fire('Error',e.message,'error')));
    $('btnLoadTugas').addEventListener('click', ()=>loadTugas().catch(e=>Swal.fire('Error',e.message,'error')));
    if($('v_kelas').value) await load7().catch(()=>{});
    await loadTugas().catch(()=>{});
  });
</script>
</body>
</html>