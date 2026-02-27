/**
 * ortu_detail.js
 * Halaman detail anak untuk orang tua
 * Menampilkan informasi kehadiran, jurnal, dan 7 kebiasaan
 */

document.addEventListener('DOMContentLoaded', function() {
    // =========================================
    // 1. AMBIL PARAMETER DARI URL
    // =========================================
    const urlParams = new URLSearchParams(window.location.search);
    const siswaUsername = urlParams.get('siswa');
    const siswaNama = urlParams.get('nama'); // opsional, bisa ditampilkan di judul
    
    // Jika tidak ada parameter siswa, tampilkan error
    if (!siswaUsername) {
        tampilkanError('Parameter siswa tidak ditemukan. Silakan kembali ke halaman sebelumnya.');
        return;
    }
    
    // Tampilkan judul halaman
    if (siswaNama) {
        document.getElementById('page-title').textContent = `Detail ${siswaNama}`;
    }
    
    // =========================================
    // 2. TAMPILKAN LOADING
    // =========================================
    tampilkanLoading(true);
    
    // =========================================
    // 3. PANGGIL API UNTUK MENDAPATKAN DATA
    // =========================================
    fetch(`/api/ortu_siswa_detail.php?siswa=${encodeURIComponent(siswaUsername)}`, {
        credentials: 'include' // penting untuk mengirim cookie session
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // Sembunyikan loading
        tampilkanLoading(false);
        
        // Cek apakah response sukses
        if (!data.ok) {
            tampilkanError(data.error || 'Gagal memuat data siswa');
            return;
        }
        
        // Render data ke halaman
        renderSemuaData(data.data);
    })
    .catch(error => {
        tampilkanLoading(false);
        tampilkanError('Terjadi kesalahan koneksi: ' + error.message);
        console.error('Error:', error);
    });
    
    // =========================================
    // 4. FUNGSI-FUNGSI BANTU
    // =========================================
    
    /**
     * Tampilkan atau sembunyikan loading spinner
     */
    function tampilkanLoading(aktif) {
        const loadingEl = document.getElementById('loading');
        if (loadingEl) {
            loadingEl.style.display = aktif ? 'flex' : 'none';
        }
    }
    
    /**
     * Tampilkan pesan error di halaman
     */
    function tampilkanError(pesan) {
        const errorEl = document.getElementById('error-message');
        if (errorEl) {
            errorEl.textContent = pesan;
            errorEl.classList.remove('hidden');
        }
        
        // Sembunyikan konten utama
        const kontenEl = document.getElementById('main-content');
        if (kontenEl) {
            kontenEl.classList.add('hidden');
        }
    }
    
    /**
     * Render semua data ke elemen HTML yang sesuai
     */
    function renderSemuaData(data) {
        // Tampilkan konten utama
        document.getElementById('main-content').classList.remove('hidden');
        
        // =========================================
        // 4.1 INFORMASI SISWA
        // =========================================
        if (data.siswa) {
            document.getElementById('siswa-nama').textContent = data.siswa.nama || '-';
            document.getElementById('siswa-kelas').textContent = data.siswa.kelas || '-';
            document.getElementById('siswa-sekolah').textContent = data.siswa.sekolah || '-';
            document.getElementById('siswa-nisn').textContent = data.siswa.nisn || '-';
        }
        
        // =========================================
        // 4.2 STATUS HARI INI (KARTU)
        // =========================================
        if (data.hari_ini) {
            const h = data.hari_ini;
            
            // Tentukan warna berdasarkan status
            let bgColor = 'bg-slate-100';
            let textColor = 'text-slate-800';
            let statusBadge = '';
            
            if (h.status === 'hadir' || h.status === 'Masuk') {
                bgColor = 'bg-emerald-50';
                textColor = 'text-emerald-800';
                statusBadge = '<span class="px-3 py-1 bg-emerald-500 text-white rounded-full text-xs font-bold">HADIR</span>';
            } else if (h.status === 'sakit') {
                bgColor = 'bg-blue-50';
                textColor = 'text-blue-800';
                statusBadge = '<span class="px-3 py-1 bg-blue-500 text-white rounded-full text-xs font-bold">SAKIT</span>';
            } else if (h.status === 'izin') {
                bgColor = 'bg-yellow-50';
                textColor = 'text-yellow-800';
                statusBadge = '<span class="px-3 py-1 bg-yellow-500 text-white rounded-full text-xs font-bold">IZIN</span>';
            } else if (h.status === 'alpha' || !h.jam_masuk) {
                bgColor = 'bg-rose-50';
                textColor = 'text-rose-800';
                statusBadge = '<span class="px-3 py-1 bg-rose-500 text-white rounded-full text-xs font-bold">ALPHA</span>';
            }
            
            // Format jam masuk/pulang
            const jamMasuk = h.jam_masuk ? new Date(h.jam_masuk).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--';
            const jamPulang = h.jam_pulang ? new Date(h.jam_pulang).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--';
            
            // Buat HTML kartu
            document.getElementById('status-hari-ini').innerHTML = `
                <div class="${bgColor} ${textColor} p-6 rounded-2xl border-2 border-slate-200 shadow-lg">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="font-bold text-lg">Hari Ini</h3>
                        ${statusBadge}
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-xs opacity-70">Jam Masuk</p>
                            <p class="text-2xl font-black">${jamMasuk}</p>
                        </div>
                        <div>
                            <p class="text-xs opacity-70">Jam Pulang</p>
                            <p class="text-2xl font-black">${jamPulang}</p>
                        </div>
                    </div>
                    ${h.foto_masuk ? `
                    <div class="mt-2">
                        <p class="text-xs opacity-70 mb-1">Foto Masuk</p>
                        <img src="${h.foto_masuk}" class="w-24 h-24 object-cover rounded-lg border-2 border-white shadow" alt="foto masuk">
                    </div>
                    ` : ''}
                </div>
            `;
        } else {
            document.getElementById('status-hari-ini').innerHTML = `
                <div class="bg-slate-100 p-6 rounded-2xl border-2 border-slate-200 text-center">
                    <p class="text-slate-500">Belum ada data kehadiran hari ini</p>
                </div>
            `;
        }
        
        // =========================================
        // 4.3 JURNAL HARI INI
        // =========================================
        const jurnalContainer = document.getElementById('jurnal-hari-ini');
        jurnalContainer.innerHTML = '';
        
        if (data.jurnal && data.jurnal.length > 0) {
            data.jurnal.forEach(j => {
                const jamMulai = j.jam_ke_mulai ? `Jam ke-${j.jam_ke_mulai}` : '-';
                const jamSelesai = j.jam_ke_selesai ? `- ${j.jam_ke_selesai}` : '';
                
                jurnalContainer.innerHTML += `
                    <div class="bg-white p-4 rounded-xl border border-slate-200 mb-3 hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-bold text-emerald-700">${j.mapel || '-'}</span>
                            <span class="text-xs bg-slate-100 px-2 py-1 rounded-full">${jamMulai} ${jamSelesai}</span>
                        </div>
                        <p class="text-sm text-slate-700 mb-2">${j.materi || '-'}</p>
                        ${j.catatan ? `<p class="text-xs text-slate-500 italic">${j.catatan}</p>` : ''}
                        <p class="text-xs text-slate-400 mt-2">Guru: ${j.guru_nama || '-'}</p>
                    </div>
                `;
            });
        } else {
            jurnalContainer.innerHTML = '<p class="text-slate-500 italic p-4 bg-slate-50 rounded-xl">Belum ada jurnal untuk hari ini</p>';
        }
        
        // =========================================
        // 4.4 RIWAYAT 7 HARI (TABEL)
        // =========================================
        const tbody = document.getElementById('riwayat-tbody');
        tbody.innerHTML = '';
        
        if (data.riwayat && data.riwayat.length > 0) {
            data.riwayat.forEach(r => {
                // Format tanggal Indonesia
                const tgl = new Date(r.tanggal + 'T00:00:00');
                const tglStr = tgl.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' });
                
                // Status badge
                let badgeClass = 'bg-slate-100 text-slate-600';
                if (r.status === 'hadir' || r.status === 'Masuk') badgeClass = 'bg-emerald-100 text-emerald-700';
                else if (r.status === 'sakit') badgeClass = 'bg-blue-100 text-blue-700';
                else if (r.status === 'izin') badgeClass = 'bg-yellow-100 text-yellow-700';
                else if (r.status === 'alpha') badgeClass = 'bg-rose-100 text-rose-700';
                
                tbody.innerHTML += `
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-3 font-medium">${tglStr}</td>
                        <td class="p-3">${r.jam_masuk ? r.jam_masuk.slice(0,5) : '-'}</td>
                        <td class="p-3">${r.jam_pulang ? r.jam_pulang.slice(0,5) : '-'}</td>
                        <td class="p-3"><span class="px-2 py-1 rounded-full text-xs font-bold ${badgeClass}">${r.status || '-'}</span></td>
                        <td class="p-3 text-sm">${r.materi_singkat || '-'}</td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="p-6 text-center text-slate-500">Tidak ada riwayat</td></tr>';
        }
        
        // =========================================
        // 4.5 7 KEBIASAAN (GRAFIK SEDERHANA)
        // =========================================
        if (data.seven_hebat && data.seven_hebat.length > 0) {
            renderSevenHeavy(data.seven_hebat);
        } else {
            document.getElementById('seven-container').innerHTML = '<p class="text-slate-500 italic">Belum ada data 7 kebiasaan</p>';
        }
    }
    
    /**
     * Render data 7 kebiasaan dalam bentuk grafik batang sederhana
     */
    function renderSevenHeavy(data) {
        const container = document.getElementById('seven-container');
        container.innerHTML = '';
        
        // Buat card untuk setiap hari
        data.forEach(hari => {
            const tgl = new Date(hari.tanggal + 'T00:00:00');
            const tglStr = tgl.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' });
            
            // Hitung persentase (max 7)
            const totalTerisi = Object.keys(hari.data || {}).filter(k => k.startsWith('k') && hari.data[k]).length;
            const persen = Math.round((totalTerisi / 7) * 100);
            
            // Warna berdasarkan persentase
            let bgColor = 'bg-red-500';
            if (persen >= 80) bgColor = 'bg-emerald-500';
            else if (persen >= 50) bgColor = 'bg-yellow-500';
            
            container.innerHTML += `
                <div class="bg-white p-4 rounded-xl border border-slate-200 mb-3">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-bold">${tglStr}</span>
                        <span class="text-sm bg-slate-100 px-3 py-1 rounded-full">${totalTerisi}/7</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-4 mb-3">
                        <div class="${bgColor} h-4 rounded-full" style="width: ${persen}%"></div>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center text-xs">
                        <div class="p-1 ${hari.data?.k1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100'} rounded">Bangun</div>
                        <div class="p-1 ${hari.data?.k2 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100'} rounded">Ibadah</div>
                        <div class="p-1 ${hari.data?.k3 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100'} rounded">Olahraga</div>
                        <div class="p-1 ${hari.data?.k4 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100'} rounded">Makan</div>
                        <div class="p-1 ${hari.data?.k5 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100'} rounded">Belajar</div>
                        <div class="p-1 ${hari.data?.k6 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100'} rounded">Sosial</div>
                        <div class="p-1 ${hari.data?.k7 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100'} rounded">Tidur</div>
                    </div>
                    ${hari.catatan ? `<p class="text-xs text-slate-500 mt-2 italic">"${hari.catatan}"</p>` : ''}
                </div>
            `;
        });
    }
});