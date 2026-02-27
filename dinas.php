<?php
require __DIR__ . '/auth/guard.php';
require_login(['dinas', 'super', 'admin']); // role dinas bisa akses

$user = $_SESSION['user'] ?? [];
$nama = $user['name'] ?? 'Dinas Pendidikan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dinas - EduGate</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f1f5f9; }
        .card { background: white; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-800">Dashboard Dinas Pendidikan</h1>
                <p class="text-slate-600">Selamat datang, <?= htmlspecialchars($nama) ?></p>
            </div>
            <div class="flex gap-3">
                <a href="admin_full.php" class="px-4 py-2 bg-slate-200 rounded-lg hover:bg-slate-300 transition">Panel Admin</a>
                <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">Logout</a>
            </div>
        </div>

        <!-- Filter -->
        <div class="card p-4 mb-6 flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Jenjang</label>
                <select id="filter-jenjang" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                    <option value="">Semua Jenjang</option>
                    <option value="SD">SD</option>
                    <option value="SMP">SMP</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Kecamatan</label>
                <select id="filter-kecamatan" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                    <option value="">Semua Kecamatan</option>
                </select>
            </div>
            <button onclick="loadData()" class="px-5 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition font-medium">
                Terapkan Filter
            </button>
        </div>

        <!-- Kartu Statistik -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6" id="cards">
            <!-- Akan diisi oleh JS -->
        </div>

        <!-- Grafik Tren -->
        <div class="card p-4 mb-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4">Tren 7 Hari Terakhir</h2>
            <canvas id="trendsChart" height="100"></canvas>
        </div>

        <!-- Live Feed -->
        <div class="card p-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-slate-800">Aktivitas Terbaru</h2>
                <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-1 rounded-full">Live</span>
            </div>
            <div class="space-y-2 max-h-80 overflow-y-auto pr-2" id="activities">
                <!-- Akan diisi oleh JS -->
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        // State
        let trendsChart = null;

        // Load kecamatan untuk dropdown filter
        async function loadKecamatan() {
            try {
                const res = await fetch('/api/dinas_kecamatan.php', { credentials: 'include' });
                const data = await res.json();
                if (data.ok) {
                    const select = document.getElementById('filter-kecamatan');
                    select.innerHTML = '<option value="">Semua Kecamatan</option>';
                    data.data.forEach(k => {
                        const option = document.createElement('option');
                        option.value = k;
                        option.textContent = k;
                        select.appendChild(option);
                    });
                }
            } catch (e) {
                console.error('Gagal load kecamatan:', e);
            }
        }

        // Load data statistik + grafik
        async function loadData() {
            const jenjang = document.getElementById('filter-jenjang').value;
            const kecamatan = document.getElementById('filter-kecamatan').value;

            // Ambil summary
            try {
                const res = await fetch(`/api/dinas_summary.php?jenjang=${encodeURIComponent(jenjang)}&kecamatan=${encodeURIComponent(kecamatan)}`, { credentials: 'include' });
                const data = await res.json();
                if (data.ok) {
                    renderCards(data.data);
                } else {
                    console.error(data.error);
                }
            } catch (e) {
                console.error('Gagal load summary:', e);
            }

            // Ambil trends
            try {
                const res = await fetch(`/api/dinas_trends.php?jenjang=${encodeURIComponent(jenjang)}&kecamatan=${encodeURIComponent(kecamatan)}`, { credentials: 'include' });
                const data = await res.json();
                if (data.ok) {
                    renderChart(data);
                } else {
                    console.error(data.error);
                }
            } catch (e) {
                console.error('Gagal load trends:', e);
            }
        }

        // Render kartu statistik
        function renderCards(d) {
            const cards = document.getElementById('cards');
            cards.innerHTML = `
                <div class="card p-4">
                    <p class="text-sm text-slate-500 uppercase">Sekolah Aktif</p>
                    <p class="text-3xl font-black text-slate-800">${d.sekolah}</p>
                </div>
                <div class="card p-4">
                    <p class="text-sm text-slate-500 uppercase">Total Siswa</p>
                    <p class="text-3xl font-black text-slate-800">${d.siswa}</p>
                </div>
                <div class="card p-4">
                    <p class="text-sm text-slate-500 uppercase">Total Guru</p>
                    <p class="text-3xl font-black text-slate-800">${d.guru}</p>
                </div>
                <div class="card p-4">
                    <p class="text-sm text-slate-500 uppercase">Kehadiran Siswa</p>
                    <p class="text-3xl font-black text-emerald-600">${d.hadir_siswa} <span class="text-sm text-slate-400">/ ${d.siswa}</span></p>
                </div>
                <div class="card p-4">
                    <p class="text-sm text-slate-500 uppercase">Kehadiran Guru</p>
                    <p class="text-3xl font-black text-emerald-600">${d.hadir_guru} <span class="text-sm text-slate-400">/ ${d.guru}</span></p>
                </div>
                <div class="card p-4">
                    <p class="text-sm text-slate-500 uppercase">Jurnal Hari Ini</p>
                    <p class="text-3xl font-black text-blue-600">${d.jurnal_hari_ini}</p>
                </div>
                <div class="card p-4">
                    <p class="text-sm text-slate-500 uppercase">7 Kebiasaan (Isi)</p>
                    <p class="text-3xl font-black text-purple-600">${d.seven_hari_ini}</p>
                </div>
                <div class="card p-4">
                    <p class="text-sm text-slate-500 uppercase">7 Kebiasaan (Valid)</p>
                    <p class="text-3xl font-black text-purple-600">${d.seven_valid}</p>
                </div>
            `;
        }

        // Render grafik tren
        function renderChart(data) {
            const ctx = document.getElementById('trendsChart').getContext('2d');
            if (trendsChart) trendsChart.destroy();

            trendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Siswa Hadir', data: data.siswa, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.3 },
                        { label: 'Guru Hadir', data: data.guru, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', tension: 0.3 },
                        { label: 'Jurnal', data: data.jurnal, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', tension: 0.3 },
                        { label: '7 Kebiasaan', data: data.seven, borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.1)', tension: 0.3 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        // Load aktivitas terbaru
        async function loadActivities() {
            try {
                const res = await fetch('/api/dinas_activities.php', { credentials: 'include' });
                const data = await res.json();
                if (data.ok) {
                    const container = document.getElementById('activities');
                    container.innerHTML = data.data.map(a => `
                        <div class="flex items-center gap-3 p-2 bg-slate-50 rounded-lg border border-slate-100">
                            <span class="w-2 h-2 rounded-full ${a.warna}"></span>
                            <span class="text-sm text-slate-700 flex-1">${escapeHtml(a.pesan)}</span>
                            <span class="text-xs text-slate-400">${a.waktu}</span>
                        </div>
                    `).join('');
                }
            } catch (e) {
                console.error('Gagal load activities:', e);
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

        // Auto-refresh activities setiap 10 detik
        setInterval(loadActivities, 10000);

        // Inisialisasi
        document.addEventListener('DOMContentLoaded', () => {
            loadKecamatan();
            loadData();
            loadActivities();
        });
    </script>
</body>
</html>