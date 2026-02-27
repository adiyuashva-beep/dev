# EduGate (Dev V9) — versi diperbaiki

Repo/zip ini adalah aplikasi **PHP + MySQL** untuk manajemen sekolah (multi-tenant via `schools.subdomain`). Saya merapikan bagian yang masih “setengah jadi” supaya **bisa di-install dari nol** dan modul inti bisa jalan.

## Yang sudah diperbaiki (ringkas)

1. **Schema DB dilengkapi**
   - Menambahkan tabel **`users`** (akun login) yang sebelumnya belum dibuat oleh `_schema.php`.
   - Menambahkan tabel **`ortu_anak`** (relasi ortu–anak) yang diperlukan modul orang tua.
   - Menambahkan kolom wilayah pada tabel **`schools`** (`provinsi/kabupaten/kecamatan/kelurahan/kode_pos/alamat/npsn`) agar halaman *Profil Sekolah* + modul *Dinas* tidak error.

2. **Siswa module (siswa.php) dibuat tenant-safe**
   - Query settings & absensi sekarang memakai **`school_id`**.
   - Upload bukti foto absen siswa disimpan rapi per sekolah: `/uploads/{school_id}/absen/{Y-m-d}/...`.
   - Ganti password di `siswa.php` sekarang update **`password_hash`** (bukan plaintext), dan filter by `school_id`.

3. **Endpoint legacy diperbaiki**
   - `api/school_settings.php` dan `api/absensi_action.php` sebelumnya membaca tabel `pengaturan` (yang tidak ada di schema). Sekarang membaca **`pengaturan_sekolah` + `jam_operasional`**.

4. **Keamanan dasar & kebersihan project**
   - Menghapus file `config/database.local.php` (yang berisi credential). Diganti dengan `config/database.local.php.example`.
   - Menambah proteksi `config/.htaccess` agar folder config tidak bisa diakses langsung (Apache).
   - Menambah `uploads/.htaccess` untuk mencegah eksekusi file `.php` di folder upload.
   - Menghapus file debug yang tidak aman/ tidak perlu (`hash.php`, `hash_dinas.php`, `test_db.php`, `api/tester.php`).

5. **Setup lebih jelas**
   - `setup.php` sekarang:
     - memastikan schema,
     - bisa membuat **user awal** (super/admin/dinas/dll),
     - aman: **CLI always allowed**, browser perlu token dari `config/setup.local.php`.

---

## Kebutuhan

- PHP 8.1+ (disarankan 8.2+)
- MySQL 5.7/8.0
- Apache + `mod_rewrite` (jika ingin URL rewrite dari `.htaccess`)

## Instalasi cepat

### 1) Konfigurasi database

1. Copy:
   - `config/database.local.php.example` → `config/database.local.php`
2. Isi:
   - `$db_host`, `$db_port` (opsional)
   - `$db_name`, `$db_user`, `$db_pass`

Alternatif: set environment variables:
- `EDUGATE_DB_HOST`, `EDUGATE_DB_PORT`, `EDUGATE_DB_NAME`, `EDUGATE_DB_USER`, `EDUGATE_DB_PASS`

### 2) Buat tabel (schema)

Paling aman via CLI:

```bash
php setup.php
```

### 3) Buat user awal (super/admin)

#### Opsi A — CLI (disarankan)

```bash
php setup.php --create-user --role=super --username=super --password=PASSWORD_KAMU --name="Super Admin"
```

> Default `school_id` = 1 (tenant default `app`).

#### Opsi B — Browser (token-protected)

1. Copy:
   - `config/setup.local.php.example` → `config/setup.local.php`
2. Ganti token di file tersebut.
3. Akses:
   - `/setup.php?token=TOKEN_KAMU`

Di halaman itu kamu bisa lihat daftar `schools` dan membuat user.

### 4) Login

- Buka halaman root: `/index.php`
- Login menggunakan username/password yang dibuat.

---

## Multi-tenant (ringkas)

- Tenant ditentukan dari **subdomain** (`schools.subdomain`).
- Seed default otomatis dibuat:
  - `subdomain = app`
- Untuk pemakaian sederhana (1 sekolah), akses domain utama tanpa subdomain biasanya akan otomatis jatuh ke tenant `app`.

---

## Catatan deployment

- Pastikan folder `uploads/` bisa ditulis oleh web server.
- Setelah instalasi selesai:
  - sebaiknya **hapus** `setup.php` atau set token yang kuat & simpan dengan aman.

---

## Troubleshooting

- **Error: Konfigurasi database belum diisi**
  - Pastikan `config/database.local.php` sudah ada, atau ENV vars sudah diset.

- **Tidak bisa login (user tidak ditemukan)**
  - Pastikan sudah menjalankan `setup.php` dan membuat user awal.

- **Fitur Dinas / Profil sekolah error kolom tidak ada**
  - Jalankan `php setup.php` lagi setelah update; schema helper akan menambahkan kolom.

---

## Opsi dev cepat pakai Docker (opsional)

Jika kamu ingin coba cepat tanpa setup manual PHP/Apache/MySQL:

1. Pastikan Docker & Docker Compose terpasang.
2. Jalankan:

```bash
docker compose up -d
```

3. Buka:
- `http://localhost:8080/`

4. Inisialisasi schema dan buat user awal (CLI di container web):

```bash
docker compose exec web php setup.php
docker compose exec web php setup.php --create-user --role=super --username=super --password=PASSWORD_KAMU --name="Super Admin"
```

