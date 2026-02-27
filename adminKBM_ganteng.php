<?php
require __DIR__ . '/auth/guard.php';
require_login(['super', 'admin', 'kurikulum']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Kurikulum (KBM) - SiGanteng</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #0f172a; color: #e2e8f0; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .nav-item.active { background: rgba(234, 179, 8, 0.1); border-right: 3px solid #eab308; color: #facc15; }
        
        /* Table Sticky Header */
        .table-sticky th { position: sticky; top: 0; z-index: 30; background: #1e293b; }
        
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Bar Refleksi 4 Warna (Versi Dark Mode) */
        .progress-stack { display: flex; height: 8px; border-radius: 4px; overflow: hidden; background: #334155; width: 100%; margin-top: 6px; }
        .bar-4 { background-color: #10b981; } /* Senang (Hijau) */
        .bar-3 { background-color: #facc15; } /* Inspirasi (Kuning) */
        .bar-2 { background-color: #f97316; } /* Bingung (Oranye) */
        .bar-1 { background-color: #64748b; } /* Ngantuk (Abu) */
    </style>
</head>
<body class="flex h-screen overflow-hidden text-sm">

    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col hidden md:flex">
        <div class="p-6">
            <h1 class="font-black text-2xl tracking-tighter text-yellow-500">SiGanteng</h1>
            <p class="text-xs text-slate-500 tracking-widest uppercase mt-1">Panel Kurikulum (KBM)</p>
        </div>

        <nav class="flex-1 space-y-1 px-3 mt-4 overflow-y-auto hide-scroll">
            <button onclick="location.reload()" class="nav-item active w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Monitoring Harian
            </button>
            <div class="px-4 py-3 text-xs text-slate-500 italic">
                *Input Jurnal dilakukan via Login Guru masing-masing.
            </div>
        </nav>
        </nav>
        <nav class="flex-1 space-y-1 px-3 mt-4 overflow-y-auto hide-scroll">
    <div class="my-4 border-t border-slate-700"></div>
    
    <button onclick="window.location.href='guru.html'" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-emerald-400 hover:text-white hover:bg-emerald-900/30 rounded-lg transition font-bold">
        <i data-lucide="pen-tool" class="w-5 h-5"></i> Input Jurnal Saya
    </button>
</nav>

        <div class="p-4 border-t border-slate-800">
            <button onclick="logout()" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-red-500/10 text-red-500 rounded-lg hover:bg-red-500 hover:text-white transition font-bold">
                <i data-lucide="log-out" class="w-4 h-4"></i> Keluar
            </button>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-slate-950 relative p-4 md:p-8">
        
        <div class="md:hidden flex justify-between items-center mb-6">
            <h1 class="font-bold text-lg text-yellow-500">Kurikulum KBM</h1>
            <button onclick="logout()" class="text-red-500"><i data-lucide="log-out"></i></button>
        </div>

        <div class="space-y-6 fade-in">
            <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-lg">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-auto">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tanggal Pantau</label>
                        <input type="date" id="filter-tanggal" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-white outline-none focus:border-yellow-500" onchange="loadDataUtama()">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 flex-1 w-full">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Filter Guru</label>
                            <select id="filter-guru" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-white outline-none focus:border-yellow-500" onchange="terapkanFilterLokal()">
                                <option value="">- Semua Guru -</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Filter Kelas</label>
                            <select id="filter-kelas" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-white outline-none focus:border-yellow-500" onchange="terapkanFilterLokal()">
                                <option value="">- Semua Kelas -</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Filter Mapel</label>
                            <select id="filter-mapel" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-white outline-none focus:border-yellow-500" onchange="terapkanFilterLokal()">
                                <option value="">- Semua Mapel -</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2 w-full md:w-auto">
                        <button onclick="downloadLaporanExcel()" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg font-bold flex items-center justify-center gap-2 transition">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Excel
                        </button>
                        <button onclick="loadDataUtama()" class="bg-slate-800 hover:bg-slate-700 text-white px-3 py-2 rounded-lg transition" title="Refresh Data">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800"><p class="text-slate-500 text-xs font-bold uppercase">Jurnal Masuk</p><h3 class="text-3xl font-black text-white mt-1" id="stat-total">0</h3></div>
                <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800"><p class="text-slate-500 text-xs font-bold uppercase">Guru Mengajar</p><h3 class="text-3xl font-black text-yellow-400 mt-1" id="stat-guru">0</h3></div>
                <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800"><p class="text-slate-500 text-xs font-bold uppercase">Respon Positif</p><h3 class="text-3xl font-black text-emerald-400 mt-1" id="stat-kepuasan">0%</h3></div>
                <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800"><p class="text-slate-500 text-xs font-bold uppercase">Siswa Tdk Hadir</p><h3 class="text-3xl font-black text-red-400 mt-1" id="stat-absen">0</h3></div>
            </div>

            <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-lg">
                <div class="p-4 border-b border-slate-800 flex justify-between items-center">
                    <h3 class="font-bold text-white">Detail Monitoring KBM</h3>
                    <span class="text-xs text-slate-500" id="clock-display">...</span>
                </div>
                <div class="overflow-x-auto max-h-[60vh]">
                    <table class="w-full text-left text-slate-400">
                        <thead class="bg-slate-950 text-xs uppercase font-bold text-slate-500 sticky top-0 z-20 shadow-md">
                            <tr>
                                <th class="p-4 w-24">Jam</th>
                                <th class="p-4 w-32">Kelas</th>
                                <th class="p-4">Aktivitas Guru</th>
                                <th class="p-4 w-64">Respon Siswa</th>
                                <th class="p-4 w-48">Ketidakhadiran</th>
                                <th class="p-4 text-center w-20">Bukti</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-monitor" class="divide-y divide-slate-800">
                            <tr><td colspan="6" class="p-8 text-center italic text-slate-500">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <div id="modal-komen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden">
        <div class="bg-slate-900 w-full max-w-lg rounded-2xl shadow-2xl border border-slate-700 overflow-hidden m-4 flex flex-col max-h-[80vh]">
            <div class="bg-slate-950 p-4 border-b border-slate-800 flex justify-between items-center shrink-0">
                <h3 class="font-bold text-white">Apa Kata Siswa?</h3>
                <button onclick="document.getElementById('modal-komen').classList.add('hidden')" class="text-slate-400 hover:text-red-500"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div id="list-komen" class="p-4 overflow-y-auto space-y-3 flex-1 text-sm"></div>
        </div>
    </div>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js";
        import { getFirestore, collection, query, where, getDocs, orderBy } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-firestore.js";

        const firebaseConfig = {
            apiKey: "AIzaSyBXWR-_aJyoMrUjTeNQYlcPD8p3eu58yOo",
            authDomain: "siganteng-absensi.firebaseapp.com",
            databaseURL: "https://siganteng-absensi-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "siganteng-absensi",
            storageBucket: "siganteng-absensi.firebasestorage.app",
            messagingSenderId: "917873420012",
            appId: "1:917873420012:web:0fe1a9eddc5f94959ba7c9"
        };
        const app = initializeApp(firebaseConfig);
        const db = getFirestore(app);

        // DATA STORE
        let rawDataJurnal = [];
        let rawDataRefleksi = {};

        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            document.getElementById('filter-tanggal').valueAsDate = new Date();
            updateJam(); setInterval(updateJam, 60000);
            loadDataUtama(); 
        });

        function updateJam() {
            const now = new Date();
            document.getElementById('clock-display').innerText = now.toLocaleDateString('id-ID', {weekday:'long', day:'numeric', month:'short'}) + " " + now.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
        }

        // 1. ENGINE UTAMA
        window.loadDataUtama = async () => {
            const tgl = document.getElementById('filter-tanggal').value;
            const tbody = document.getElementById('tabel-monitor');
            tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-slate-500"><i class="animate-spin" data-lucide="loader"></i> Mengambil data KBM...</td></tr>';
            lucide.createIcons();

            try {
                // A. Ambil Jurnal
                const qJurnal = query(collection(db, "jurnal_guru"), where("tanggal", "==", tgl));
                const snapJurnal = await getDocs(qJurnal);

                // B. Ambil Refleksi
                const qRef = query(collection(db, "refleksi_siswa"), where("tanggal", "==", tgl));
                const snapRef = await getDocs(qRef);

                // C. Proses Data Refleksi
                rawDataRefleksi = {};
                snapRef.forEach(doc => {
                    const r = doc.data();
                    if(!rawDataRefleksi[r.id_jurnal]) rawDataRefleksi[r.id_jurnal] = { r4:0, r3:0, r2:0, r1:0, total:0, komen:[] };
                    
                    if(r.rating == 4) rawDataRefleksi[r.id_jurnal].r4++; 
                    else if(r.rating == 3) rawDataRefleksi[r.id_jurnal].r3++; 
                    else if(r.rating == 2) rawDataRefleksi[r.id_jurnal].r2++; 
                    else rawDataRefleksi[r.id_jurnal].r1++; 
                    
                    rawDataRefleksi[r.id_jurnal].total++;
                    if(r.pesan && r.pesan.length > 2 && r.pesan !== '-') {
                        rawDataRefleksi[r.id_jurnal].komen.push({ nama: r.nama, text: r.pesan, rating: r.rating });
                    }
                });

                // D. Simpan Jurnal
                rawDataJurnal = [];
                snapJurnal.forEach(doc => {
                    let d = doc.data();
                    d.id = doc.id;
                    rawDataJurnal.push(d);
                });

                rawDataJurnal.sort((a,b) => (b.waktu?.toMillis() || 0) - (a.waktu?.toMillis() || 0));

                populateDropdowns(); 
                terapkanFilterLokal(); 

            } catch (e) {
                console.error(e);
                tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-red-500">Gagal memuat: ${e.message}</td></tr>`;
            }
        }

        // 2. FILTER LOGIC
        function populateDropdowns() {
            const setGuru = new Set();
            const setKelas = new Set();
            const setMapel = new Set();

            rawDataJurnal.forEach(d => {
                if(d.nama_guru) setGuru.add(d.nama_guru);
                if(d.kelas) setKelas.add(d.kelas);
                if(d.mapel) setMapel.add(d.mapel);
            });

            isiSelect('filter-guru', Array.from(setGuru).sort());
            isiSelect('filter-kelas', Array.from(setKelas).sort());
            isiSelect('filter-mapel', Array.from(setMapel).sort());
        }

        function isiSelect(id, arr) {
            const el = document.getElementById(id);
            const valBefore = el.value; 
            el.innerHTML = `<option value="">- Semua -</option>`;
            arr.forEach(item => el.add(new Option(item, item)));
            if(arr.includes(valBefore)) el.value = valBefore;
        }

        window.terapkanFilterLokal = () => {
            const fGuru = document.getElementById('filter-guru').value;
            const fKelas = document.getElementById('filter-kelas').value;
            const fMapel = document.getElementById('filter-mapel').value;

            const filteredData = rawDataJurnal.filter(d => {
                return (!fGuru || d.nama_guru === fGuru) &&
                       (!fKelas || d.kelas === fKelas) &&
                       (!fMapel || d.mapel === fMapel);
            });

            renderTabel(filteredData);
            hitungStatistik(filteredData);
        }

        // 3. RENDER TABEL (DARK MODE)
        function renderTabel(data) {
            const tbody = document.getElementById('tabel-monitor');
            tbody.innerHTML = '';

            if(data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-10 text-center text-slate-500 italic">Tidak ada data yang cocok.</td></tr>';
                return;
            }

            data.forEach(d => {
                const jam = d.waktu ? new Date(d.waktu.seconds*1000).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) : '-';
                
                // BAR REFLEKSI
                const ref = rawDataRefleksi[d.id] || { r4:0, r3:0, r2:0, r1:0, total:0, komen:[] };
                let barRefleksi = `<span class="text-xs text-slate-600 italic">Menunggu respon...</span>`;
                let btnKomen = '';

                if(ref.total > 0) {
                    const p4 = (ref.r4 / ref.total) * 100;
                    const p3 = (ref.r3 / ref.total) * 100;
                    const p2 = (ref.r2 / ref.total) * 100;
                    const p1 = (ref.r1 / ref.total) * 100;
                    
                    barRefleksi = `
                        <div class="flex justify-between text-[10px] font-bold text-slate-400 mb-1">
                            <span class="text-emerald-400">🥰 ${ref.r4}</span>
                            <span class="text-yellow-400">💡 ${ref.r3}</span>
                            <span class="text-orange-400">😵‍💫 ${ref.r2}</span>
                            <span class="text-slate-500">😴 ${ref.r1}</span>
                        </div>
                        <div class="progress-stack">
                            <div class="bar-4" style="width: ${p4}%" title="Senang"></div>
                            <div class="bar-3" style="width: ${p3}%" title="Terinspirasi"></div>
                            <div class="bar-2" style="width: ${p2}%" title="Kurang Paham"></div>
                            <div class="bar-1" style="width: ${p1}%" title="Ngantuk"></div>
                        </div>
                    `;

                    if(ref.komen.length > 0) {
                        const komenJson = encodeURIComponent(JSON.stringify(ref.komen));
                        btnKomen = `<button onclick="lihatKomen('${komenJson}')" class="mt-2 text-[10px] bg-slate-800 text-slate-300 border border-slate-700 px-2 py-1 rounded font-bold hover:bg-slate-700 transition flex items-center gap-1"><i data-lucide="message-square" class="w-3 h-3"></i> ${ref.komen.length} Testimoni</button>`;
                    }
                }

                // LIST ABSENSI
                let listAbsen = [];
                if(d.absensi_mapel) {
                    d.absensi_mapel.forEach(s => {
                        if(s.status !== 'H') {
                            let warna = s.status==='A'?'text-red-400 font-bold':(s.status==='S'?'text-blue-400':'text-orange-400');
                            if(s.status === 'D') warna = 'text-purple-400 font-bold';
                            listAbsen.push(`<span class="${warna} block">${s.nama} (${s.status})</span>`);
                        }
                    });
                }
                const absenHtml = listAbsen.length > 0 ? `<div class="text-[10px] leading-tight space-y-1">${listAbsen.join('')}</div>` : `<span class="text-[10px] font-bold text-emerald-400 bg-emerald-900/30 px-2 py-1 rounded-full">Lengkap (Nihil)</span>`;

                tbody.innerHTML += `
                    <tr class="hover:bg-slate-900 border-b border-slate-800 transition">
                        <td class="p-4 align-top font-mono font-bold text-yellow-500 text-xs">${jam}</td>
                        <td class="p-4 align-top">
                            <span class="bg-slate-800 text-slate-300 border border-slate-700 font-bold px-2 py-1 rounded text-xs">${d.kelas}</span>
                        </td>
                        <td class="p-4 align-top">
                            <div class="font-bold text-white">${d.nama_guru}</div>
                            <div class="text-xs text-yellow-600 font-bold mt-0.5">${d.mapel}</div>
                            <p class="text-[10px] text-slate-400 mt-2 line-clamp-2 bg-slate-950 p-2 rounded border border-slate-800">"${d.materi}"</p>
                        </td>
                        <td class="p-4 align-top">
                            ${barRefleksi}
                            ${btnKomen}
                        </td>
                        <td class="p-4 align-top">
                            ${absenHtml}
                        </td>
                        <td class="p-4 align-top text-center">
                            ${d.foto_bukti ? `<img src="${d.foto_bukti}" onclick="Swal.fire({imageUrl:'${d.foto_bukti}', showConfirmButton:false, width:'400px', background:'#1e293b'})" class="w-10 h-10 object-cover rounded-lg border border-slate-700 cursor-pointer hover:scale-110 transition">` : '-'}
                        </td>
                    </tr>
                `;
            });
            lucide.createIcons();
        }

        // 4. MODAL KOMENTAR (DARK)
        window.lihatKomen = (jsonStr) => {
            const data = JSON.parse(decodeURIComponent(jsonStr));
            const cont = document.getElementById('list-komen');
            cont.innerHTML = '';
            
            data.forEach(k => {
                let emot = '😴';
                if(k.rating == 4) emot = '🥰';
                else if(k.rating == 3) emot = '💡';
                else if(k.rating == 2) emot = '😵‍💫';

                cont.innerHTML += `
                    <div class="border-b border-slate-800 pb-2 last:border-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-lg">${emot}</span>
                            <span class="text-xs font-bold text-slate-300">${k.nama}</span>
                        </div>
                        <p class="text-xs text-slate-400 italic bg-slate-950 p-2 rounded border border-slate-800">"${k.text}"</p>
                    </div>
                `;
            });
            document.getElementById('modal-komen').classList.remove('hidden');
        }

        // 5. STATISTIK
        function hitungStatistik(data) {
            document.getElementById('stat-total').innerText = data.length;
            const guruUnik = new Set(data.map(d => d.nama_guru));
            document.getElementById('stat-guru').innerText = guruUnik.size;

            let totalPositif = 0, totalRespon = 0, totalAbsen = 0;
            data.forEach(d => {
                const r = rawDataRefleksi[d.id];
                if(r && r.total > 0) {
                    totalRespon += r.total;
                    totalPositif += (r.r4 + r.r3);
                }
                if(d.absensi_mapel) {
                    totalAbsen += d.absensi_mapel.filter(s => s.status !== 'H').length;
                }
            });

            const persen = totalRespon > 0 ? Math.round((totalPositif/totalRespon)*100) : 0;
            document.getElementById('stat-kepuasan').innerText = persen + "%";
            document.getElementById('stat-absen').innerText = totalAbsen;
        }

        // 6. EXCEL
        window.downloadLaporanExcel = () => {
            const fGuru = document.getElementById('filter-guru').value;
            const fKelas = document.getElementById('filter-kelas').value;
            const activeData = rawDataJurnal.filter(d => (!fGuru || d.nama_guru === fGuru) && (!fKelas || d.kelas === fKelas));
            
            if(activeData.length === 0) return Swal.fire('Data Kosong', 'Tidak ada data untuk diunduh', 'warning');

            const exportData = activeData.map(d => {
                const ref = rawDataRefleksi[d.id] || { r4:0, r3:0, r2:0, r1:0, total:0, komen:[] };
                const absenStr = d.absensi_mapel ? d.absensi_mapel.filter(s=>s.status!=='H').map(s=>`${s.nama}(${s.status})`).join(', ') : '-';
                
                const komenStr = ref.komen.map(k => {
                    let lbl = 'Ngantuk';
                    if(k.rating==4) lbl='Senang'; else if(k.rating==3) lbl='Inspirasi'; else if(k.rating==2) lbl='KrgPaham';
                    return `[${lbl}] ${k.nama}: ${k.text}`;
                }).join('\n');

                return {
                    TANGGAL: d.tanggal,
                    WAKTU: d.waktu ? new Date(d.waktu.seconds*1000).toLocaleTimeString('id-ID') : '-',
                    KELAS: d.kelas,
                    GURU: d.nama_guru,
                    MAPEL: d.mapel,
                    MATERI: d.materi,
                    SISWA_ABSEN: absenStr,
                    'SENANG (4)': ref.r4,
                    'TERINSPIRASI (3)': ref.r3,
                    'KURANG PAHAM (2)': ref.r2,
                    'NGANTUK (1)': ref.r1,
                    TESTIMONI_SISWA: komenStr
                }
            });

            const ws = XLSX.utils.json_to_sheet(exportData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Rekap Monitoring");
            XLSX.writeFile(wb, `MONITORING_KBM_${document.getElementById('filter-tanggal').value}.xlsx`);
        }
        
        window.logout = () => { localStorage.clear(); window.location.href='/logout.php'; }
    </script>
</body>
</html>
