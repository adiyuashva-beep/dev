<?php
require __DIR__ . '/auth/guard.php';
require_login(['admin','super','kurikulum','staff']);

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Monitoring Jurnal Guru - Kurikulum</title>
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
      <div class="text-2xl font-black text-white tracking-tight">Kurikulum • <span class="text-blue-400">Monitoring Jurnal Guru</span></div>
      <div class="text-slate-400 text-sm mt-1">Data jurnal tersimpan di MySQL.</div>
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
        <div>
          <div class="label">Kelas</div>
          <input id="kelas" class="inp mt-1" placeholder="opsional" />
        </div>
        <div>
          <div class="label">Guru</div>
          <select id="guru" class="inp mt-1"></select>
        </div>
        <div class="flex items-end">
          <button id="btnLoad" class="btn bg-blue-600 hover:bg-blue-500 text-white w-full">Muat</button>
        </div>
      </div>
    </section>

    <section class="card p-6">
      <div class="overflow-x-auto border border-slate-800 rounded-xl">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-950 text-slate-400 text-xs uppercase font-black">
            <tr>
              <th class="p-3">Tanggal</th>
              <th class="p-3">Guru</th>
              <th class="p-3">Kelas</th>
              <th class="p-3">Mapel</th>
              <th class="p-3">Materi</th>
              <th class="p-3">Catatan</th>
            </tr>
          </thead>
          <tbody id="tb" class="divide-y divide-slate-800">
            <tr><td colspan="6" class="p-4 text-center italic text-slate-500">Silakan muat data.</td></tr>
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

  async function loadGuruSelect(){
    const js = await apiGet('tugas_admin_list_guru.php');
    const rows = js.data || [];
    $('guru').innerHTML = [`<option value="">-- semua --</option>`].concat(rows.map(r=>`<option value="${esc(r.username)}">${esc(r.nama||r.username)}</option>`)).join('');
  }

  async function load(){
    const tanggal = $('tanggal').value;
    const kelas = $('kelas').value.trim();
    const guru = $('guru').value;
    const qs = new URLSearchParams();
    if(tanggal) qs.set('tanggal', tanggal);
    if(kelas) qs.set('kelas', kelas);
    if(guru) qs.set('guru', guru);
    const js = await apiGet(`jurnal_guru_admin_list.php?${qs.toString()}`);
    const rows = js.data || [];
    if(rows.length===0){
      $('tb').innerHTML = `<tr><td colspan="6" class="p-4 text-center italic text-slate-500">Tidak ada data.</td></tr>`;
      return;
    }
    $('tb').innerHTML = rows.map(r=>`
      <tr class="hover:bg-slate-800">
        <td class="p-3 text-slate-300">${esc(r.tanggal)}</td>
        <td class="p-3 text-white font-black">${esc(r.guru_nama||r.guru_username)}</td>
        <td class="p-3 text-slate-300">${esc(r.kelas||'-')}</td>
        <td class="p-3 text-emerald-300 font-black">${esc(r.mapel||'-')}</td>
        <td class="p-3 text-slate-200">${esc(r.materi||'').slice(0,120)}${String(r.materi||'').length>120?'…':''}</td>
        <td class="p-3 text-slate-400">${esc(r.catatan||'')}</td>
      </tr>
    `).join('');
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    $('tanggal').value = ''; // default semua tanggal
    $('btnLoad').addEventListener('click', ()=>load().catch(e=>Swal.fire('Error',e.message,'error')));
    loadGuruSelect().catch(()=>{});
    load().catch(()=>{});
  });
</script>
</body>
</html>
