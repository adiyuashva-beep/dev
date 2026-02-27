<?php
require __DIR__ . '/auth/guard.php';
require_login(['admin', 'super']); // hanya admin sekolah & super yang bisa edit

require __DIR__ . '/config/database.php';
$sid = school_id();

$user = $_SESSION['user'] ?? [];
$nama = $user['name'] ?? 'Admin';

// Ambil data sekolah saat ini
$stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
$stmt->execute([$sid]);
$sekolah = $stmt->fetch();

if (!$sekolah) {
    die("Data sekolah tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Sekolah - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f1f5f9; }
        select:disabled { background: #e2e8f0; cursor: not-allowed; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-black text-slate-800">Profil Sekolah</h1>
            <a href="admin_full.php" class="px-4 py-2 bg-slate-200 rounded-lg hover:bg-slate-300 transition">← Kembali</a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <form id="form-profil" onsubmit="simpanProfil(event)">
                <!-- Data Dasar Sekolah (readonly) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Sekolah</label>
                        <input type="text" id="nama_sekolah" value="<?= htmlspecialchars($sekolah['nama_sekolah']) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-100" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jenjang</label>
                        <input type="text" id="jenjang" value="<?= htmlspecialchars($sekolah['jenjang']) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-100" readonly>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">NPSN <span class="text-red-500">*</span></label>
                        <input type="text" id="npsn" value="<?= htmlspecialchars($sekolah['npsn'] ?? '') ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kode Pos</label>
                        <input type="text" id="kode_pos" value="<?= htmlspecialchars($sekolah['kode_pos'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                    </div>
                </div>

                <!-- ===== WILAYAH CASCADE ===== -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Provinsi <span class="text-red-500">*</span></label>
                        <select id="provinsi" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                            <option value="">-- Pilih Provinsi --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kabupaten/Kota <span class="text-red-500">*</span></label>
                        <select id="kabupaten" required disabled class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                            <option value="">-- Pilih Kabupaten --</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
                        <select id="kecamatan" required disabled class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                            <option value="">-- Pilih Kecamatan --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kelurahan/Desa <span class="text-red-500">*</span></label>
                        <select id="kelurahan" required disabled class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                            <option value="">-- Pilih Kelurahan --</option>
                        </select>
                    </div>
                </div>

                <!-- Hidden fields untuk menyimpan nama wilayah (diisi otomatis) -->
                <input type="hidden" id="provinsi_nama" name="provinsi_nama">
                <input type="hidden" id="kabupaten_nama" name="kabupaten_nama">
                <input type="hidden" id="kecamatan_nama" name="kecamatan_nama">
                <input type="hidden" id="kelurahan_nama" name="kelurahan_nama">

                <!-- Alamat (teks panjang) -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
                    <textarea id="alamat" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"><?= htmlspecialchars($sekolah['alamat'] ?? '') ?></textarea>
                </div>

                <!-- Data tersimpan (untuk prefill) -->
                <input type="hidden" id="provinsi_tersimpan" value="<?= htmlspecialchars($sekolah['provinsi'] ?? '') ?>">
                <input type="hidden" id="kabupaten_tersimpan" value="<?= htmlspecialchars($sekolah['kabupaten'] ?? '') ?>">
                <input type="hidden" id="kecamatan_tersimpan" value="<?= htmlspecialchars($sekolah['kecamatan'] ?? '') ?>">
                <input type="hidden" id="kelurahan_tersimpan" value="<?= htmlspecialchars($sekolah['kelurahan'] ?? '') ?>">

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition font-medium">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        // =========================================
        // 1. LOAD PROVINSI
        // =========================================
        async function loadProvinsi() {
            try {
                const res = await fetch('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
                const data = await res.json();
                const select = document.getElementById('provinsi');
                select.innerHTML = '<option value="">-- Pilih Provinsi --</option>';

                // Urutkan berdasarkan nama
                data.sort((a, b) => a.name.localeCompare(b.name));

                data.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p.id; // ID provinsi (misal '33')
                    option.textContent = p.name;
                    select.appendChild(option);
                });

                // Cek apakah ada provinsi tersimpan
                const provinsiTersimpan = document.getElementById('provinsi_tersimpan').value;
                if (provinsiTersimpan) {
                    // Cari ID provinsi berdasarkan nama (karena kita simpan nama, bukan ID)
                    const found = data.find(p => p.name === provinsiTersimpan);
                    if (found) {
                        select.value = found.id;
                        select.dispatchEvent(new Event('change', { target: { value: found.id } }));
                    }
                }
            } catch (e) {
                console.error('Gagal load provinsi:', e);
                alert('Gagal memuat data provinsi. Periksa koneksi internet.');
            }
        }

        // =========================================
        // 2. SAAT PROVINSI DIPILIH → LOAD KABUPATEN
        // =========================================
        document.getElementById('provinsi').addEventListener('change', async function(e) {
            const provId = e.target.value;
            const selectKab = document.getElementById('kabupaten');
            const selectKec = document.getElementById('kecamatan');
            const selectKel = document.getElementById('kelurahan');

            // Reset dropdown
            selectKab.innerHTML = '<option value="">-- Pilih Kabupaten --</option>';
            selectKec.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
            selectKel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';

            if (!provId) {
                selectKab.disabled = true;
                selectKec.disabled = true;
                selectKel.disabled = true;
                return;
            }

            selectKab.disabled = true;
            selectKec.disabled = true;
            selectKel.disabled = true;

            try {
                const res = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/regencies/${provId}.json`);
                const data = await res.json();
                data.sort((a, b) => a.name.localeCompare(b.name));

                data.forEach(k => {
                    const option = document.createElement('option');
                    option.value = k.id;
                    option.textContent = k.name;
                    selectKab.appendChild(option);
                });

                selectKab.disabled = false;

                // Cek apakah ada kabupaten tersimpan
                const kabTersimpan = document.getElementById('kabupaten_tersimpan').value;
                if (kabTersimpan) {
                    const found = data.find(k => k.name === kabTersimpan);
                    if (found) {
                        selectKab.value = found.id;
                        selectKab.dispatchEvent(new Event('change', { target: { value: found.id } }));
                    }
                }
            } catch (e) {
                console.error('Gagal load kabupaten:', e);
                selectKab.innerHTML = '<option value="">Gagal memuat data</option>';
            }
        });

        // =========================================
        // 3. SAAT KABUPATEN DIPILIH → LOAD KECAMATAN
        // =========================================
        document.getElementById('kabupaten').addEventListener('change', async function(e) {
            const kabId = e.target.value;
            const selectKec = document.getElementById('kecamatan');
            const selectKel = document.getElementById('kelurahan');

            selectKec.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
            selectKel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';

            if (!kabId) {
                selectKec.disabled = true;
                selectKel.disabled = true;
                return;
            }

            selectKec.disabled = true;
            selectKel.disabled = true;

            try {
                const res = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/districts/${kabId}.json`);
                const data = await res.json();
                data.sort((a, b) => a.name.localeCompare(b.name));

                data.forEach(k => {
                    const option = document.createElement('option');
                    option.value = k.id;
                    option.textContent = k.name;
                    selectKec.appendChild(option);
                });

                selectKec.disabled = false;

                // Cek apakah ada kecamatan tersimpan
                const kecTersimpan = document.getElementById('kecamatan_tersimpan').value;
                if (kecTersimpan) {
                    const found = data.find(k => k.name === kecTersimpan);
                    if (found) {
                        selectKec.value = found.id;
                        selectKec.dispatchEvent(new Event('change', { target: { value: found.id } }));
                    }
                }
            } catch (e) {
                console.error('Gagal load kecamatan:', e);
                selectKec.innerHTML = '<option value="">Gagal memuat data</option>';
            }
        });

        // =========================================
        // 4. SAAT KECAMATAN DIPILIH → LOAD KELURAHAN
        // =========================================
        document.getElementById('kecamatan').addEventListener('change', async function(e) {
            const kecId = e.target.value;
            const selectKel = document.getElementById('kelurahan');

            selectKel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';

            if (!kecId) {
                selectKel.disabled = true;
                return;
            }

            selectKel.disabled = true;

            try {
                const res = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/villages/${kecId}.json`);
                const data = await res.json();
                data.sort((a, b) => a.name.localeCompare(b.name));

                data.forEach(k => {
                    const option = document.createElement('option');
                    option.value = k.id;
                    option.textContent = k.name;
                    selectKel.appendChild(option);
                });

                selectKel.disabled = false;

                // Cek apakah ada kelurahan tersimpan
                const kelTersimpan = document.getElementById('kelurahan_tersimpan').value;
                if (kelTersimpan) {
                    const found = data.find(k => k.name === kelTersimpan);
                    if (found) {
                        selectKel.value = found.id;
                        // Nama kelurahan akan diambil saat submit
                    }
                }
            } catch (e) {
                console.error('Gagal load kelurahan:', e);
                selectKel.innerHTML = '<option value="">Gagal memuat data</option>';
            }
        });

        // =========================================
        // 5. SIMPAN DATA
        // =========================================
        async function simpanProfil(event) {
            event.preventDefault();

            // Ambil nama dari dropdown (bukan ID)
            const provinsiSelect = document.getElementById('provinsi');
            const kabupatenSelect = document.getElementById('kabupaten');
            const kecamatanSelect = document.getElementById('kecamatan');
            const kelurahanSelect = document.getElementById('kelurahan');

            const provinsiNama = provinsiSelect.options[provinsiSelect.selectedIndex]?.text || '';
            const kabupatenNama = kabupatenSelect.options[kabupatenSelect.selectedIndex]?.text || '';
            const kecamatanNama = kecamatanSelect.options[kecamatanSelect.selectedIndex]?.text || '';
            const kelurahanNama = kelurahanSelect.options[kelurahanSelect.selectedIndex]?.text || '';

            if (!provinsiNama || !kabupatenNama || !kecamatanNama || !kelurahanNama) {
                alert('Harap lengkapi semua data wilayah (provinsi sampai kelurahan).');
                return;
            }

            const data = {
                npsn: document.getElementById('npsn').value,
                kode_pos: document.getElementById('kode_pos').value,
                alamat: document.getElementById('alamat').value,
                provinsi: provinsiNama,
                kabupaten: kabupatenNama,
                kecamatan: kecamatanNama,
                kelurahan: kelurahanNama
            };

            try {
                const res = await fetch('/api/profil_sekolah_update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.ok) {
                    alert('Data berhasil disimpan!');
                    // Update hidden fields untuk prefill berikutnya
                    document.getElementById('provinsi_tersimpan').value = provinsiNama;
                    document.getElementById('kabupaten_tersimpan').value = kabupatenNama;
                    document.getElementById('kecamatan_tersimpan').value = kecamatanNama;
                    document.getElementById('kelurahan_tersimpan').value = kelurahanNama;
                } else {
                    alert('Gagal: ' + result.error);
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }

        // Inisialisasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            loadProvinsi();
        });
    </script>
</body>
</html>