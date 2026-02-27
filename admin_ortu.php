<?php
require __DIR__ . '/auth/guard.php';
require_login(['admin', 'super']); // hanya admin & super yang bisa akses

require __DIR__ . '/config/database.php';
$sid = school_id();

$user = $_SESSION['user'] ?? [];
$nama = $user['name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Orang Tua - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f1f5f9; }
        .modal { transition: opacity 0.2s ease; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-black text-slate-800">Manajemen Orang Tua</h1>
                <p class="text-slate-600">Kelola akun orang tua dan hubungkan dengan siswa.</p>
            </div>
            <div class="flex gap-3">
                <a href="admin_full.php" class="px-4 py-2 bg-slate-200 rounded-lg hover:bg-slate-300 transition">← Kembali</a>
                <button onclick="openModal()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition flex items-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i> Tambah Orang Tua
                </button>
            </div>
        </div>

        <!-- Tabel Orang Tua -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="p-4 text-left">No</th>
                            <th class="p-4 text-left">Username</th>
                            <th class="p-4 text-left">Nama</th>
                            <th class="p-4 text-left">Jumlah Anak</th>
                            <th class="p-4 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="ortu-tbody" class="divide-y divide-slate-200">
                        <tr><td colspan="5" class="p-8 text-center text-slate-500">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH/EDIT ORANG TUA -->
    <div id="modal" class="modal fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-full max-w-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modal-title" class="text-xl font-bold text-slate-800">Tambah Orang Tua</h2>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="ortu-form" onsubmit="saveOrtu(event)">
                <input type="hidden" id="edit-username" name="edit-username">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Orang Tua <span class="text-red-500">*</span></label>
                    <input type="text" id="nama" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Username <span class="text-red-500">*</span></label>
                    <input type="text" id="username" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                    <p class="text-xs text-slate-500 mt-1">Contoh: 12345678ortu (NISN anak + "ortu")</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" id="password" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                    <p class="text-xs text-slate-500 mt-1">Kosongkan = random (hanya saat tambah)</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Pilih Anak (bisa lebih dari satu)</label>
                    <select id="anak" multiple class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none min-h-[120px]">
                        <!-- akan diisi oleh JS -->
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Tekan Ctrl/Cmd untuk memilih lebih dari satu.</p>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        // State
        let daftarSiswa = [];
        let daftarOrtu = [];

        // Ambil data siswa untuk dropdown
        async function loadSiswa() {
            try {
                const res = await fetch('/api/siswa_list.php', { credentials: 'include' });
                const data = await res.json();
                if (data.ok) {
                    daftarSiswa = data.data;
                    renderSiswaDropdown();
                }
            } catch (e) {
                console.error('Gagal load siswa:', e);
            }
        }

        function renderSiswaDropdown() {
            const select = document.getElementById('anak');
            select.innerHTML = '';
            daftarSiswa.forEach(s => {
                const option = document.createElement('option');
                option.value = s.username;
                option.textContent = `${s.nama} (${s.kelas})`;
                select.appendChild(option);
            });
        }

        // Ambil daftar orang tua
        async function loadOrtu() {
            try {
                const res = await fetch('/api/ortu_list.php', { credentials: 'include' });
                const data = await res.json();
                if (data.ok) {
                    daftarOrtu = data.data;
                    renderTabel();
                } else {
                    alert('Gagal memuat data: ' + data.error);
                }
            } catch (e) {
                console.error(e);
            }
        }

        function renderTabel() {
            const tbody = document.getElementById('ortu-tbody');
            if (!daftarOrtu.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-slate-500">Belum ada data</td></tr>';
                return;
            }
            tbody.innerHTML = daftarOrtu.map((o, idx) => `
                <tr class="hover:bg-slate-50">
                    <td class="p-4">${idx+1}</td>
                    <td class="p-4 font-mono">${escapeHtml(o.username)}</td>
                    <td class="p-4">${escapeHtml(o.nama)}</td>
                    <td class="p-4">${o.jumlah_anak}</td>
                    <td class="p-4">
                        <button onclick="editOrtu('${o.username}')" class="text-blue-600 hover:text-blue-800 mr-2">
                            <i data-lucide="edit" class="w-4 h-4 inline"></i>
                        </button>
                        <button onclick="hapusOrtu('${o.username}')" class="text-red-600 hover:text-red-800">
                            <i data-lucide="trash-2" class="w-4 h-4 inline"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            lucide.createIcons();
        }

        function escapeHtml(unsafe) {
            return String(unsafe).replace(/[&<>"']/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                if (m === '"') return '&quot;';
                return '&#039;';
            });
        }

        // Modal
        function openModal() {
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('modal').classList.add('flex');
            document.getElementById('modal-title').textContent = 'Tambah Orang Tua';
            document.getElementById('ortu-form').reset();
            document.getElementById('edit-username').value = '';
            document.getElementById('password').required = false;
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
            document.getElementById('modal').classList.remove('flex');
        }

        // Edit: isi form dengan data ortu
        async function editOrtu(username) {
            const ortu = daftarOrtu.find(o => o.username === username);
            if (!ortu) return;

            document.getElementById('modal-title').textContent = 'Edit Orang Tua';
            document.getElementById('edit-username').value = ortu.username;
            document.getElementById('nama').value = ortu.nama;
            document.getElementById('username').value = ortu.username;
            document.getElementById('username').disabled = true; // username tidak bisa diubah
            document.getElementById('password').required = false;
            document.getElementById('password').placeholder = 'Kosongkan jika tidak diganti';

            // Pilih anak yang sudah terhubung
            try {
                const res = await fetch(`/api/ortu_anak_list.php?username=${encodeURIComponent(username)}`, { credentials: 'include' });
                const data = await res.json();
                if (data.ok) {
                    const anakTerpilih = data.data.map(a => a.username);
                    const select = document.getElementById('anak');
                    Array.from(select.options).forEach(opt => {
                        opt.selected = anakTerpilih.includes(opt.value);
                    });
                }
            } catch (e) {
                console.error(e);
            }

            openModal();
        }

        // Hapus ortu
        async function hapusOrtu(username) {
            if (!confirm(`Hapus orang tua ${username}?`)) return;
            try {
                const res = await fetch('/api/ortu_delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ username })
                });
                const data = await res.json();
                if (data.ok) {
                    await loadOrtu();
                } else {
                    alert('Gagal: ' + data.error);
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }

        // Simpan (create/update)
        async function saveOrtu(event) {
            event.preventDefault();

            const editUsername = document.getElementById('edit-username').value;
            const isEdit = !!editUsername;

            const payload = {
                mode: isEdit ? 'update' : 'create',
                nama: document.getElementById('nama').value,
                username: document.getElementById('username').value,
                password: document.getElementById('password').value,
                anak: Array.from(document.getElementById('anak').selectedOptions).map(opt => opt.value)
            };

            if (isEdit) {
                payload.original_username = editUsername; // username lama untuk identifikasi di backend
            }

            try {
                const res = await fetch('/api/ortu_save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.ok) {
                    closeModal();
                    await loadOrtu();
                } else {
                    alert('Gagal: ' + data.error);
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }

        // Inisialisasi
        document.addEventListener('DOMContentLoaded', () => {
            loadSiswa();
            loadOrtu();
        });
    </script>
</body>
</html>