<?php
require __DIR__ . '/auth/guard.php';
require_login(['admin','super','kesiswaan','kurikulum','bk','staff']);

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Rekap Presensi Guru</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
  <header class="max-w-6xl mx-auto px-4 py-6 flex items-center justify-between">
    <div>
      <div class="text-2xl font-black text-white tracking-tight">Admin • <span class="text-emerald-400">Rekap Presensi Guru</span></div>
      <div class="text-slate-400 text-sm mt-1">Jam masuk & pulang (detail).</div>
    </div>
    <div class="flex gap-2">
      <a href="/admin_full.php" class="btn bg-slate-800 hover:bg-slate-700">Kembali</a>
      <a href="/logout.php" class="btn bg-red-600 hover:bg-red-500 text-white">Logout</a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 pb-12 space-y-6">
    <section class="card p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <div class="label">Tanggal</div>
          <input id="tanggal" type="date" class="inp mt-1" />
        </div>
        <div class="md:col-span-2">
          <div class="label">Cari (nama/username)</div>
          <input id="q" class="inp mt-1" placeholder="opsional" />
        </div>
        <div class="flex items-end">
          <button id="btnLoad" class="btn bg-emerald-600 hover:bg-emerald-500 text-white w-full">Muat</button>
        </div>
      </div>
    </section>

    <section class="card p-6">
      <div class="overflow-x-auto border border-slate-800 rounded-xl">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-950 text-slate-400 text-xs uppercase font-black">
            <tr>
              <th class="p-3">Nama</th>
              <th class="p-3">Username</th>
              <th class="p-3">Role</th>
              <th class="p-3">Jam Masuk</th>
              <th class="p-3">Jam Pulang</th>
              <th class="p-3">Foto Masuk</th>
              <th class="p-3">Foto Pulang</th>
              <th class="p-3">Status</th>
              <th class="p-3">Telat</th>
            </tr>
          </thead>
          <tbody id="tb" class="divide-y divide-slate-800">
            <tr><td colspan="9" class="p-4 text-center italic text-slate-500">Silakan muat data.</td></tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>

<script>
  const BASE_API = '/api';
  const $ = (id)=>document.getElementById(id);
  const esc = (s)=>String(s??'').replace(/[&<>"']/g, m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"}[m]));
  const isoToday = ()=>{ const d=new Date(); const p=n=>String(n).padStart(2,'0'); return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}`; };
  async function apiGet(path){
    const res = await fetch(`${BASE_API}/${path}`, {credentials:'include'});
    const js = await res.json();
    if(!js.ok) throw new Error(js.error||'Error');
    return js;
  }

  async function load(){
    const tanggal = $('tanggal').value || isoToday();
    const q = $('q').value.trim().toLowerCase();
    const js = await apiGet(`absensi_guru_list.php?tanggal=${encodeURIComponent(tanggal)}`);
    let rows = js.data || [];
    if(q){
      rows = rows.filter(r=>String(r.nama||'').toLowerCase().includes(q) || String(r.username||'').toLowerCase().includes(q));
    }
    if(rows.length===0){
      $('tb').innerHTML = `<tr><td colspan="9" class="p-4 text-center italic text-slate-500">Tidak ada data.</td></tr>`;
      return;
    }
    const photoLink = (url)=> url ? `<a href="${esc(url)}" target="_blank" class="text-emerald-300 font-black hover:underline">Lihat</a>` : '<span class="text-slate-600">-</span>';
    $('tb').innerHTML = rows.map(r=>`
      <tr class="hover:bg-slate-800">
        <td class="p-3 text-white font-black">${esc(r.nama||'-')}</td>
        <td class="p-3 text-slate-300">${esc(r.username||'-')}</td>
        <td class="p-3 text-slate-300">${esc(r.role||'-')}</td>
        <td class="p-3 text-slate-300">${esc(r.jam_masuk||'-')}</td>
        <td class="p-3 text-slate-300">${esc(r.jam_pulang||'-')}</td>
        <td class="p-3">${photoLink(r.foto_masuk)}</td>
        <td class="p-3">${photoLink(r.foto_pulang)}</td>
        <td class="p-3"><span class="px-2 py-1 rounded bg-slate-900 border border-slate-700 text-xs font-black">${esc(r.status||'-')}</span></td>
        <td class="p-3">${r.telat==1? '<span class="text-red-300 font-black">YA</span>':'<span class="text-emerald-300 font-black">TIDAK</span>'}</td>
      </tr>
    `).join('');
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    $('tanggal').value = isoToday();
    $('btnLoad').addEventListener('click', ()=>load().catch(e=>Swal.fire('Error',e.message,'error')));
    load().catch(()=>{});
  });
</script>
</body>
</html>
