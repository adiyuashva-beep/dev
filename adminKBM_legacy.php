<?php
require __DIR__ . '/auth/guard.php';
require_login(['super', 'admin', 'kurikulum', 'bk', 'kesiswaan']);
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
        .table-sticky th { position: sticky; top: 0; z-index: 30; background: #1e293b; border-bottom: 2px solid #334155; }
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
            
            <!-- MENU BARU: KSN & EKSTRA -->
            <button onclick="nav('ksn')" id="nav-ksn" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-lg transition font-bold">
                <i data-lucide="trophy" class="w-5 h-5 text-yellow-500"></i> Manajemen KSN/Ekstra
            </button>

            <button onclick="nav('alpha')" id="nav-alpha" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white rounded-lg transition font-bold">
                <i data-lucide="user-x" class="w-5 h-5 text-rose-500"></i> Monitoring Alpha
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
        </nav>

        <div class="p-4 border-t border-slate-800">
            <button onclick="logout()" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-red-900/20 text-red-500 rounded-lg font-bold uppercase text-[10px] tracking-widest hover:bg-red-600 hover:text-white transition">
                <i data-lucide="power" class="w-4 h-4"></i> Keluar Panel
            </button>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-slate-950 p-4 md:p-8 relative">
        
        <!-- DASHBOARD VIEW -->
        <div id="view-dashboard" class="space-y-6 fade-in">
            <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter">Live Monitoring</h2>
                    <p class="text-slate-400 text-sm">Waktu Sistem: <span id="jam-real" class="font-mono text-emerald-400 font-bold">00:00:00</span></p>
                </div>
                <div class="flex gap-2">
                    <button onclick="tarikDataSiswa(true)" class="bg-slate-800 text-white px-4 py-2 rounded-lg font-bold text-[10px] uppercase border border-slate-700">Refresh Master</button>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl cursor-pointer hover:border-emerald-500 transition" onclick="nav('dashboard')"><p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Hadir</p><h3 class="text-3xl font-black text-emerald-400 mt-1" id="stat-hadir">0</h3></div>
                <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl cursor-pointer hover:border-blue-500 transition" onclick="nav('riwayat')"><p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Sakit/Izin</p><h3 class="text-3xl font-black text-blue-400 mt-1" id="stat-izin">0</h3></div>
                <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl cursor-pointer hover:border-rose-500 transition" onclick="nav('alpha')"><p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Belum Hadir</p><h3 class="text-3xl font-black text-rose-500 mt-1" id="stat-alpha">0</h3></div>
                <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl"><p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Total Siswa</p><h3 class="text-3xl font-black text-white mt-1" id="stat-total">0</h3></div>
            </div>

            <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto max-h-[450px]">
                    <table class="w-full text-left text-slate-400">
                        <thead class="bg-slate-950 text-[10px] uppercase font-bold text-slate-500 sticky top-0">
                            <tr><th class="p-4">Jam In</th><th class="p-4">Siswa</th><th class="p-4">Kelas</th><th class="p-4">Status</th><th class="p-4 text-center">Foto</th></tr>
                        </thead>
                        <tbody id="tabel-live" class="divide-y divide-slate-800"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- VIEW KSN & EKSTRA (BARU) -->
        <div id="view-ksn" class="hidden space-y-6 fade-in">
             <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter text-yellow-500">Plotting KSN & Ekstra</h2>
                    <p class="text-slate-400 text-sm">Tetapkan Guru Pembimbing untuk membuka akses "Kelas Bimbingan" di akun guru mereka.</p>
                </div>
                <button onclick="bukaModalKSN()" class="bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg flex items-center gap-2 transition active:scale-95">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i> Tambah Tim
                </button>
            </div>

            <!-- List KSN/Ekstra -->
            <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-slate-400">
                        <thead class="bg-slate-950 text-[10px] uppercase font-bold text-slate-500">
                            <tr>
                                <th class="p-4">Nama Tim / Mapel</th>
                                <th class="p-4">Kategori</th>
                                <th class="p-4">Guru Pembimbing</th>
                                <th class="p-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-ksn" class="divide-y divide-slate-800 text-sm">
                            <tr><td colspan="4" class="p-8 text-center animate-pulse italic">Memuat data tim...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Tambah KSN -->
        <div id="modal-ksn" class="fixed inset-0 bg-black/80 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
            <div class="bg-slate-900 w-full max-w-md rounded-2xl border border-slate-700 shadow-2xl">
                <div class="p-5 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="font-bold text-white">Buat Tim Baru</h3>
                    <button onclick="tutupModalKSN()" class="text-slate-500 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Tim / Mapel</label>
                        <input type="text" id="inputNamaKSN" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-blue-500 outline-none" placeholder="Contoh: KSN Matematika">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Kategori</label>
                        <select id="inputKategoriKSN" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-blue-500 outline-none">
                            <option value="KSN">KSN (Olimpiade)</option>
                            <option value="O2SN">O2SN (Olahraga)</option>
                            <option value="FLS2N">FLS2N (Seni)</option>
                            <option value="EKSTRA">Ekstrakurikuler</option>
                            <option value="LAINNYA">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Guru Pembimbing</label>
                        <select id="pilihGuruKSN" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-blue-500 outline-none">
                            <option value="">-- Memuat Guru... --</option>
                        </select>
                        <p class="text-[10px] text-slate-500 mt-1 italic">*Mengambil data dari akun Guru (Firebase Users)</p>
                    </div>
                    <div class="pt-2">
                        <button onclick="simpanKSN()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-blue-600/20">SIMPAN & PLOTTING</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIEW LAINNYA (ALPHA, REKAP, DLL) - BIARKAN SEPERTI ASLINYA -->
        <div id="view-alpha" class="hidden space-y-6 fade-in">
             <!-- Konten view alpha sama seperti sebelumnya -->
             <div class="flex flex-col md:flex-row justify-between items-end gap-4">
                <div><h2 class="text-3xl font-black text-white uppercase italic tracking-tighter text-rose-500">Laporan Alpha</h2></div>
                <div class="flex gap-2 bg-slate-900 p-3 rounded-2xl border border-slate-800 shadow-2xl">
                    <select id="filter-kelas-alpha" onchange="loadSiswaAlpha()" class="bg-slate-800 text-white text-[10px] rounded-lg px-3 py-2 outline-none border border-slate-700 font-bold"><option value="">SEMUA KELAS</option></select>
                </div>
            </div>
            <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto h-[65vh]">
                    <table class="w-full text-left">
                        <thead class="bg-slate-950 text-[10px] uppercase font-bold text-slate-500 sticky top-0"><tr><th class="p-4">No</th><th class="p-4">NISN</th><th class="p-4">Nama Siswa</th><th class="p-4">Kelas</th><th class="p-4 text-center">Tindakan</th></tr></thead>
                        <tbody id="tbody-alpha" class="divide-y divide-slate-800 text-slate-400"><tr><td colspan="5" class="p-20 text-center animate-pulse italic">Memproses Data Alpha...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="view-rekap" class="hidden space-y-6 fade-in">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-4">
                <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter">Rekap Detail Jam</h2>
                <div class="flex flex-wrap gap-2 bg-slate-900 p-3 rounded-2xl border border-slate-800 shadow-xl">
                    <select id="pilih-bulan" class="bg-slate-800 text-white text-[10px] rounded-lg px-3 py-2 outline-none border border-slate-700 font-bold"></select>
                    <select id="pilih-tahun" class="bg-slate-800 text-white text-[10px] rounded-lg px-3 py-2 outline-none border border-slate-700 font-bold"></select>
                    <select id="pilih-kelas" class="bg-slate-800 text-white text-[10px] rounded-lg px-3 py-2 outline-none border border-slate-700 font-bold"><option value="">- KELAS -</option></select>
                    <button onclick="loadRekap()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-widest transition active:scale-95">Tampilkan</button>
                    <button onclick="downloadExcel('tabel-rekap', 'REKAP_DETAIL_JAM_PEJAGOAN')" class="bg-emerald-800 text-white px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-widest flex items-center gap-2"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Excel</button>
                </div>
            </div>
            <div class="flex-1 bg-slate-900 rounded-2xl border border-slate-800 overflow-auto relative h-[70vh] shadow-2xl">
                <table id="tabel-rekap" class="w-full text-center text-[10px] border-collapse table-sticky whitespace-nowrap">
                    <thead class="text-slate-300 font-bold uppercase tracking-tighter" id="thead-rekap"></thead>
                    <tbody id="tbody-rekap" class="text-slate-400 divide-y divide-slate-800"></tbody>
                </table>
            </div>
        </div>

        <div id="view-keluar" class="hidden space-y-6 fade-in"><div class="p-10 text-center text-slate-500">Panel Keluar Gerbang (Gunakan Menu Izin Keluar-Masuk)</div></div>
        <div id="view-riwayat" class="hidden space-y-6 fade-in"><div class="p-10 text-center text-slate-500">Panel Arsip Sakit/Izin</div></div>
        <div id="view-dispen" class="hidden space-y-6 fade-in"><div class="p-10 text-center text-slate-500">Panel Dispensasi</div></div>
        <div id="view-mbg" class="hidden space-y-6 fade-in"><div class="p-10 text-center text-slate-500">Panel MBG</div></div>
        <div id="view-libur" class="hidden space-y-6 fade-in"><div class="p-10 text-center text-slate-500">Panel Hari Libur</div></div>

    </main>

    <div id="loading" class="fixed inset-0 bg-slate-950 flex flex-col items-center justify-center z-[100] text-white">
        <div class="w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="font-black animate-pulse uppercase tracking-[0.3em] text-[10px]">EduGate SMANSAKRA</p>
    </div>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js";
        import { getFirestore, collection, query, where, getDocs, onSnapshot, doc, setDoc, addDoc, deleteDoc, orderBy, limit, serverTimestamp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-firestore.js";

        // CONFIG FIREBASE (EDUGATE SMANSAKRA)
        const firebaseConfig = { apiKey: "AIzaSyBTanYQQ-oPkO_Zw9cg_H5V0jIJvt_x72c", authDomain: "edugate-smansakra.firebaseapp.com", projectId: "edugate-smansakra", storageBucket: "edugate-smansakra.firebasestorage.app", messagingSenderId: "181623194937", appId: "1:181623194937:web:d494810276000dceda14e8" };
        const app = initializeApp(firebaseConfig); const db = getFirestore(app);

        let listSiswa = []; let mapKelas = {}; let cacheAbsenHariIni = {}; let cacheHariLibur = {}; 

        document.addEventListener('DOMContentLoaded', async () => {
            lucide.createIcons();
            setupDropdown();
            try {
                await tarikDataSiswa(); 
                pantauRealtime();
                loadHariLibur(); 
                // Load data khusus KSN
                loadGuru();
                loadKSN();
            } catch (e) { console.error("Error Initializing:", e); } 
            finally { if(document.getElementById('loading')) document.getElementById('loading').style.display = 'none'; }
        });

        // FUNGSI NAVIGASI
        window.nav = (v) => { 
            document.querySelectorAll('[id^="view-"]').forEach(e => e.classList.add('hidden')); 
            document.querySelectorAll('.nav-item').forEach(e => e.classList.remove('active')); 
            
            const view = document.getElementById('view-'+v);
            if(view) view.classList.remove('hidden');
            
            const menu = document.getElementById('nav-'+v);
            if(menu) menu.classList.add('active'); 

            if(v === 'alpha') setTimeout(() => loadSiswaAlpha(), 50);
        }

        // --- FITUR BARU: MANAJEMEN KSN/EKSTRA ---
        
        // 1. Ambil Data Guru untuk Dropdown (Biar gak input manual)
        window.loadGuru = async () => {
            const select = document.getElementById('pilihGuruKSN');
            select.innerHTML = '<option value="">-- Memuat Data... --</option>';
            
            // Ambil user dengan role 'guru'
            const q = query(collection(db, "users"), where("role", "==", "guru"));
            const snap = await getDocs(q);
            
            select.innerHTML = '<option value="">-- Pilih Guru Pembimbing --</option>';
            snap.forEach(doc => {
                const g = doc.data();
                const nama = g.nama_lengkap || g.nama || "Guru Tanpa Nama";
                // Value simpan ID Guru, Teks simpan Nama
                const option = new Option(nama, doc.id); 
                // Simpan nama guru di dataset biar gampang diambil nanti
                option.dataset.nama = nama;
                select.add(option);
            });
        }

        // 2. Load Data KSN yang sudah ada
        window.loadKSN = () => {
            const tbody = document.getElementById('tbody-ksn');
            const q = query(collection(db, "kelas_bimbingan"), orderBy("nama_ekstra", "asc"));
            
            onSnapshot(q, (snap) => {
                tbody.innerHTML = '';
                if(snap.empty) {
                    tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-slate-500 italic">Belum ada tim yang dibentuk.</td></tr>';
                    return;
                }

                snap.forEach(doc => {
                    const d = doc.data();
                    let badgeColor = "text-slate-400 border-slate-600";
                    if(d.kategori.includes("KSN")) badgeColor = "text-blue-400 border-blue-500/30 bg-blue-500/10";
                    if(d.kategori.includes("O2SN")) badgeColor = "text-emerald-400 border-emerald-500/30 bg-emerald-500/10";
                    
                    tbody.innerHTML += `
                        <tr class="border-b border-slate-800 hover:bg-slate-800/50 transition">
                            <td class="p-4 font-bold text-white">${d.nama_ekstra}</td>
                            <td class="p-4"><span class="px-2 py-1 rounded text-[10px] font-black uppercase border ${badgeColor}">${d.kategori}</span></td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-900 text-indigo-300 flex items-center justify-center text-[10px] font-bold">
                                        ${d.nama_guru ? d.nama_guru.charAt(0) : '?'}
                                    </div>
                                    <span class="text-xs text-slate-300">${d.nama_guru || 'Belum dipilih'}</span>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <button onclick="hapusKSN('${doc.id}', '${d.nama_ekstra}')" class="text-rose-500 hover:text-white hover:bg-rose-600 p-2 rounded-lg transition"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    `;
                });
                lucide.createIcons();
            });
        }

        // 3. Simpan Plotting Baru
        window.simpanKSN = async () => {
            const nama = document.getElementById('inputNamaKSN').value;
            const kategori = document.getElementById('inputKategoriKSN').value;
            const selectGuru = document.getElementById('pilihGuruKSN');
            const idGuru = selectGuru.value;
            
            if(!nama || !idGuru) {
                Swal.fire("Gagal", "Nama Tim dan Guru Pembimbing wajib diisi!", "warning");
                return;
            }

            const namaGuru = selectGuru.options[selectGuru.selectedIndex].dataset.nama;

            try {
                await addDoc(collection(db, "kelas_bimbingan"), {
                    nama_ekstra: nama,
                    kategori: kategori,
                    id_guru: idGuru,     // Ini kuncinya: Biar nanti di guru.html bisa difilter
                    nama_guru: namaGuru, // Buat display aja
                    created_at: serverTimestamp()
                });
                tutupModalKSN();
                Swal.fire("Berhasil", "Tim berhasil dibentuk & Guru ditugaskan.", "success");
            } catch (e) {
                Swal.fire("Error", e.message, "error");
            }
        }

        // 4. Hapus Plotting
        window.hapusKSN = async (id, nama) => {
            const r = await Swal.fire({
                title: 'Hapus Tim?',
                text: `Tim "${nama}" akan dihapus. Guru ybs tidak akan bisa mengakses kelas ini lagi.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus'
            });

            if(r.isConfirmed) {
                await deleteDoc(doc(db, "kelas_bimbingan", id));
                Swal.fire("Terhapus", "Data berhasil dihapus.", "success");
            }
        }

        // Modal Helpers
        window.bukaModalKSN = () => { document.getElementById('modal-ksn').classList.remove('hidden'); }
        window.tutupModalKSN = () => { document.getElementById('modal-ksn').classList.add('hidden'); }


        // --- LOGIC LAMA (ALPHA, REKAP, DLL) TETAP AMAN DI BAWAH ---
        
        window.loadSiswaAlpha = () => {
            const filterKelas = document.getElementById('filter-kelas-alpha').value;
            const tbody = document.getElementById('tbody-alpha');
            
            if (!listSiswa.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="p-20 text-center">Data Master Siswa Belum Siap. Silakan Refresh Master.</td></tr>';
                return;
            }

            const daftarAlpha = listSiswa.filter(s => {
                const sudahAbsen = cacheAbsenHariIni[s.nisn];
                const matchKelas = filterKelas === "" || s.kelas === filterKelas;
                return !sudahAbsen && matchKelas;
            });

            if(daftarAlpha.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="p-20 text-center italic text-emerald-500 font-bold uppercase tracking-widest">Alhamdulillah, Semua Sudah Presensi!</td></tr>';
                return;
            }

            let htmlRows = [];
            daftarAlpha.sort((a,b) => a.nama.localeCompare(b.nama)).forEach((s, idx) => {
                const pesan = `Assalamu'alaikum Bapak/Ibu, menginformasikan bahwa ananda *${s.nama}* (${s.kelas}) hingga pukul ${new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})} WIB belum melakukan presensi di SiGanteng. Mohon konfirmasinya. Terima kasih.`;
                const linkWA = `https://wa.me/${s.hp || ''}?text=${encodeURIComponent(pesan)}`;
                
                htmlRows.push(`
                    <tr class="border-b border-slate-800 hover:bg-rose-900/10 transition">
                        <td class="p-4 text-slate-500 font-mono">${idx+1}</td>
                        <td class="p-4 text-slate-500 font-mono">${s.nisn}</td>
                        <td class="p-4 text-white font-black uppercase tracking-tighter">${s.nama}</td>
                        <td class="p-4 font-bold text-slate-500 text-[10px]">${s.kelas}</td>
                        <td class="p-4 text-center">
                            <a href="${linkWA}" target="_blank" class="inline-flex bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-[9px] font-black uppercase items-center gap-2 transition active:scale-95">
                                <i data-lucide="message-circle" class="w-3 h-3"></i> WA Ortu
                            </a>
                        </td>
                    </tr>
                `);
            });
            tbody.innerHTML = htmlRows.join('');
            lucide.createIcons();
        }

        window.tarikDataSiswa = async (f = false) => {
            const ck = 'master_siswa_bk_v6'; if(f) localStorage.removeItem(ck);
            const c = localStorage.getItem(ck);
            if(c) { 
                const p = JSON.parse(c); listSiswa = p.listSiswa; mapKelas = p.mapKelas; 
                updateUIKelas(); return; 
            }
            const q = query(collection(db, "users"), where("role", "==", "siswa"));
            const s = await getDocs(q);
            listSiswa = []; mapKelas = {};
            s.forEach(doc => {
                const u = doc.data(); 
                if(u.kelas && u.kelas !== '-') {
                    const nisn = String(u.username).trim(); 
                    const kls = String(u.kelas).trim().toUpperCase();
                    const obj = { nama: u.nama, nisn, kelas: kls, hp: u.nomor_hp || u.whatsapp || "" }; 
                    listSiswa.push(obj);
                    if(!mapKelas[kls]) mapKelas[kls] = []; mapKelas[kls].push(obj);
                }
            });
            localStorage.setItem(ck, JSON.stringify({listSiswa, mapKelas})); 
            updateUIKelas();
        }

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
                const hEl = document.getElementById('stat-hadir'); if(hEl) hEl.innerText = hadir;
                const iEl = document.getElementById('stat-izin'); if(iEl) iEl.innerText = izin;
                const tEl = document.getElementById('stat-total'); if(tEl) tEl.innerText = listSiswa.length;
                const aEl = document.getElementById('stat-alpha'); if(aEl) aEl.innerText = listSiswa.length - (hadir+izin);
                const tbl = document.getElementById('tabel-live'); 
                if(tbl) {
                    tbl.innerHTML = logs.sort((a,b) => (b.jam_masuk?.seconds || 0) - (a.jam_masuk?.seconds || 0)).slice(0, 10).map(d => {
                        let jIn = d.jam_masuk ? new Date(d.jam_masuk.seconds*1000).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) : "--:--";
                        return `<tr class="border-b border-slate-800 hover:bg-slate-900 transition"><td class="p-4 font-mono text-emerald-400 font-black">${jIn}</td><td class="p-4 text-white font-black uppercase tracking-tighter">${d.nama}</td><td class="p-4 font-bold text-slate-500 text-[10px]">${d.kelas}</td><td class="p-4 text-[9px] font-black uppercase italic tracking-widest text-slate-300">${d.status_terakhir}</td><td class="p-4 text-center">-</td></tr>`;
                    }).join('');
                }
                const alphaView = document.getElementById('view-alpha');
                if (alphaView && !alphaView.classList.contains('hidden')) loadSiswaAlpha();
            });
        }

        function updateUIKelas() { 
            const sAlpha = document.getElementById('filter-kelas-alpha');
            const sRekap = document.getElementById('pilih-kelas'); 
            if(!sAlpha || !sRekap) return; 
            let options = '<option value="">SEMUA KELAS</option>';
            Object.keys(mapKelas).sort().forEach(k => { options += `<option value="${k}">${k}</option>`; });
            sAlpha.innerHTML = options;
            sRekap.innerHTML = '<option value="">- KELAS -</option>' + options.replace('SEMUA KELAS', '- KELAS -');
        }

        function setupDropdown() { 
            const d=new Date(); const m=["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agt","Sep","Okt","Nov","Des"]; 
            const blnSelect = document.getElementById('pilih-bulan');
            if(blnSelect) m.forEach((b,i)=>{let o=new Option(b,i); if(i===d.getMonth())o.selected=true; blnSelect.add(o);}); 
            const thnSelect = document.getElementById('pilih-tahun');
            if(thnSelect) for(let y=d.getFullYear()-1;y<=d.getFullYear()+1;y++) thnSelect.add(new Option(y,y)); 
        }

        function loadHariLibur() { 
            const q = query(collection(db, "hari_libur"), orderBy("tanggal", "asc")); 
            onSnapshot(q, (snap) => { cacheHariLibur = {}; snap.forEach(doc => { cacheHariLibur[doc.data().tanggal] = true; }); }); 
        }

        window.logout = () => { sessionStorage.clear(); window.location.href='/logout.php'; }
        setInterval(() => { const el = document.getElementById('jam-real'); if(el) el.innerText = new Date().toLocaleTimeString('id-ID'); }, 1000);
    </script>
</body>
</html>
