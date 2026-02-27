# EduGate SaaS – Sprint 1 (Multi Sekolah + Ready-to-Sell basics)

Tanggal: 2026-02-24

## Added
- Multi-tenant resolver `config/tenant.php` (subdomain -> `schools` -> `$_SESSION['school_id']` + `$_SESSION['school']`).
- Tabel `schools` + seed default tenant `app` (best effort via schema).
- API bootstrap `api/_bootstrap.php` (helper JSON + schema + tenant).
- Admin approval workflow izin/cuti guru:
  - `api/absensi_guru_ket_admin_list.php` (list pengajuan)
  - `api/absensi_guru_ket_admin_action.php` (approve/reject + catatan)
  - Page: `admin_persetujuan_izin_guru.php` (+ wrapper `app/admin_persetujuan_izin_guru.php`)
- Export CSV (minimal siap jual):
  - `api/export_absensi_siswa_csv.php`
  - `api/export_absensi_guru_csv.php`
  - `api/export_jurnal_guru_csv.php`
- Super Admin portal (tenant onboarding cepat):
  - `api/super_school_list.php`
  - `api/super_school_create.php` (buat sekolah + admin sekolah pertama)
  - Page: `super_admin_schools.php` (+ wrapper `app/super_admin_schools.php`)

## Changed
- `auth/guard.php`: sekarang selalu resolve tenant (subdomain) sebelum cek role.
- `auth/login_process.php`: login tenant-safe (filter `school_id`) + `session_regenerate_id(true)`.
- `index.php`: tampilkan nama sekolah di halaman login (tenant-aware).
- `api/_schema.php`: upgrade besar untuk SaaS:
  - tambah kolom `school_id` di tabel operasional (absensi, jurnal, 7hebat, tugas, ket, dll) + index.
  - perbaiki unique key agar include `school_id` (best effort).
  - tambah tabel `pengaturan_sekolah`, `jam_operasional`, `feedback_kbm` (tenant-safe).
- Tenant-safe patch untuk modul:
  - Tugas tambahan (`tugas_*`): filter `school_id` + perbaikan endpoint yang sebelumnya require `_bootstrap.php` tapi file belum ada.
  - Jurnal (`jurnal_*`): filter `school_id`, rapikan `jurnal_guru_save.php` (hapus duplikasi file), upload path per sekolah.
  - Settings (`settings_public/get/save`): sekarang per sekolah (school_id) termasuk jadwal.
  - Kiosk absen (`kiosk_absen.php`): tenant-safe + absensi_log tenant-safe.
  - Users (`users_list/save/delete/bulk_upsert`): tenant-safe.
  - 7Hebat summary/list kelas: tenant-safe.
  - Kelas distinct, guru_list, siswa_by_kelas: tenant-safe.
  - `absensi_guru_ket_submit/status`: tenant-safe + upload bukti per sekolah.

## Upload path (tenant-safe)
- Bukti izin/cuti guru: `/uploads/{school_id}/bukti_guru/{tanggal}/...`
- Foto jurnal guru: `/uploads/{school_id}/jurnal_guru/{tanggal}/...`

## Notes
- Beberapa endpoint presensi lama kemungkinan masih perlu penyisiran ekstra untuk memastikan semua query sudah filter `school_id` (terutama endpoint yang jarang dipakai). Pola aman: selalu ambil `$sid = school_id();` dan tambahkan `WHERE school_id=?`.
