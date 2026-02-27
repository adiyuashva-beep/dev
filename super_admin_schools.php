<?php
require __DIR__ . '/auth/guard.php';
require_login(['super']);
$schoolName = $_SESSION['school']['nama'] ?? 'EduGate';
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Super Admin • Schools</title>
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
      <div class="text-2xl font-black text-white tracking-tight">Super Admin • <span class="text-emerald-400">Tenant Schools</span></div>
      <div class="text-slate-400 text-sm mt-1">Kelola sekolah SaaS (subdomain, jenjang, admin pertama).</div>
    </div>
    <div class="flex gap-2">
      <a href="/admin_full.php" class="btn bg-slate-800 hover:bg-slate-700">Admin Panel</a>
      <a href="/logout.php" class="btn bg-red-600 hover:bg-red-500 text-white">Logout</a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 pb-12 space-y-6">
    <section class="card p-6">
      <div class="text-white font-black text-lg">Buat Sekolah Baru</div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div>
          <div class="label">Nama Sekolah</div>
          <input id="nama" class="inp mt-1" placeholder="SMA Negeri 1" />
        </div>
        <div>
          <div class="label">Jenjang</div>
          <select id="jenjang" class="inp mt-1">
            <option>SD</option><option>SMP</option><option selected>SMA</option>
          </select>
        </div>
        <div>
          <div class="label">Subdomain</div>
          <input id="subdomain" class="inp mt-1" placeholder="sma1" />
          <div class="text-xs text-slate-500 mt-1">contoh: <b>sma1</b>.edugate-tech.com</div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div>
          <div class="label">Admin Username</div>
          <input id="admin_username" class="inp mt-1" value="admin" />
        </div>
        <div>
          <div class="label">Admin Nama</div>
          <input id="admin_name" class="inp mt-1" value="Admin Sekolah" />
        </div>
        <div>
          <div class="label">Admin Password</div>
          <input id="admin_password" type="password" class="inp mt-1" placeholder="kosong = sama dengan username" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-4">
        <div>
          <div class="label">Max JP</div>
          <input id="max_jp" type="number" min="1" max="20" class="inp mt-1" value="12" />
        </div>
        <div class="flex items-end">
          <label class="flex items-center gap-2 text-sm"><input id="fitur_jurnal" type="checkbox" checked> Jurnal</label>
        </div>
        <div class="flex items-end">
          <label class="flex items-center gap-2 text-sm"><input id="fitur_7hebat" type="checkbox" checked> 7 Hebat</label>
        </div>
        <div class="flex items-end">
          <label class="flex items-center gap-2 text-sm"><input id="fitur_cuti_guru" type="checkbox" checked> Cuti Guru</label>
        </div>
        <div class="flex items-end">
          <label class="flex items-center gap-2 text-sm"><input id="fitur_gps" type="checkbox" checked> GPS</label>
        </div>
      </div>

      <div class="mt-5">
        <button id="btnCreate" class="btn bg-emerald-600 hover:bg-emerald-500 text-white">Create School</button>
      </div>
    </section>

    <section class="card p-6">
      <div class="flex items-center justify-between">
        <div class="text-white font-black text-lg">Daftar Schools</div>
        <button id="btnReload" class="btn bg-slate-800 hover:bg-slate-700">Reload</button>
      </div>
      <div class="overflow-x-auto border border-slate-800 rounded-xl mt-4">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-950 text-slate-400 text-xs uppercase font-black">
            <tr>
              <th class="p-3">ID</th>
              <th class="p-3">Nama</th>
              <th class="p-3">Jenjang</th>
              <th class="p-3">Subdomain</th>
              <th class="p-3">Status</th>
              <th class="p-3">Created</th>
            </tr>
          </thead>
          <tbody id="tb" class="divide-y divide-slate-800"></tbody>
        </table>
      </div>
    </section>
  </main>

<script>
  const BASE_API='/api';
  const $=(id)=>document.getElementById(id);
  const esc=(s)=>String(s??'').replace(/[&<>"']/g,m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"}[m]));

  async function apiGet(path){
    const res = await fetch(`${BASE_API}/${path}`, {credentials:'include'});
    const js = await res.json();
    if(!js.ok) throw new Error(js.error||'Error');
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
    if(!js.ok) throw new Error(js.error||'Error');
    return js;
  }

  function readSettings(){
    return {
      max_jp: parseInt($('max_jp').value||'12',10) || 12,
      fitur_jurnal: $('fitur_jurnal').checked,
      fitur_7hebat: $('fitur_7hebat').checked,
      fitur_cuti_guru: $('fitur_cuti_guru').checked,
      fitur_gps: $('fitur_gps').checked,
    };
  }

  async function reload(){
    const js = await apiGet('super_school_list.php');
    const rows = js.data || [];
    $('tb').innerHTML = rows.map(r=>`
      <tr class="hover:bg-slate-800">
        <td class="p-3 text-slate-300">${esc(r.id)}</td>
        <td class="p-3 text-white font-black">${esc(r.nama_sekolah)}</td>
        <td class="p-3 text-slate-300">${esc(r.jenjang)}</td>
        <td class="p-3 text-emerald-300 font-black">${esc(r.subdomain)}</td>
        <td class="p-3 text-slate-300">${esc(r.status)}</td>
        <td class="p-3 text-slate-500">${esc(r.created_at||'')}</td>
      </tr>
    `).join('') || `<tr><td colspan="6" class="p-4 text-center italic text-slate-500">Kosong</td></tr>`;
  }

  async function createSchool(){
    const payload = {
      nama_sekolah: $('nama').value.trim(),
      jenjang: $('jenjang').value,
      subdomain: $('subdomain').value.trim(),
      admin_username: $('admin_username').value.trim(),
      admin_name: $('admin_name').value.trim(),
      admin_password: $('admin_password').value,
      settings: readSettings()
    };
    await apiPost('super_school_create.php', payload);
    Swal.fire('OK','Sekolah berhasil dibuat','success');
    $('nama').value=''; $('subdomain').value=''; $('admin_password').value='';
    await reload();
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    $('btnReload').addEventListener('click', ()=>reload().catch(e=>Swal.fire('Error',e.message,'error')));
    $('btnCreate').addEventListener('click', ()=>createSchool().catch(e=>Swal.fire('Error',e.message,'error')));
    reload().catch(()=>{});
  });
</script>
</body>
</html>
