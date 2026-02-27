<?php
require __DIR__ . '/auth/guard.php';
require_login(['ortu']); // hanya role ortu yang bisa akses

require __DIR__ . '/config/database.php';
$sid = school_id();

$user = $_SESSION['user'] ?? [];
$ortu_username = $user['username'] ?? '';
$nama = $user['name'] ?? 'Orang Tua';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Orang Tua - EduGate</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="min-h-screen bg-slate-50">

    <header class="bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-black text-slate-800">EduGate <span class="text-emerald-600">Orang Tua</span></h1>
            <div class="flex items-center gap-4">
                <span class="text-sm text-slate-600"><?= htmlspecialchars($nama) ?></span>
                <a href="logout.php" class="text-red-500 hover:text-red-700">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <h2 class="text-3xl font-black text-slate-800 mb-6">Anak Anda</h2>

        <!-- Loading State -->
        <div id="loading" class="flex justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
        </div>

        <!-- Daftar Anak (akan diisi JS) -->
        <div id="daftar-anak" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 hidden"></div>

        <!-- Pesan jika tidak ada anak -->
        <div id="empty-message" class="hidden text-center py-12 bg-white rounded-2xl border border-slate-200">
            <i data-lucide="users" class="w-16 h-16 text-slate-300 mx-auto mb-4"></i>
            <p class="text-slate-500">Belum ada data anak terhubung.</p>
            <p class="text-sm text-slate-400 mt-2">Hubungi admin sekolah untuk informasi lebih lanjut.</p>
        </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        async function loadAnak() {
            try {
                const res = await fetch('/api/ortu_anak_saya.php', { credentials: 'include' });
                const data = await res.json();
                document.getElementById('loading').classList.add('hidden');

                if (data.ok && data.data.length > 0) {
                    const container = document.getElementById('daftar-anak');
                    container.classList.remove('hidden');
                    container.innerHTML = data.data.map(anak => `
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                                    <span class="text-lg font-black text-emerald-700">${anak.inisial}</span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800">${escapeHtml(anak.nama)}</h3>
                                    <p class="text-sm text-slate-500">Kelas: ${escapeHtml(anak.kelas)}</p>
                                </div>
                            </div>
                            <p class="text-sm text-slate-600 mb-4">Sekolah: ${escapeHtml(anak.sekolah)}</p>
                            <div class="flex justify-end">
                                <a href="ortu_detail.php?siswa=${encodeURIComponent(anak.username)}" 
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                                    <i data-lucide="eye" class="w-4 h-4"></i> Lihat Detail
                                </a>
                            </div>
                        </div>
                    `).join('');
                    lucide.createIcons();
                } else {
                    document.getElementById('empty-message').classList.remove('hidden');
                }
            } catch (error) {
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('empty-message').classList.remove('hidden');
                document.getElementById('empty-message').querySelector('p').textContent = 'Gagal memuat data: ' + error.message;
            }
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

        document.addEventListener('DOMContentLoaded', loadAnak);
    </script>
</body>
</html>