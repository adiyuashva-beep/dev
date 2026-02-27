<?php
require __DIR__ . '/auth/guard.php';
require_login(['ortu']); // hanya role ortu yang boleh akses

$user = $_SESSION['user'] ?? [];
$nama = $user['name'] ?? 'Orang Tua';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Anak - EduGate Orang Tua</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="min-h-screen bg-slate-50">

    <!-- Header sederhana -->
    <header class="bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-black text-slate-800">EduGate <span class="text-emerald-600">Orang Tua</span></h1>
            <div class="flex items-center gap-4">
                <span class="text-sm text-slate-600"><?= htmlspecialchars($nama) ?></span>
                <a href="ortu.php" class="text-emerald-600 hover:text-emerald-800">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <!-- Loading Spinner -->
        <div id="loading" class="fixed inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-50" style="display: none;">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600 mx-auto mb-4"></div>
                <p class="text-slate-600 font-medium">Memuat data...</p>
            </div>
        </div>

        <!-- Pesan Error -->
        <div id="error-message" class="hidden bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6"></div>

        <!-- Konten Utama (tersembunyi sampai data loading) -->
        <div id="main-content" class="hidden space-y-6">
            
            <!-- Judul Halaman (diisi JS) -->
            <h1 id="page-title" class="text-3xl font-black text-slate-800 mb-2">Detail Siswa</h1>

            <!-- Kartu Identitas Siswa -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center">
                        <span class="text-2xl font-black text-emerald-700" id="siswa-inisial"></span>
                    </div>
                    <div>
                        <h2 id="siswa-nama" class="text-2xl font-bold text-slate-800"></h2>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-600 mt-1">
                            <span>Kelas: <span id="siswa-kelas" class="font-medium"></span></span>
                            <span>Sekolah: <span id="siswa-sekolah" class="font-medium"></span></span>
                            <span>NISN: <span id="siswa-nisn" class="font-medium"></span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Hari Ini -->
            <div id="status-hari-ini"></div>

            <!-- Jurnal Hari Ini -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="font-bold text-lg text-slate-800 mb-4 flex items-center gap-2">
                    <i data-lucide="book-open" class="w-5 h-5 text-emerald-600"></i>
                    Jurnal Hari Ini
                </h3>
                <div id="jurnal-hari-ini"></div>
            </div>

            <!-- Riwayat 7 Hari -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="font-bold text-lg text-slate-800 mb-4 flex items-center gap-2">
                    <i data-lucide="calendar" class="w-5 h-5 text-emerald-600"></i>
                    Riwayat 7 Hari Terakhir
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="p-3 text-left">Tanggal</th>
                                <th class="p-3 text-left">Masuk</th>
                                <th class="p-3 text-left">Pulang</th>
                                <th class="p-3 text-left">Status</th>
                                <th class="p-3 text-left">Materi</th>
                            </tr>
                        </thead>
                        <tbody id="riwayat-tbody" class="divide-y divide-slate-200"></tbody>
                    </table>
                </div>
            </div>

            <!-- 7 Kebiasaan -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="font-bold text-lg text-slate-800 mb-4 flex items-center gap-2">
                    <i data-lucide="star" class="w-5 h-5 text-emerald-600"></i>
                    7 Kebiasaan Anak Indonesia Hebat
                </h3>
                <div id="seven-container"></div>
            </div>
        </div>
    </main>

    <!-- Script untuk icon dan JavaScript utama -->
    <script>
        lucide.createIcons();
    </script>
    <script src="/assets/js/ortu_detail.js"></script>
</body>
</html>