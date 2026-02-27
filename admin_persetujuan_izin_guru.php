<?php
require __DIR__ . '/auth/guard.php';
require_login(['admin','super','kesiswaan','kurikulum','bk','staff']);

$schoolName = $_SESSION['school']['nama'] ?? 'EduGate';
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Persetujuan Izin/Cuti Guru • <?= htmlspecialchars((string)$schoolName) ?></title>
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
      <div class="text-2xl font-black text-white tracking-tight">Admin • <span class="text-emerald-400">Persetujuan Izin/Cuti Guru</span></div>
      <div class="text-slate-400 text-sm mt-1"><?= htmlspecialchars((string)$schoolName) ?> • Approve/Reject + catatan.</div>
    </div>
    <div class="flex gap-2">
      <a href="/admin_full.php" class="btn bg-slate-800 hover:bg-slate-700">Kembali</a>
      <a href="/logout.php" class="btn bg-red-600 hover:bg-red-500 text-white">Logout</a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 pb-12 space-y-6">
    <section class="card p-6">
      <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div>
          <div class="label">Dari</div>
          <input id="from" type="date" class="inp mt-1" />
        </div>
        <div>
          <div class="label">Sampai</div>
          <input id="to" type="date" class="inp mt-1" />
        </div>
        <div>
          <div class="label">Status</div>
          <select id="status" class="inp mt-1">
            <option value="submitted">Submitted</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <div class="label">Cari (nama/username/jenis)</div>
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
              <th class="p-3">Tanggal</th>
              <th class="p-3">Nama</th>
              <th class="p-3">Username</th>
              <th class="p-3">Jenis</th>
              <th class="p-3">Hari</th>
              <th class="p-3">Bukti</th>
              <th class="p-3">Status</th>
              <th class="p-3">Aksi</th>
            </tr>
          </thead>
          <tbody id="tb" class="divide-y divide-slate-800">
            <tr><td colspan="8" class="p-4 text-center italic text-slate-500">Silakan muat data.</td></tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>

<script>
  const BASE_API = '/api';
  const $ = (id)=>document.getElementById(id);
  const esc = (s)=>String(s??'').replace(/[&<>"']/g, m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"}[m]));
  const iso = (d)=>{ const p=n=>String(n).padStart(2,'0'); return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}`; };

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

  function badge(status){
    const m = {
      submitted: 'bg-yellow-500/10 border-yellow-500/30 text-yellow-300',
      approved: 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300',
      rejected: 'bg-red-500/10 border-red-500/30 text-red-300'
    };
    const cls = m[status] || 'bg-slate-900 border-slate-700 text-slate-300';
    return `<span class="px-2 py-1 rounded border text-xs font-black ${cls}">${esc(status||'-')}</span>`;
  }

  async function load(){
    const from = $('from').value;
    const to = $('to').value;
    const status = $('status').value;
    const q = $('q').value.trim();

    const js = await apiGet(`absensi_guru_ket_admin_list.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&status=${encodeURIComponent(status)}&q=${encodeURIComponent(q)}`);
    const rows = js.data || [];
    if(rows.length===0){
      $('tb').innerHTML = `<tr><td colspan="8" class="p-4 text-center italic text-slate-500">Tidak ada data.</td></tr>`;
      return;
    }

    const link = (u)=> u ? `<a href="${esc(u)}" target="_blank" class="text-emerald-300 font-black hover:underline">Lihat</a>` : '<span class="text-slate-600">-</span>';

    $('tb').innerHTML = rows.map(r=>{
      const canAction = (r.status === 'submitted');
      return `
        <tr class="hover:bg-slate-800">
          <td class="p-3 text-slate-300">${esc(r.tanggal)}</td>
          <td class="p-3 text-white font-black">${esc(r.nama)}</td>
          <td class="p-3 text-slate-300">${esc(r.username)}</td>
          <td class="p-3 text-slate-300">${esc(r.jenis)}</td>
          <td class="p-3 text-slate-300">${esc(r.jumlah_hari ?? '-')}</td>
          <td class="p-3">${link(r.bukti_url)}</td>
          <td class="p-3">${badge(r.status)}</td>
          <td class="p-3">
            ${canAction ? `
              <div class="flex gap-2">
                <button class="btn bg-emerald-600 hover:bg-emerald-500 text-white" onclick="act(${r.id},'approve')">Approve</button>
                <button class="btn bg-red-600 hover:bg-red-500 text-white" onclick="act(${r.id},'reject')">Reject</button>
              </div>
            ` : `<div class="text-xs text-slate-400">${esc(r.validator_name||'-')}<br>${esc(r.validated_at||'')}</div>`}
          </td>
        </tr>
      `;
    }).join('');
  }

  async function act(id, action){
    const { value: note } = await Swal.fire({
      title: action==='approve' ? 'Approve pengajuan' : 'Reject pengajuan',
      input: 'textarea',
      inputLabel: 'Catatan (opsional)',
      inputPlaceholder: 'contoh: bukti kurang jelas / disetujui',
      showCancelButton: true,
      confirmButtonText: action==='approve' ? 'Approve' : 'Reject'
    });
    if (note === undefined) return;

    await apiPost('absensi_guru_ket_admin_action.php', {id, action, note});
    await load();
    Swal.fire('OK','Status diperbarui','success');
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    const today = new Date();
    $('to').value = iso(today);
    const from = new Date(); from.setDate(from.getDate()-7);
    $('from').value = iso(from);

    $('btnLoad').addEventListener('click', ()=>load().catch(e=>Swal.fire('Error',e.message,'error')));
    load().catch(()=>{});
  });
</script>
</body>
</html>
