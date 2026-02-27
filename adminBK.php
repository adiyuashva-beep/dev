<?php
require __DIR__ . '/auth/guard.php';
require_login(['super','admin','bk','kesiswaan']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Tim Kesiswaan - EduGate SMAN 1 Karanganyar</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #020617; color: #f1f5f9; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .nav-item.active { background: rgba(16, 185, 129, 0.1); border-right: 4px solid #10b981; color: #34d399; }
        
        /* Table Sticky Optimization */
        .table-sticky th { position: sticky; top: 0; z-index: 30; background: #1e293b; border-bottom: 2px solid #334155; }
        .table-sticky td:first-child, .table-sticky th:first-child { position: sticky; left: 0; z-index: 40; background: #0f172a; border-right: 1px solid #334155; }
        .table-sticky td:nth-child(2), .table-sticky th:nth-child(2) { position: sticky; left: 40px; z-index: 40; background: #0f172a; border-right: 1px solid #334155; }

        .bg-weekend { background-color: #1e293b; color: #475569; }
        .bg-hadir { background-color: #064e3b; color: #a7f3d0; border: 0.1px solid #065f46; }
        .bg-dispen { background-color: #4c1d95; color: #ddd6fe; font-weight: 900; }
        .bg-sakit { background-color: #1e3a8a; color: #bfdbfe; font-weight: 900; }
        .bg-izin { background-color: #713f12; color: #fef08a; font-weight: 900; }
        .bg-alpha { background-color: #450a0a; color: #fca5a5; font-weight: 900; }

        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-sm">

    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col hidden md:flex">
        <div class="p-6">
            <h1 class="font-black text-2xl tracking-tighter text-emerald-500 italic">EduGate</h1>
            <p class="text-[10px] text-slate-500 tracking-widest uppercase mt-1">SMAN 1 Karanganyar</p>
            <div class="mt-2 inline-block px-2 py-1 rounded bg-blue-900/30 text-blue-400 text-[9px] font-bold border border-blue-800 uppercase">
                PANEL TIM KESISWAAN
            </div>
        </div>

        <nav class="flex-1 space-y-1 px-3 mt-4 overflow-y-auto hide-scroll">
            <button onclick="window.open('guru.html', '_blank')" class="w-full flex items-center gap-3 px-4 py-3 bg-slate-800 text-yellow-400 hover:bg-slate-700 hover:text-yellow-300 rounded-lg transition font-bold border border-slate-700 mb-4 shadow-lg">
                <i data-lucide="book-open-check" class="w-5 h-5"></i> MODE GURU / JURNAL
            </button>
            
            <div class="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Menu Admin</div>

            <button onclick="nav('dashboard')" id="nav-dashboard" class="nav-item active w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-lg transition font-bold">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </button>
            <button onclick="nav('keluar')" id="nav-keluar" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-lg transition font-bold">
                <i data-lucide="door-open" class="w-5 h-5"></i> Izin Keluar-Masuk
            </button>
            <button onclick="nav('riwayat')" id="nav-riwayat" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-lg transition font-bold">
                <i data-lucide="archive" class="w-5 h-5"></i> Arsip Sakit & Izin
            </button>
            <button onclick="nav('rekap')" id="nav-rekap" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-lg transition font-bold">
                <i data-lucide="calendar-check" class="w-5 h-5"></i> Rekap Detail Jam
            </button>
            <button onclick="nav('dispen')" id="nav-dispen" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-lg transition font-bold">
                <i data-lucide="file-badge-2" class="w-5 h-5"></i> Kelola Dispensasi
            </button>
            <button onclick="nav('mbg')" id="nav-mbg" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-lg transition font-bold">
                <i data-lucide="utensils" class="w-5 h-5"></i> Program MBG
            </button>
            <div class="my-4 border-t border-slate-800"></div>
            <button onclick="nav('libur')" id="nav-libur" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-lg transition font-bold">
                <i data-lucide="calendar-off" class="w-5 h-5"></i> Hari Libur
            </button>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <button onclick="logout()" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-red-900/20 text-red-500 rounded-lg font-bold uppercase text-[10px] tracking-widest hover:bg-red-600 hover:text-white transition">
                <i data-lucide="power" class="w-4 h-4"></i> Keluar Panel
            </button>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-slate-950 p-4 md:p-8 relative">
        
        <div id="view-dashboard" class="space-y-6 fade-in">
            <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter">Live Monitoring</h2>
                    <p class="text-slate-400 text-sm">Waktu Sistem: <span id="jam-real" class="font-mono text-emerald-400 font-bold">00:00:00</span></p>
                </div>
                <div class="flex gap-2">
                    <button onclick="tarikDataSiswa(true)" class="bg-slate-800 text-white px-4 py-2 rounded-lg font-bold text-[10px] uppercase border border-slate-700 tracking-widest">Refresh Master</button>
                    <button onclick="window.open('monitor.html', '_blank')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold text-[10px] uppercase shadow-lg shadow-indigo-500/20 tracking-widest italic">Layar TV</button>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl"><p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Hadir</p><h3 class="text-3xl font-black text-emerald-400 mt-1" id="stat-hadir">0</h3></div>
                <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl"><p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Sakit/Izin</p><h3 class="text-3xl font-black text-blue-400 mt-1" id="stat-izin">0</h3></div>
                <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl"><p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Belum Hadir</p><h3 class="text-3xl font-black text-rose-500 mt-1" id="stat-alpha">0</h3></div>
                <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl"><p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Total Siswa</p><h3 class="text-3xl font-black text-white mt-1" id="stat-total">0</h3></div>
            </div>

            <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
                <div class="p-4 border-b border-slate-800 bg-slate-900/50 flex justify-between items-center">
                    <h3 class="font-bold text-white text-xs uppercase tracking-widest italic">Aktivitas Terbaru Hari Ini</h3>
                </div>
                <div class="overflow-x-auto max-h-[450px]">
                    <table class="w-full text-left text-slate-400">
                        <thead class="bg-slate-950 text-[10px] uppercase font-bold text-slate-500 sticky top-0">
                            <tr><th class="p-4">Jam In</th><th class="p-4">Jam Out</th><th class="p-4">Siswa</th><th class="p-4">Kelas</th><th class="p-4">Status</th><th class="p-4 text-center">Foto</th></tr>
                        </thead>
                        <tbody id="tabel-live" class="divide-y divide-slate-800"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="view-keluar" class="hidden space-y-6 fade-in">
            <div class="flex flex-col md:flex-row justify-between items-end gap-4">
                <div>
                    <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter">Log Keluar Gerbang</h2>
                    <p class="text-slate-400 text-sm">Monitoring perizinan keluar-masuk area sekolah.</p>
                </div>
                <div class="flex gap-2 bg-slate-900 p-3 rounded-2xl border border-slate-800 shadow-2xl">
                    <input type="date" id="keluar-start" class="bg-slate-800 text-white text-xs p-2 rounded-lg border border-slate-700 outline-none">
                    <input type="date" id="keluar-end" class="bg-slate-800 text-white text-xs p-2 rounded-lg border border-slate-700 outline-none">
                    <button onclick="cariRiwayatKeluar()" class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded-lg font-bold text-[10px] uppercase tracking-widest">Filter</button>
                    <button onclick="downloadExcel('table-izin-keluar', 'LOG_KELUAR_MASUK')" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg font-bold text-[10px] uppercase"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i></button>
                </div>
            </div>
            <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden">
                <div class="overflow-x-auto h-[65vh]">
                    <table id="table-izin-keluar" class="w-full text-left">
                        <thead class="bg-slate-950 text-[10px] uppercase font-bold text-slate-500 sticky top-0">
                            <tr><th class="p-4">Tanggal</th><th class="p-4">Nama Siswa</th><th class="p-4">Kelas</th><th class="p-4 text-orange-400">Jam Keluar</th><th class="p-4">Alasan</th><th class="p-4 text-emerald-400">Jam Kembali</th><th class="p-4 text-center">Status</th></tr>
                        </thead>
                        <tbody id="tbody-izin-keluar" class="divide-y divide-slate-800 text-slate-400"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="view-riwayat" class="hidden space-y-6 fade-in">
            <div class="flex flex-col md:flex-row justify-between items-end gap-4">
                <div>
                    <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter">Arsip Sakit & Izin</h2>
                    <p class="text-xs text-slate-400 mt-1 italic italic">Verifikasi bukti surat & alasan perizinan siswa.</p>
                </div>
                <div class="flex gap-2 bg-slate-900 p-3 rounded-2xl border border-slate-800 shadow-2xl">
                    <input type="date" id="riwayat-start" class="bg-slate-800 text-white text-xs p-2 rounded-lg border border-slate-700 outline-none">
                    <input type="date" id="riwayat-end" class="bg-slate-800 text-white text-xs p-2 rounded-lg border border-slate-700 outline-none">
                    <button onclick="cariRiwayatIzin()" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-widest">Cari Data</button>
                    <button onclick="downloadExcel('table-arsip-izin', 'ARSIP_SAKIT_IZIN')" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg font-bold text-[10px] uppercase"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i></button>
                </div>
            </div>

            <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto h-[65vh]">
                    <table id="table-arsip-izin" class="w-full text-left text-slate-400">
                        <thead class="bg-slate-950 text-[10px] uppercase font-bold text-slate-500 sticky top-0">
                            <tr>
                                <th class="p-4">Tanggal</th>
                                <th class="p-4">Nama Siswa</th>
                                <th class="p-4">Kelas</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Alasan / Ket</th> <th class="p-4 text-center">Bukti Foto</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-riwayat-izin" class="divide-y divide-slate-800">
                            <tr><td colspan="6" class="p-20 text-center italic text-slate-500 uppercase text-[10px] tracking-widest">Masukkan Rentang Tanggal</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="view-rekap" class="hidden space-y-6 fade-in">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-4">
                <div>
                    <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter">Rekap Detail Jam</h2>
                    <p class="text-xs text-slate-400 mt-1 italic">Tabel Jam Masuk (atas) & Jam Pulang (bawah) model Pejagoan.</p>
                </div>
                <div class="flex flex-wrap gap-2 bg-slate-900 p-3 rounded-2xl border border-slate-800 shadow-xl">
                    <select id="pilih-bulan" class="bg-slate-800 text-white text-[10px] rounded-lg px-3 py-2 outline-none border border-slate-700 font-bold"></select>
                    <select id="pilih-tahun" class="bg-slate-800 text-white text-[10px] rounded-lg px-3 py-2 outline-none border border-slate-700 font-bold"></select>
                    <select id="pilih-kelas" class="bg-slate-800 text-white text-[10px] rounded-lg px-3 py-2 outline-none border border-slate-700 font-bold"><option value="">- KELAS -</option></select>
                    <button onclick="loadRekap()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-widest transition active:scale-95">Tampilkan</button>
                    <button onclick="downloadExcel('tabel-rekap', 'REKAP_DETAIL_JAM')" class="bg-emerald-800 text-white px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-widest flex items-center gap-2"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Excel</button>
                </div>
            </div>
            <div class="flex-1 bg-slate-900 rounded-2xl border border-slate-800 overflow-auto relative h-[70vh] shadow-2xl">
                <table id="tabel-rekap" class="w-full text-center text-[10px] border-collapse table-sticky whitespace-nowrap">
                    <thead class="text-slate-300 font-bold uppercase tracking-tighter" id="thead-rekap"></thead>
                    <tbody id="tbody-rekap" class="text-slate-400 divide-y divide-slate-800"></tbody>
                </table>
            </div>
        </div>

        <div id="view-dispen" class="hidden space-y-6 fade-in">
             <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter">Data Dispensasi (D)</h2>
             <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                 <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800">
                     <h3 class="font-bold text-white mb-4 uppercase text-xs italic">Input Dispen</h3>
                     <div class="space-y-4">
                         <div><label class="text-[10px] text-slate-500 uppercase font-bold">Cari Siswa</label><input type="text" id="inputCariSiswa" onkeyup="cariSiswaLokal()" class="w-full bg-slate-800 text-white p-3 rounded-lg border border-slate-700 mt-1" placeholder="Nama/NISN...">
                         <div id="hasilCariSiswa" class="hidden bg-slate-800 border border-slate-700 mt-1 rounded-lg max-h-40 overflow-y-auto absolute w-60 z-50 shadow-xl"></div></div>
                         <input type="hidden" id="inputNisnSiswa"><input type="hidden" id="inputKelasSiswa">
                         <div><label class="text-[10px] text-slate-500 uppercase font-bold">Terpilih</label><input type="text" id="displayNamaSiswa" class="w-full bg-slate-950 text-emerald-400 p-3 rounded-lg border border-slate-700 mt-1 font-bold" readonly placeholder="-"></div>
                         <div class="grid grid-cols-2 gap-2">
                             <div><label class="text-[10px] text-slate-500 uppercase font-bold">Mulai</label><input type="date" id="inputStart" class="w-full bg-slate-800 text-white p-2 rounded-lg border border-slate-700 mt-1"></div>
                             <div><label class="text-[10px] text-slate-500 uppercase font-bold">Selesai</label><input type="date" id="inputEnd" class="w-full bg-slate-800 text-white p-2 rounded-lg border border-slate-700 mt-1"></div>
                         </div>
                         <div><label class="text-[10px] text-slate-500 uppercase font-bold">Alasan</label><select id="inputAlasan" class="w-full bg-slate-800 text-white p-3 rounded-lg border border-slate-700 mt-1"><option value="Lomba">Kegiatan Lomba</option><option value="Tugas">Tugas Sekolah</option><option value="Dispen">Izin Khusus</option></select></div>
                         <button onclick="simpanDispen()" class="w-full bg-purple-600 text-white font-bold py-3 rounded-lg transition uppercase text-xs tracking-widest shadow-lg shadow-purple-500/20 active:scale-95">Simpan Data</button>
                     </div>
                 </div>
                 <div class="md:col-span-2 bg-slate-900 p-6 rounded-2xl border border-slate-800">
                    <h3 class="font-bold text-white mb-4 uppercase text-xs italic">Data Dispen</h3>
                    <div class="overflow-y-auto max-h-[500px]"><table class="w-full text-left text-xs"><thead class="bg-slate-950 text-slate-500 uppercase"><tr><th class="p-3">Siswa</th><th class="p-3">Tanggal</th><th class="p-3">Alasan</th><th class="p-3 text-right">Aksi</th></tr></thead><tbody id="tbody-dispen" class="divide-y divide-slate-800 text-slate-400"></tbody></table></div>
                 </div>
             </div>
        </div>

        <div id="view-mbg" class="hidden space-y-6 fade-in">
             <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter">Program MBG</h2>
             <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-slate-900 rounded-2xl border border-slate-800 p-6 group hover:border-emerald-500 cursor-pointer transition shadow-xl" onclick="previewMBG('X')"><h3 class="text-xl font-black text-white italic">TINGKAT X</h3><p class="text-xs text-slate-400 mt-2 uppercase tracking-widest font-bold">Cek Katering</p></div>
                <div class="bg-slate-900 rounded-2xl border border-slate-800 p-6 group hover:border-emerald-500 cursor-pointer transition shadow-xl" onclick="previewMBG('XI')"><h3 class="text-xl font-black text-white italic">TINGKAT XI</h3><p class="text-xs text-slate-400 mt-2 uppercase tracking-widest font-bold">Cek Katering</p></div>
                <div class="bg-slate-900 rounded-2xl border border-slate-800 p-6 group hover:border-emerald-500 cursor-pointer transition shadow-xl" onclick="previewMBG('XII')"><h3 class="text-xl font-black text-white italic">TINGKAT XII</h3><p class="text-xs text-slate-400 mt-2 uppercase tracking-widest font-bold">Cek Katering</p></div>
             </div>
             <div id="container-tabel-mbg" class="hidden bg-white text-slate-900 rounded-2xl p-6 shadow-2xl mt-6">
                <div class="flex justify-between items-center mb-4"><h3 class="font-bold text-lg text-slate-800" id="judul-tabel-mbg">Detail MBG</h3><button onclick="downloadExcel('tabel-mbg-detail', 'LOGISTIK_MBG')" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-widest">Download Excel</button></div>
                <div class="overflow-x-auto"><table id="tabel-mbg-detail" class="w-full text-sm border-collapse"><thead class="bg-slate-100"><tr><th class="border p-3 text-left">KELAS</th><th class="border p-3 w-32">SISWA</th><th class="border p-3 bg-green-100 text-green-800 font-black italic">MAKAN (HADIR)</th><th class="border p-3 bg-red-100 text-red-800 italic">SISA (TIDAK HADIR)</th></tr></thead><tbody id="tbody-mbg" class="text-center font-bold"></tbody></table></div>
             </div>
        </div>

        <div id="view-libur" class="hidden space-y-6 fade-in">
             <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800">
                    <h3 class="font-bold text-white mb-4 uppercase text-xs italic">Set Hari Libur</h3>
                    <div class="space-y-4">
                        <input type="date" id="inputTglLibur" class="w-full bg-slate-800 text-white p-3 rounded-lg border border-slate-700">
                        <input type="text" id="inputKetLibur" class="w-full bg-slate-800 text-white p-3 rounded-lg border border-slate-700" placeholder="Keterangan Libur...">
                        <button onclick="simpanLibur()" class="w-full bg-red-600 text-white font-bold py-3 rounded-lg transition uppercase text-xs tracking-widest">Simpan Tanggal</button>
                    </div>
                </div>
                <div class="md:col-span-2 bg-slate-900 p-6 rounded-2xl border border-slate-800">
                    <h3 class="font-bold text-white mb-4 uppercase text-xs">Kalender Libur</h3>
                    <div class="overflow-y-auto max-h-96"><table class="w-full text-left text-xs"><thead class="bg-slate-950 text-slate-500 uppercase"><tr><th class="p-3">Tanggal</th><th class="p-3">Keterangan</th><th class="p-3 text-right">Aksi</th></tr></thead><tbody id="tbody-libur" class="divide-y divide-slate-800 text-slate-400 font-bold"></tbody></table></div>
                </div>
             </div>
        </div>
    </main>

    <div id="loading" class="fixed inset-0 bg-slate-950 flex flex-col items-center justify-center z-[100] text-white">
        <div class="w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="font-black animate-pulse uppercase tracking-[0.3em] text-[10px]">EduGate SMAN 1 Karanganyar</p>
    </div>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js";
        import { getFirestore, collection, query, where, getDocs, onSnapshot, doc, setDoc, addDoc, deleteDoc, orderBy, limit, serverTimestamp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-firestore.js";

        // CONFIG FIREBASE - KARANGANYAR
        const firebaseConfig = { apiKey: "AIzaSyBTanYQQ-oPkO_Zw9cg_H5V0jIJvt_x72c", authDomain: "edugate-smansakra.firebaseapp.com", projectId: "edugate-smansakra", storageBucket: "edugate-smansakra.firebasestorage.app", messagingSenderId: "181623194937", appId: "1:181623194937:web:d494810276000dceda14e8" };
        const app = initializeApp(firebaseConfig); const db = getFirestore(app);

        let listSiswa = []; let mapKelas = {}; let cacheAbsenHariIni = {}; let cacheHariLibur = {}; 
        let cacheDispen = {}; let cacheRekapData = {}; 

        document.addEventListener('DOMContentLoaded', async () => {
            lucide.createIcons();
            setupDropdown();
            try {
                await tarikDataSiswa(); 
                pantauRealtime();
                loadHariLibur(); 
                loadDispen();
            } catch (e) { console.error(e); } 
            finally { if(document.getElementById('loading')) document.getElementById('loading').style.display = 'none'; }
        });

        window.nav = (v) => { 
            document.querySelectorAll('[id^="view-"]').forEach(e => e.classList.add('hidden')); 
            document.querySelectorAll('.nav-item').forEach(e => e.classList.remove('active')); 
            document.getElementById('view-'+v).classList.remove('hidden'); 
            document.getElementById('nav-'+v).classList.add('active'); 
        }

        // --- 1. LALU LINTAS KELUAR-MASUK ---
        window.cariRiwayatKeluar = async () => {
            const start = document.getElementById('keluar-start').value;
            const end = document.getElementById('keluar-end').value;
            const tbody = document.getElementById('tbody-izin-keluar');
            if(!start || !end) return Swal.fire('Pilih Tanggal','','warning');
            
            tbody.innerHTML = '<tr><td colspan="7" class="p-20 text-center animate-pulse text-[10px] tracking-widest uppercase">Mencari Log...</td></tr>';
            try {
                const q = query(collection(db, "absensi"), where("tanggal", ">=", start), where("tanggal", "<=", end));
                const snap = await getDocs(q);
                tbody.innerHTML = ''; let ada = false;
                snap.forEach(doc => {
                    const d = doc.data();
                    const logs = d.riwayat_kegiatan || [];
                    let jKeluar = "-", jKembali = "-", alasan = "-";
                    logs.forEach(log => {
                        if(log.includes("Izin Keluar")) { jKeluar = log.split(' - ')[0]; alasan = log.split(': ')[1] || "-"; }
                        if(log.includes("Kembali")) jKembali = log.split(' - ')[0];
                    });
                    if(jKeluar !== "-") {
                        ada = true;
                        let statusBadge = jKembali === "-" ? `<span class="bg-orange-900 text-orange-400 px-2 py-0.5 rounded-full text-[9px] font-black border border-orange-800 animate-pulse">DI LUAR</span>` : `<span class="bg-emerald-950 text-emerald-400 px-2 py-0.5 rounded-full text-[9px] font-black border border-emerald-800">KEMBALI</span>`;
                        tbody.innerHTML += `<tr class="border-b border-slate-800 hover:bg-slate-900 transition"><td class="p-4 text-xs font-mono">${d.tanggal.split('-').reverse().join('/')}</td><td class="p-4 text-white font-bold">${d.nama}</td><td class="p-4 text-[10px] text-slate-500 font-bold">${d.kelas}</td><td class="p-4 font-mono font-bold text-orange-400">${jKeluar}</td><td class="p-4 text-xs italic">${alasan}</td><td class="p-4 font-mono font-bold text-emerald-400">${jKembali}</td><td class="p-4 text-center">${statusBadge}</td></tr>`;
                    }
                });
                if(!ada) tbody.innerHTML = '<tr><td colspan="7" class="p-20 text-center italic text-slate-500 text-[10px]">Data tidak ditemukan.</td></tr>';
            } catch(e) { Swal.fire('Error', e.message, 'error'); }
        }

        // --- 2. ARSIP SAKIT & IZIN (DENGAN KOLOM KETERANGAN/ALASAN) ---
        window.cariRiwayatIzin = async () => {
            const start = document.getElementById('riwayat-start').value;
            const end = document.getElementById('riwayat-end').value;
            const tbody = document.getElementById('tbody-riwayat-izin');
            if(!start || !end) return Swal.fire('Tentukan Tanggal','','warning');
            
            tbody.innerHTML = '<tr><td colspan="6" class="p-20 text-center animate-pulse italic">Membuka Arsip...</td></tr>';
            try {
                const q = query(collection(db, "absensi"), where("tanggal", ">=", start), where("tanggal", "<=", end));
                const snap = await getDocs(q);
                tbody.innerHTML = ''; let ada = false;
                snap.forEach(doc => {
                    const d = doc.data();
                    const st = d.status_terakhir || "";
                    // Filter: Harus Izin/Sakit tapi BUKAN Izin Keluar gerbang
                    if((st.includes("Sakit") || st.includes("Izin")) && !st.includes("Keluar")) {
                        ada = true;
                        let foto = d.foto_bukti || d.foto_masuk || d.foto_pulang;
                        let btn = foto ? `<button onclick="Swal.fire({imageUrl:'${foto}',showConfirmButton:false,showCloseButton:true})" class="bg-indigo-900/50 text-indigo-400 border border-indigo-700 p-2 rounded-lg hover:bg-indigo-600 hover:text-white transition"><i data-lucide="eye" class="w-4 h-4"></i></button>` : `<span class="text-slate-800 italic text-[10px]">No File</span>`;
                        let color = st.includes("Sakit") ? "text-blue-400" : "text-yellow-400";
                        
                        // Cari Alasan dari Riwayat Kegiatan
                        let alasan = "-";
                        if(d.riwayat_kegiatan) {
                            d.riwayat_kegiatan.forEach(log => {
                                if(log.includes(st)) alasan = log.split(': ')[1] || "-";
                            });
                        }

                        tbody.innerHTML += `<tr class="border-b border-slate-800 hover:bg-slate-900 transition"><td class="p-4 text-xs font-mono font-bold">${d.tanggal.split('-').reverse().join('/')}</td><td class="p-4 text-white font-black uppercase tracking-tighter">${d.nama}</td><td class="p-4 text-slate-500 text-xs font-bold">${d.kelas}</td><td class="p-4 font-black ${color} text-[10px] tracking-widest uppercase italic">${st}</td><td class="p-4 text-[10px] italic text-slate-300 font-bold">${alasan}</td><td class="p-4 text-center">${btn}</td></tr>`;
                    }
                });
                if(!ada) tbody.innerHTML = '<tr><td colspan="6" class="p-20 text-center italic text-slate-500 uppercase text-[10px] tracking-widest">Arsip Kosong</td></tr>';
                lucide.createIcons();
            } catch(e) { console.error(e); }
        }

        // --- 3. REKAP DETAIL JAM (MODEL PEJAGOAN - ATAS BAWAH) ---
        window.loadRekap = async () => {
            const bln = parseInt(document.getElementById('pilih-bulan').value) + 1; const thn = document.getElementById('pilih-tahun').value; const kls = document.getElementById('pilih-kelas').value;
            if(!kls) return Swal.fire('Pilih Kelas!','','warning');
            const blnStr = bln < 10 ? '0' + bln : bln; const days = new Date(thn, bln, 0).getDate();
            const tbody = document.getElementById('tbody-rekap'); tbody.innerHTML = '<tr><td colspan="40" class="p-20 text-center animate-pulse uppercase tracking-widest font-black italic text-xs">Generating Report...</td></tr>';
            
            const start = `${thn}-${blnStr}-01`; const end = `${thn}-${blnStr}-${days}`;
            const qA = query(collection(db, "absensi"), where("tanggal", ">=", start), where("tanggal", "<=", end));
            const snapA = await getDocs(qA);
            let mapAbsen = {}; snapA.forEach(doc => { const d = doc.data(); if(!mapAbsen[d.nisn]) mapAbsen[d.nisn] = {}; mapAbsen[d.nisn][d.tanggal] = d; });

            const qD = query(collection(db, "dispensasi"), where("start", "<=", end)); 
            const snapD = await getDocs(qD);
            let mapDis = {}; snapD.forEach(doc => { const d = doc.data(); if(!mapDis[d.nisn]) mapDis[d.nisn] = []; mapDis[d.nisn].push(d); });

            let head = `<tr><th class="p-2 border border-slate-700">No</th><th class="p-2 border border-slate-700 text-left min-w-[150px]">Nama Siswa</th>`;
            for(let i=1; i<=days; i++) head += `<th class="p-1 border border-slate-700 w-10 text-[9px]">${i}</th>`;
            head += `<th class="p-2 border border-slate-700 bg-emerald-950">H</th><th class="p-2 border border-slate-700 bg-blue-950">S</th><th class="p-2 border border-slate-700 bg-yellow-950">I</th><th class="p-2 border border-slate-700 bg-purple-950">D</th><th class="p-2 border border-slate-700 bg-rose-950">A</th></tr>`;
            document.getElementById('thead-rekap').innerHTML = head;

            tbody.innerHTML = '';
            (mapKelas[kls] || []).sort((a,b)=>a.nama.localeCompare(b.nama)).forEach((s, idx) => {
                let h=0, si=0, iz=0, di=0, al=0;
                let row = `<tr class="hover:bg-slate-800"><td class="p-2 border border-slate-700">${idx+1}</td><td class="p-2 border border-slate-700 text-left font-black sticky left-0 bg-slate-900 text-[11px] uppercase tracking-tighter">${s.nama}</td>`;
                for(let i=1; i<=days; i++) {
                    const tgl = `${thn}-${blnStr}-${i < 10 ? '0'+i : i}`;
                    const d = mapAbsen[s.nisn]?.[tgl];
                    const isD = mapDis[s.nisn]?.some(range => tgl >= range.start && tgl <= range.end);
                    
                    let cls = "", txt = "-";
                    if(isD) { txt = "D"; cls = "bg-dispen"; di++; }
                    else if(d) {
                        const st = d.status_terakhir;
                        if(st.includes("Masuk")||st.includes("Hadir")||st.includes("Terlambat")||st.includes("Kiosk")||st.includes("Kembali")||st.includes("Pulang")) { 
                            let jamI = d.jam_masuk ? new Date(d.jam_masuk.seconds*1000).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) : "--:--";
                            let jamP = d.jam_pulang ? new Date(d.jam_pulang.seconds*1000).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) : "--:--";
                            txt = `<div class="text-[7px] leading-none font-bold">${jamI}<br>${jamP}</div>`;
                            cls = "bg-hadir"; h++; 
                        }
                        else if(st.includes("Sakit")) { txt = "S"; cls = "bg-sakit"; si++; }
                        else if(st.includes("Izin")) { txt = "I"; cls = "bg-izin"; iz++; }
                    } else {
                        const dateO = new Date(thn, bln-1, i); const day = dateO.getDay();
                        if(day === 0 || day === 6 || cacheHariLibur[tgl]) { txt = ""; cls = "bg-weekend"; }
                        else if(dateO <= new Date()) { txt = "A"; cls = "bg-alpha"; al++; }
                    }
                    row += `<td class="p-1 border border-slate-700 ${cls}">${txt}</td>`;
                }
                row += `<td class="p-2 border border-slate-700 font-black text-emerald-400">${h}</td><td class="p-2 border border-slate-700 font-black text-blue-400">${si}</td><td class="p-2 border border-slate-700 font-black text-yellow-400">${iz}</td><td class="p-2 border border-slate-700 font-black text-purple-300">${di}</td><td class="p-2 border border-slate-700 font-black text-rose-500">${al}</td></tr>`;
                tbody.innerHTML += row;
            });
        }

        // --- EXPORT EXCEL ---
        window.downloadExcel = (id, file) => {
            const table = document.getElementById(id);
            const wb = XLSX.utils.table_to_book(table, {sheet: "Data"});
            XLSX.writeFile(wb, `${file}_${new Date().getTime()}.xlsx`);
        }

        // --- DASHBOARD REALTIME ---
        function pantauRealtime() {
            const today = new Date().toLocaleDateString('fr-CA');
            const q = query(collection(db, "absensi"), where("tanggal", "==", today));
            onSnapshot(q, (snap) => {
                let hadir=0, izin=0; let logs=[]; cacheAbsenHariIni = {};
                snap.forEach(doc => {
                    const d = doc.data(); cacheAbsenHariIni[d.nisn] = d;
                    const st = d.status_terakhir || "";
                    if(st.includes("Masuk") || st.includes("Hadir") || st.includes("Terlambat") || st.includes("Kiosk") || st.includes("Kembali") || st.includes("Pulang")) hadir++;
                    else if(st.includes("Sakit") || st.includes("Izin")) izin++;
                    logs.push(d);
                });
                document.getElementById('stat-hadir').innerText = hadir;
                document.getElementById('stat-izin').innerText = izin;
                document.getElementById('stat-total').innerText = listSiswa.length;
                document.getElementById('stat-alpha').innerText = listSiswa.length - (hadir+izin);

                const tbl = document.getElementById('tabel-live'); tbl.innerHTML = '';
                logs.sort((a,b) => (b.jam_masuk?.seconds || 0) - (a.jam_masuk?.seconds || 0)).slice(0, 15).forEach(d => {
                    let jIn = d.jam_masuk ? new Date(d.jam_masuk.seconds*1000).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) : "--:--";
                    let jOut = d.jam_pulang ? new Date(d.jam_pulang.seconds*1000).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) : "--:--";
                    let f = d.foto_masuk || d.foto_pulang || d.foto_bukti;
                    let btn = f ? `<button onclick="Swal.fire({imageUrl:'${f}',showConfirmButton:false})" class="text-emerald-400 bg-emerald-900/40 p-1.5 rounded-lg border border-emerald-700 transition active:scale-90"><i data-lucide="camera" class="w-4 h-4"></i></button>` : "-";
                    tbl.innerHTML += `<tr class="border-b border-slate-800 hover:bg-slate-900 transition"><td class="p-4 font-mono text-emerald-400 font-black">${jIn}</td><td class="p-4 font-mono text-rose-400 font-black">${jOut}</td><td class="p-4 text-white font-black uppercase tracking-tighter">${d.nama}</td><td class="p-4 font-bold text-slate-500 text-[10px]">${d.kelas}</td><td class="p-4 text-[9px] font-black uppercase italic tracking-widest text-slate-300">${d.status_terakhir}</td><td class="p-4 text-center">${btn}</td></tr>`;
                });
                lucide.createIcons();
            });
        }

        // --- MASTER SISWA (CACHE) ---
        window.tarikDataSiswa = async (f = false) => {
            const ck = 'master_siswa_bk_v6'; if(f) localStorage.removeItem(ck);
            const c = localStorage.getItem(ck);
            if(c) { const p = JSON.parse(c); listSiswa = p.listSiswa; mapKelas = p.mapKelas; updateUIKelas(); return; }
            const q = query(collection(db, "users"), where("role", "==", "siswa"));
            const s = await getDocs(q);
            listSiswa = []; mapKelas = {};
            s.forEach(doc => {
                const u = doc.data(); if(u.kelas && u.kelas !== '-') {
                const nisn = String(u.username).trim(); const kls = String(u.kelas).trim().toUpperCase();
                const obj = { nama: u.nama, nisn, kelas: kls }; listSiswa.push(obj);
                if(!mapKelas[kls]) mapKelas[kls] = []; mapKelas[kls].push(obj);
            }});
            localStorage.setItem(ck, JSON.stringify({listSiswa, mapKelas})); updateUIKelas();
        }

        function updateUIKelas() { const s = document.getElementById('pilih-kelas'); if(!s) return; s.innerHTML = '<option value="">- KELAS -</option>'; Object.keys(mapKelas).sort().forEach(k => s.add(new Option(k, k))); }
        function setupDropdown() { const d=new Date(); const m=["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agt","Sep","Okt","Nov","Des"]; m.forEach((b,i)=>{let o=new Option(b,i); if(i===d.getMonth())o.selected=true; document.getElementById('pilih-bulan').add(o);}); for(let y=d.getFullYear()-1;y<=d.getFullYear()+1;y++) document.getElementById('pilih-tahun').add(new Option(y,y)); }
        function loadHariLibur() { const q = query(collection(db, "hari_libur"), orderBy("tanggal", "asc")); onSnapshot(q, (snap) => { const tb = document.getElementById('tbody-libur'); if(tb) tb.innerHTML = ''; cacheHariLibur = {}; snap.forEach(doc => { const d=doc.data(); cacheHariLibur[d.tanggal] = d.keterangan; if(tb) tb.innerHTML += `<tr class="border-b border-slate-800 hover:bg-slate-900 transition"><td class="p-3 font-mono text-white">${d.tanggal.split('-').reverse().join('/')}</td><td class="p-3 text-slate-300 font-black italic uppercase text-[10px]">${d.keterangan}</td><td class="p-3 text-right"><button onclick="hapusLibur('${doc.id}')" class="text-rose-500 hover:scale-125 transition"><i data-lucide="trash-2" class="w-4 h-4"></i></button></td></tr>`; }); lucide.createIcons(); }); }
        
        // --- DISPEN & LAINNYA ---
        window.cariSiswaLokal = () => {
            const v = document.getElementById('inputCariSiswa').value.toLowerCase();
            const d = document.getElementById('hasilCariSiswa'); d.innerHTML = '';
            if(v.length < 3) return d.classList.add('hidden');
            const res = listSiswa.filter(s => s.nama.toLowerCase().includes(v) || s.nisn.includes(v)).slice(0, 5);
            if(res.length > 0) { d.classList.remove('hidden'); res.forEach(s => { d.innerHTML += `<div onclick="pilihSiswaDispen('${s.nisn}','${s.nama}','${s.kelas}')" class="p-2 hover:bg-slate-700 cursor-pointer text-[10px] text-white border-b border-slate-700">${s.nama} (${s.kelas})</div>`; }); }
        }
        window.pilihSiswaDispen = (n, m, k) => { document.getElementById('inputNisnSiswa').value = n; document.getElementById('displayNamaSiswa').value = m; document.getElementById('inputKelasSiswa').value = k; document.getElementById('hasilCariSiswa').classList.add('hidden'); }
        window.simpanDispen = async () => {
            const nisn = document.getElementById('inputNisnSiswa').value; const nama = document.getElementById('displayNamaSiswa').value; const start = document.getElementById('inputStart').value; const end = document.getElementById('inputEnd').value; const alasan = document.getElementById('inputAlasan').value;
            if(!nisn || !start || !end) return Swal.fire('Error','Lengkapi data!','error');
            try { await addDoc(collection(db, "dispensasi"), { nisn, nama, start, end, alasan, created_at: serverTimestamp() }); Swal.fire('Berhasil','Dispensasi dicatat','success'); } catch(e) { Swal.fire('Gagal', e.message, 'error'); }
        }
        window.loadDispen = () => { onSnapshot(query(collection(db, "dispensasi"), orderBy("created_at","desc"), limit(20)), (snap) => { const tb = document.getElementById('tbody-dispen'); tb.innerHTML = ''; snap.forEach(doc => { const d = doc.data(); tb.innerHTML += `<tr class="border-b border-slate-800"><td class="p-3 text-white font-bold">${d.nama}</td><td class="p-3 font-mono text-blue-400 text-[10px]">${d.start} s.d ${d.end}</td><td class="p-3 italic text-[10px]">${d.alasan}</td><td class="p-3 text-right"><button onclick="hapusDispen('${doc.id}')" class="text-rose-500"><i data-lucide="trash-2" class="w-4 h-4"></i></button></td></tr>`; }); lucide.createIcons(); }); }
        window.hapusDispen = async (id) => { if(confirm("Hapus?")) await deleteDoc(doc(db, "dispensasi", id)); }
        window.simpanLibur = async () => { const t = document.getElementById('inputTglLibur').value; const k = document.getElementById('inputKetLibur').value; if(!t||!k) return; await addDoc(collection(db, "hari_libur"), { tanggal: t, keterangan: k }); Swal.fire('Sukses','','success'); }
        window.hapusLibur = async (id) => { if(confirm("Hapus?")) await deleteDoc(doc(db, "hari_libur", id)); }
        window.logout = () => { sessionStorage.clear(); window.location.href='/logout.php'; }
        
        setInterval(() => { const el = document.getElementById('jam-real'); if(el) el.innerText = new Date().toLocaleTimeString('id-ID'); }, 1000);
    </script>
</body>
</html>
