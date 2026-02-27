<?php
require __DIR__ . '/auth/guard.php';
require_login(['admin','super','kesiswaan','kurikulum','bk','staff']);

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Monitoring 7 Hebat - Admin</title>
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
      <div class="text-2xl font-black text-white tracking-tight">Admin • <span class="text-blue-400">Monitoring 7 Hebat</span></div>
      <div class="text-slate-400 text-sm mt-1">Ringkasan & validasi per kelas.</div>
    </div>
    <div class="flex gap-2">
      <a href="/admin_full.php" class="btn bg-slate-800 hover:bg-slate-700">Kembali</a>
      <a href="/logout.php" class="btn bg-red-600 hover:bg-red-500 text-white">Logout</a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 pb-12 space-y-6">
    <section class="card p-6">
      <div class="flex flex-col md:flex-row md:items-end gap-3">
        <div class="flex-1">
          <div class="label">Tanggal</div>
          <input id="tanggal" type="date" class="inp mt-1" />
        </div>
        <button id="btnLoad" class="btn bg-blue-600 hover:bg-blue-500 text-white">Muat Ringkasan</button>
      </div>
    </section>

    <section class="card p-6">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-black text-white">Ringkasan Per Kelas</h2>
        <div class="text-slate-400 text-sm">Klik kelas untuk detail</div>
      </div>
      <div class="mt-4 overflow-x-auto border border-slate-800 rounded-xl">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-950 text-slate-400 text-xs uppercase font-black">
            <tr>
              <th class="p-3">Kelas</th>
              <th class="p-3">Total</th>
              <th class="p-3">Submitted</th>
              <th class="p-3">Valid</th>
              <th class="p-3">Reject</th>
              <th class="p-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody id="tbSum" class="divide-y divide-slate-800">
            <tr><td colspan="6" class="p-4 text-center italic text-slate-500">Silakan muat data.</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card p-6">
      <div class="flex flex-col md:flex-row md:items-end gap-3">
        <div class="flex-1">
          <div class="label">Detail Kelas</div>
          <input id="kelas" class="inp mt-1" placeholder="Contoh: X IPA 1" />
        </div>
        <button id="btnDetail" class="btn bg-slate-800 hover:bg-slate-700">Muat Detail</button>
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
          <tbody id="tbDet" class="divide-y divide-slate-800">
            <tr><td colspan="4" class="p-4 text-center italic text-slate-500">Pilih kelas dulu.</td></tr>
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
  async function apiPost(path, body){
    const res = await fetch(`${BASE_API}/${path}`, {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body:JSON.stringify(body||{})});
    const js = await res.json();
    if(!js.ok) throw new Error(js.error||'Error');
    return js;
  }

  function ringkas7(data_json){
    let d={};
    try{ d = (typeof data_json==='string')? JSON.parse(data_json): (data_json||{});}catch(e){ d={}; }
    const n = ['k1','k2','k3','k4','k5','k6','k7'].reduce((a,k)=>a+(d[k]?1:0),0);
    return `${n}/7`;
  }

  async function loadSummary(){
    const tanggal = $('tanggal').value || isoToday();
    const js = await apiGet(`7hebat_summary.php?tanggal=${encodeURIComponent(tanggal)}`);
    const rows = js.data || [];
    if(rows.length===0){
      $('tbSum').innerHTML = `<tr><td colspan="6" class="p-4 text-center italic text-slate-500">Belum ada data.</td></tr>`;
      return;
    }
    $('tbSum').innerHTML = rows.map(r=>`
      <tr class="hover:bg-slate-800">
        <td class="p-3 text-white font-black">${esc(r.kelas||'-')}</td>
        <td class="p-3 text-slate-300">${Number(r.total||0)}</td>
        <td class="p-3 text-slate-300">${Number(r.submitted||0)}</td>
        <td class="p-3 text-emerald-300 font-black">${Number(r.valid||0)}</td>
        <td class="p-3 text-red-300 font-black">${Number(r.reject||0)}</td>
        <td class="p-3 text-right">
          <button class="px-3 py-1 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-black text-xs" onclick="pilihKelas('${esc(r.kelas||'')}')">Detail</button>
        </td>
      </tr>
    `).join('');
  }

  window.pilihKelas = (k)=>{
    $('kelas').value = k;
    loadDetail().catch(e=>Swal.fire('Error',e.message,'error'));
  }

  async function loadDetail(){
    const tanggal = $('tanggal').value || isoToday();
    const kelas = $('kelas').value.trim();
    if(!kelas) return Swal.fire('Info','Isi kelas dulu','warning');
    const js = await apiGet(`7hebat_list_kelas.php?tanggal=${encodeURIComponent(tanggal)}&kelas=${encodeURIComponent(kelas)}`);
    const rows = js.data || [];
    if(rows.length===0){
      $('tbDet').innerHTML = `<tr><td colspan="4" class="p-4 text-center italic text-slate-500">Belum ada data.</td></tr>`;
      return;
    }
    $('tbDet').innerHTML = rows.map(r=>`
      <tr class="hover:bg-slate-800">
        <td class="p-3 text-white font-black">${esc(r.nama||r.username)}</td>
        <td class="p-3"><span class="px-2 py-1 rounded bg-slate-900 border border-slate-700 text-xs font-black">${esc(r.status||'submitted')}</span></td>
        <td class="p-3 text-slate-300">${ringkas7(r.data_json)}</td>
        <td class="p-3 text-right"><button class="px-3 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 text-white font-black text-xs" onclick="lihat7('${esc(r.username)}','${esc(r.tanggal)}')">Detail</button></td>
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
        await loadDetail();
        await loadSummary();
        Swal.fire('OK','Tervalidasi','success');
      } else if(res.isDenied){
        const { value: note } = await Swal.fire({title:'Alasan reject (opsional)', input:'text', showCancelButton:true});
        await apiPost('7hebat_validate.php', {username, tanggal, action:'reject', note: note||''});
        await loadDetail();
        await loadSummary();
        Swal.fire('OK','Direject','success');
      }
    }catch(e){
      Swal.fire('Error', e.message, 'error');
    }
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    $('tanggal').value = isoToday();
    $('btnLoad').addEventListener('click', ()=>loadSummary().catch(e=>Swal.fire('Error',e.message,'error')));
    $('btnDetail').addEventListener('click', ()=>loadDetail().catch(e=>Swal.fire('Error',e.message,'error')));
    loadSummary().catch(()=>{});
  });
</script>
</body>
</html>
