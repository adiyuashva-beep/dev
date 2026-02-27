<?php
// public_html/tujuh_hebat.php
require __DIR__ . '/auth/guard.php';
require_login(['siswa']);

$user = $_SESSION['user'] ?? ['username'=>'','name'=>'Siswa','kelas'=>''];
$name = $user['name'] ?? 'Siswa';
$kelas = $user['kelas'] ?? '';
$username = $user['username'] ?? '';

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>7 Kebiasaan Anak Indonesia Hebat</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Outfit',sans-serif;background:#0b1220;color:#e5e7eb}
    .card{background:#0f172a;border:1px solid #1f2937;border-radius:18px}
    .inp{background:#0b1220;border:1px solid #24324a;border-radius:12px;padding:.75rem;color:#fff;width:100%}
    .label{font-size:.75rem;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;font-weight:800}
  </style>
</head>
<body class="min-h-screen">
  <header class="max-w-4xl mx-auto px-4 py-6 flex items-center justify-between">
    <div>
      <div class="text-2xl font-black text-white tracking-tight">7 Kebiasaan <span class="text-blue-400">Anak Indonesia Hebat</span></div>
      <div class="text-slate-400 text-sm mt-1">Siswa: <b class="text-white"><?= htmlspecialchars($name) ?></b> • Kelas: <b class="text-white"><?= htmlspecialchars($kelas ?: '-') ?></b></div>
    </div>
    <div class="flex gap-2">
      <a href="/siswa.php" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold">Kembali</a>
      <a href="/logout.php" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold">Logout</a>
    </div>
  </header>

  <main class="max-w-4xl mx-auto px-4 pb-10 space-y-6">

    <section class="card p-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
          <h2 class="text-xl font-black text-white">Laporan Harian</h2>
          <p class="text-slate-400 text-sm">Isi kebiasaanmu hari ini. Setelah disimpan, wali kelas bisa memvalidasi.</p>
        </div>
        <div class="flex items-center gap-2">
          <label class="label">Tanggal</label>
          <input id="tanggal" type="date" class="inp w-auto" />
        </div>
      </div>
    </section>

    <section class="card p-6 space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <div class="label">1. Bangun Pagi</div>
          <div class="mt-2 flex items-center gap-3">
            <input id="k1" type="checkbox" class="w-5 h-5" />
            <span class="text-slate-200">Saya bangun pagi</span>
          </div>
          <div class="mt-3">
            <div class="label">Jam Bangun (opsional)</div>
            <input id="jam_bangun" type="time" class="inp mt-1" />
          </div>
        </div>

        <div>
          <div class="label">2. Beribadah</div>
          <div class="mt-2 flex items-center gap-3">
            <input id="k2" type="checkbox" class="w-5 h-5" />
            <span class="text-slate-200">Saya beribadah</span>
          </div>
          <div class="mt-3">
            <div class="label">Jenis Ibadah (opsional)</div>
            <input id="jenis_ibadah" class="inp mt-1" placeholder="Contoh: Sholat Subuh / Doa / Ibadah pagi" />
          </div>
        </div>

        <div>
          <div class="label">3. Berolahraga</div>
          <div class="mt-2 flex items-center gap-3">
            <input id="k3" type="checkbox" class="w-5 h-5" />
            <span class="text-slate-200">Saya berolahraga</span>
          </div>
          <div class="mt-3">
            <div class="label">Jenis Olahraga (opsional)</div>
            <input id="olahraga" class="inp mt-1" placeholder="Contoh: Lari / Senam / Sepak bola" />
          </div>
        </div>

        <div>
          <div class="label">4. Makan Sehat & Bergizi</div>
          <div class="mt-2 flex items-center gap-3">
            <input id="k4" type="checkbox" class="w-5 h-5" />
            <span class="text-slate-200">Saya makan sehat</span>
          </div>
          <div class="mt-3">
            <div class="label">Menu Makan (opsional)</div>
            <input id="menu_makan" class="inp mt-1" placeholder="Contoh: Nasi + telur + sayur" />
          </div>
        </div>

        <div>
          <div class="label">5. Gemar Belajar</div>
          <div class="mt-2 flex items-center gap-3">
            <input id="k5" type="checkbox" class="w-5 h-5" />
            <span class="text-slate-200">Saya belajar</span>
          </div>
          <div class="mt-3">
            <div class="label">Mapel yang dipelajari (opsional)</div>
            <input id="mapel" class="inp mt-1" placeholder="Contoh: Matematika" />
          </div>
        </div>

        <div>
          <div class="label">6. Bermasyarakat / Sosial</div>
          <div class="mt-2 flex items-center gap-3">
            <input id="k6" type="checkbox" class="w-5 h-5" />
            <span class="text-slate-200">Saya melakukan kegiatan sosial</span>
          </div>
          <div class="mt-3">
            <div class="label">Kegiatan sosial (opsional)</div>
            <input id="sosial" class="inp mt-1" placeholder="Contoh: membantu orang tua / kerja bakti" />
          </div>
        </div>

        <div>
          <div class="label">7. Tidur Cepat</div>
          <div class="mt-2 flex items-center gap-3">
            <input id="k7" type="checkbox" class="w-5 h-5" />
            <span class="text-slate-200">Saya tidur cepat</span>
          </div>
          <div class="mt-3">
            <div class="label">Jam Tidur (opsional)</div>
            <input id="jam_tidur" type="time" class="inp mt-1" />
          </div>
        </div>

        <div>
          <div class="label">Catatan (opsional)</div>
          <textarea id="catatan" rows="6" class="inp mt-1" placeholder="Tulis catatan singkat..."></textarea>
        </div>
      </div>

      <div class="flex flex-col md:flex-row md:justify-between gap-3 pt-2">
        <div class="text-sm text-slate-400">Status: <span id="status" class="font-black text-white">-</span></div>
        <div class="flex gap-2">
          <button id="btnReload" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 font-black">Muat</button>
          <button id="btnSave" class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 font-black text-white">Simpan</button>
        </div>
      </div>
    </section>

  </main>

<script>
  const BASE_API = '/api';
  const $ = (id)=>document.getElementById(id);

  function isoToday(){
    const d = new Date();
    const pad = (n)=>String(n).padStart(2,'0');
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
  }

  function fillForm(row){
    const data = row?.data || {};
    $('k1').checked = !!data.k1;
    $('k2').checked = !!data.k2;
    $('k3').checked = !!data.k3;
    $('k4').checked = !!data.k4;
    $('k5').checked = !!data.k5;
    $('k6').checked = !!data.k6;
    $('k7').checked = !!data.k7;
    $('jam_bangun').value = data.jam_bangun || '';
    $('jenis_ibadah').value = data.jenis_ibadah || '';
    $('olahraga').value = data.olahraga || '';
    $('menu_makan').value = data.menu_makan || '';
    $('mapel').value = data.mapel || '';
    $('sosial').value = data.sosial || '';
    $('jam_tidur').value = data.jam_tidur || '';
    $('catatan').value = row?.catatan || data.catatan || '';
    $('status').textContent = (row?.status || 'submitted');
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

  async function loadMy(){
    const tanggal = $('tanggal').value || isoToday();
    const js = await apiGet(`7hebat_get_my.php?tanggal=${encodeURIComponent(tanggal)}`);
    fillForm(js.data || null);
  }

  async function saveMy(){
    const tanggal = $('tanggal').value || isoToday();
    const data = {
      k1: $('k1').checked ? 1 : 0,
      k2: $('k2').checked ? 1 : 0,
      k3: $('k3').checked ? 1 : 0,
      k4: $('k4').checked ? 1 : 0,
      k5: $('k5').checked ? 1 : 0,
      k6: $('k6').checked ? 1 : 0,
      k7: $('k7').checked ? 1 : 0,
      jam_bangun: $('jam_bangun').value || '',
      jenis_ibadah: $('jenis_ibadah').value || '',
      olahraga: $('olahraga').value || '',
      menu_makan: $('menu_makan').value || '',
      mapel: $('mapel').value || '',
      sosial: $('sosial').value || '',
      jam_tidur: $('jam_tidur').value || '',
      catatan: $('catatan').value || '',
    };

    await apiPost('7hebat_save_my.php', { tanggal, data, catatan: $('catatan').value || ''});
    await loadMy();
    Swal.fire('Sukses','Laporan tersimpan','success');
  }

  document.addEventListener('DOMContentLoaded', async ()=>{
    $('tanggal').value = isoToday();
    $('btnReload').addEventListener('click', ()=>loadMy().catch(e=>Swal.fire('Error',e.message,'error')));
    $('btnSave').addEventListener('click', ()=>saveMy().catch(e=>Swal.fire('Error',e.message,'error')));
    loadMy().catch(e=>Swal.fire('Error',e.message,'error'));
  });
</script>
</body>
</html>
