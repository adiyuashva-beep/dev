<?php
require __DIR__ . '/auth/guard.php';
require_login(['admin','super','kurikulum','kesiswaan','bk','staff']);

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tugas Tambahan Guru - Admin</title>
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
      <div class="text-2xl font-black text-white tracking-tight">Admin • <span class="text-yellow-400">Tugas Tambahan Guru</span></div>
      <div class="text-slate-400 text-sm mt-1">Buat tugas (ekstra/KSN/dll) dan pilih siswa per kelas.</div>
    </div>
    <div class="flex gap-2">
      <a href="/admin_full.php" class="btn bg-slate-800 hover:bg-slate-700">Kembali</a>
      <a href="/logout.php" class="btn bg-red-600 hover:bg-red-500 text-white">Logout</a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 pb-12 space-y-6">
    <section class="card p-6">
      <h2 class="text-lg font-black text-white">1) Set Wali Kelas (Tugas Pokok)</h2>
      <p class="text-slate-400 text-sm mt-1">Di sistem ini, wali kelas disimpan di kolom <b>kelas</b> milik akun guru. Satu guru = satu wali kelas.</p>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div>
          <div class="label">Guru</div>
          <select id="wali_guru" class="inp mt-1"></select>
        </div>
        <div>
          <div class="label">Kelas Wali</div>
          <input id="wali_kelas" class="inp mt-1" placeholder="Contoh: X IPA 1" />
        </div>
        <div class="flex items-end">
          <button id="btnSetWali" class="btn bg-emerald-600 hover:bg-emerald-500 text-white w-full">Simpan Wali</button>
        </div>
      </div>
    </section>

    <section class="card p-6">
      <h2 class="text-lg font-black text-white">2) Buat Tugas Tambahan (Tidak dibatasi)</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
        <div class="md:col-span-2">
          <div class="label">Nama Tugas</div>
          <input id="t_nama" class="inp mt-1" placeholder="Contoh: Pembina Basket" />
        </div>
        <div>
          <div class="label">Tipe</div>
          <select id="t_tipe" class="inp mt-1">
            <option value="ekstra">Ekstra</option>
            <option value="ksn">KSN</option>
            <option value="lainnya">Lainnya</option>
          </select>
        </div>
        <div>
          <div class="label">Mulai</div>
          <input id="t_mulai" type="date" class="inp mt-1" />
        </div>
        <div class="md:col-span-4">
          <div class="label">Deskripsi (opsional)</div>
          <textarea id="t_desc" rows="3" class="inp mt-1" placeholder="Keterangan singkat..."></textarea>
        </div>
      </div>
      <div class="mt-4 flex justify-end">
        <button id="btnCreate" class="btn bg-blue-600 hover:bg-blue-500 text-white">Buat Tugas</button>
      </div>
    </section>

    <section class="card p-6">
      <h2 class="text-lg font-black text-white">3) Pilih Siswa per Kelas → Masukkan ke Tugas</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
        <div>
          <div class="label">Pilih Tugas</div>
          <select id="a_tugas" class="inp mt-1"></select>
        </div>
        <div>
          <div class="label">Pilih Guru Pembimbing</div>
          <select id="a_guru" class="inp mt-1"></select>
        </div>
        <div>
          <div class="label">Filter Kelas</div>
          <input id="a_kelas" class="inp mt-1" placeholder="Contoh: X IPA 1" />
        </div>
        <div class="flex items-end">
          <button id="btnLoadSiswa" class="btn bg-slate-800 hover:bg-slate-700 w-full">Muat Siswa</button>
        </div>
      </div>

      <div class="mt-4 overflow-x-auto border border-slate-800 rounded-xl">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-950 text-slate-400 text-xs uppercase font-black">
            <tr>
              <th class="p-3 w-12"></th>
              <th class="p-3">Nama</th>
              <th class="p-3">Username/NISN</th>
              <th class="p-3">Kelas</th>
            </tr>
          </thead>
          <tbody id="tbSiswa" class="divide-y divide-slate-800">
            <tr><td colspan="4" class="p-4 text-center italic text-slate-500">Klik “Muat Siswa”.</td></tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4 flex flex-col md:flex-row md:justify-between gap-2">
        <div class="text-slate-400 text-sm">Centang siswa → klik Simpan Anggota.</div>
        <div class="flex gap-2">
          <button id="btnCheckAll" class="btn bg-slate-800 hover:bg-slate-700">Check All</button>
          <button id="btnSaveMembers" class="btn bg-emerald-600 hover:bg-emerald-500 text-white">Simpan Anggota</button>
        </div>
      </div>
    </section>

    <section class="card p-6">
      <h2 class="text-lg font-black text-white">4) Lihat Tugas per Guru</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div>
          <div class="label">Pilih Guru</div>
          <select id="l_guru" class="inp mt-1"></select>
        </div>
        <div class="flex items-end">
          <button id="btnLoadTugasGuru" class="btn bg-slate-800 hover:bg-slate-700 w-full">Muat</button>
        </div>
      </div>

      <div class="mt-4 overflow-x-auto border border-slate-800 rounded-xl">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-950 text-slate-400 text-xs uppercase font-black">
            <tr>
              <th class="p-3">Tugas</th>
              <th class="p-3">Tipe</th>
              <th class="p-3">Mulai</th>
              <th class="p-3 text-right">Anggota</th>
            </tr>
          </thead>
          <tbody id="tbTugasGuru" class="divide-y divide-slate-800">
            <tr><td colspan="4" class="p-4 text-center italic text-slate-500">Pilih guru.</td></tr>
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

  async function loadGuruSelects(){
    const js = await apiGet('tugas_admin_list_guru.php');
    const rows = js.data || [];
    const opts = [`<option value="">-- pilih --</option>`].concat(rows.map(r=>`<option value="${esc(r.username)}" data-kelas="${esc(r.kelas||'')}">${esc(r.nama||r.username)}${r.kelas? ' (Wali '+esc(r.kelas)+')':''}</option>`));
    $('wali_guru').innerHTML = opts.join('');
    $('a_guru').innerHTML = opts.join('');
    $('l_guru').innerHTML = opts.join('');
  }

  async function loadTugasSelect(){
    const js = await apiGet('tugas_admin_list.php');
    const rows = js.data || [];
    const opts = [`<option value="">-- pilih tugas --</option>`].concat(rows.map(r=>`<option value="${Number(r.id)}">${esc(r.nama_tugas)} (${esc(r.tipe||'-')})</option>`));
    $('a_tugas').innerHTML = opts.join('');
  }

  async function setWali(){
    const guru = $('wali_guru').value;
    const kelas = $('wali_kelas').value.trim();
    if(!guru) return Swal.fire('Wajib','Pilih guru','warning');
    await apiPost('tugas_admin_set_wali_kelas.php', {username:guru, kelas});
    await loadGuruSelects();
    Swal.fire('Sukses','Wali kelas tersimpan','success');
  }

  async function createTugas(){
    const nama_tugas = $('t_nama').value.trim();
    const tipe = $('t_tipe').value;
    const tanggal_mulai = $('t_mulai').value || isoToday();
    const deskripsi = $('t_desc').value.trim();
    if(!nama_tugas) return Swal.fire('Wajib','Nama tugas wajib','warning');
    await apiPost('tugas_admin_create.php', {nama_tugas, tipe, tanggal_mulai, deskripsi});
    $('t_nama').value=''; $('t_desc').value='';
    await loadTugasSelect();
    Swal.fire('Sukses','Tugas dibuat','success');
  }

  async function loadSiswa(){
    const kelas = $('a_kelas').value.trim();
    const js = await apiGet(`tugas_admin_list_siswa.php?kelas=${encodeURIComponent(kelas)}`);
    const rows = js.data || [];
    if(rows.length===0){
      $('tbSiswa').innerHTML = `<tr><td colspan="4" class="p-4 text-center italic text-slate-500">Tidak ada siswa.</td></tr>`;
      return;
    }
    $('tbSiswa').innerHTML = rows.map(r=>`
      <tr class="hover:bg-slate-800">
        <td class="p-3"><input type="checkbox" class="chk w-4 h-4" value="${esc(r.username)}" /></td>
        <td class="p-3 text-white font-bold">${esc(r.nama||r.username)}</td>
        <td class="p-3 text-slate-300">${esc(r.username)}</td>
        <td class="p-3 text-slate-300">${esc(r.kelas||'-')}</td>
      </tr>
    `).join('');
  }

  function getChecked(){
    return Array.from(document.querySelectorAll('.chk:checked')).map(x=>x.value);
  }

  async function saveMembers(){
    const tugas_id = Number($('a_tugas').value||0);
    const guru_username = $('a_guru').value;
    const members = getChecked();
    if(!tugas_id) return Swal.fire('Wajib','Pilih tugas','warning');
    if(!guru_username) return Swal.fire('Wajib','Pilih guru pembimbing','warning');
    if(members.length===0) return Swal.fire('Info','Belum ada siswa yang dicentang','info');
    await apiPost('tugas_admin_set_members.php', {tugas_id, guru_username, members});
    Swal.fire('Sukses','Anggota tersimpan','success');
  }

  async function loadTugasGuru(){
    const guru = $('l_guru').value;
    if(!guru) return Swal.fire('Wajib','Pilih guru','warning');
    const js = await apiGet(`tugas_admin_list.php?guru=${encodeURIComponent(guru)}`);
    const rows = js.data || [];
    if(rows.length===0){
      $('tbTugasGuru').innerHTML = `<tr><td colspan="4" class="p-4 text-center italic text-slate-500">Belum ada tugas.</td></tr>`;
      return;
    }
    $('tbTugasGuru').innerHTML = rows.map(r=>`
      <tr class="hover:bg-slate-800">
        <td class="p-3 text-white font-black">${esc(r.nama_tugas||'')}</td>
        <td class="p-3 text-slate-300">${esc(r.tipe||'-')}</td>
        <td class="p-3 text-slate-300">${esc(r.tanggal_mulai||'-')}</td>
        <td class="p-3 text-right"><button class="px-3 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 text-white font-black text-xs" onclick="lihatAnggota(${Number(r.id)})">Lihat</button></td>
      </tr>
    `).join('');
  }
  window.lihatAnggota = async (tugas_id)=>{
    try{
      const js = await apiGet(`tugas_admin_members.php?tugas_id=${encodeURIComponent(tugas_id)}`);
      const rows = js.data || [];
      const html = rows.length ? `<div class="text-left text-sm">${rows.map(x=>`<div>• <b>${esc(x.nama||x.username)}</b> <span class='text-slate-400'>(${esc(x.kelas||'-')})</span></div>`).join('')}</div>` : `<div class='text-slate-500 italic'>Belum ada anggota</div>`;
      Swal.fire({title:'Anggota', html});
    }catch(e){ Swal.fire('Error',e.message,'error'); }
  }

  document.addEventListener('DOMContentLoaded', async ()=>{
    $('t_mulai').value = isoToday();
    $('btnSetWali').addEventListener('click', ()=>setWali().catch(e=>Swal.fire('Error',e.message,'error')));
    $('btnCreate').addEventListener('click', ()=>createTugas().catch(e=>Swal.fire('Error',e.message,'error')));
    $('btnLoadSiswa').addEventListener('click', ()=>loadSiswa().catch(e=>Swal.fire('Error',e.message,'error')));
    $('btnSaveMembers').addEventListener('click', ()=>saveMembers().catch(e=>Swal.fire('Error',e.message,'error')));
    $('btnCheckAll').addEventListener('click', ()=>{ document.querySelectorAll('.chk').forEach(x=>x.checked=true); });
    $('btnLoadTugasGuru').addEventListener('click', ()=>loadTugasGuru().catch(e=>Swal.fire('Error',e.message,'error')));
    $('wali_guru').addEventListener('change', ()=>{ const opt = $('wali_guru').selectedOptions?.[0]; $('wali_kelas').value = opt?.dataset?.kelas || ''; });

    await loadGuruSelects().catch(()=>{});
    await loadTugasSelect().catch(()=>{});
  });
</script>
</body>
</html>
