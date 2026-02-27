<?php
// public_html/kiosk_webcam.php
// Kiosk tidak butuh login agar bisa dipakai di sekolah (opsional). Kalau mau dikunci, tinggal aktifkan guard.
// require __DIR__ . '/auth/guard.php';
// require_login(['admin','super','bk','kesiswaan','kurikulum','guru','staff']);

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kiosk Webcam - EduGate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/html5-qrcode"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Outfit',sans-serif;background:#0b1220;color:#e5e7eb}
    .card{background:#0f172a;border:1px solid #1f2937;border-radius:18px}
    .btn{padding:.75rem 1rem;border-radius:14px;font-weight:900}
    .inp{background:#0b1220;border:1px solid #24324a;border-radius:12px;padding:.75rem;color:#fff;width:100%}
    .label{font-size:.75rem;text-transform:uppercase;letter-spacing:.12em;color:#94a3b8;font-weight:800}
  </style>
</head>
<body class="min-h-screen">
  <header class="max-w-4xl mx-auto px-4 py-6 flex items-center justify-between">
    <div>
      <div class="text-2xl font-black text-white tracking-tight">EduGate <span class="text-emerald-400">Kiosk Webcam</span></div>
      <div class="text-slate-400 text-sm mt-1">Alternatif untuk siswa yang tidak membawa HP</div>
    </div>
    <div class="flex gap-2">
      <a href="/" class="btn bg-slate-800 hover:bg-slate-700">Home</a>
      <a href="/guru.php" class="btn bg-indigo-600 hover:bg-indigo-500 text-white">Panel Guru</a>
    </div>
  </header>

  <main class="max-w-4xl mx-auto px-4 pb-10 space-y-6">
    <section class="card p-6">
      <h2 class="text-lg font-black text-white">Scan QR / Input NISN</h2>
      <p class="text-slate-400 text-sm mt-1">QR bisa berisi NISN/username. Kalau belum ada presensi hari ini → otomatis <b>MASUK</b>. Kalau sudah masuk → otomatis <b>PULANG</b>.</p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">
        <div class="card p-4">
          <div class="label">Scan QR</div>
          <div id="reader" class="mt-3"></div>
          <div class="mt-3 text-sm text-slate-400">Hasil scan: <span id="hasil" class="text-white font-black">-</span></div>
        </div>
        <div class="card p-4">
          <div class="label">Input Manual</div>
          <input id="nisn" class="inp mt-2" placeholder="Masukkan NISN / username" />
          <div class="mt-3 flex gap-2">
            <button id="btnAuto" class="btn bg-emerald-600 hover:bg-emerald-500 text-white flex-1">Auto (Masuk/Pulang)</button>
            <button id="btnReset" class="btn bg-slate-800 hover:bg-slate-700 flex-1">Reset</button>
          </div>
          <div class="mt-3 text-slate-400 text-sm">Catatan: Endpoint kiosk ini dibuat ringan supaya tidak ngebebanin hosting.</div>
        </div>
      </div>
    </section>
  </main>

<script>
  const BASE_API = '/api';
  const $ = (id)=>document.getElementById(id);

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

  async function submitKiosk(username){
    username = String(username||'').trim();
    if(!username) return;
    $('hasil').textContent = username;
    $('nisn').value = username;
    const js = await apiPost('kiosk_absen.php', {username}); // action auto
    Swal.fire('Sukses', js.message || 'Tercatat', 'success');
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    $('btnAuto').addEventListener('click', ()=>submitKiosk($('nisn').value).catch(e=>Swal.fire('Error',e.message,'error')));
    $('btnReset').addEventListener('click', ()=>{ $('nisn').value=''; $('hasil').textContent='-'; });

    const html5QrCode = new Html5Qrcode("reader");
    const config = { fps: 10, qrbox: { width: 220, height: 220 } };

    Html5Qrcode.getCameras().then(cameras => {
      const camId = cameras?.[0]?.id;
      if(!camId) throw new Error('Kamera tidak ditemukan');
      return html5QrCode.start(
        camId,
        config,
        (decodedText) => {
          // stop scanning for 2s to prevent double
          html5QrCode.pause(true);
          submitKiosk(decodedText).catch(e=>Swal.fire('Error',e.message,'error')).finally(()=>{
            setTimeout(()=>html5QrCode.resume(), 1500);
          });
        },
        () => {}
      );
    }).catch(err => {
      console.warn(err);
      $('reader').innerHTML = `<div class='text-red-400 text-sm'>Kamera tidak bisa diakses. Pakai input manual.</div>`;
    });
  });
</script>
</body>
</html>
