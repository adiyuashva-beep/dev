<?php
/**
 * Schema helper untuk EduGate v5/v6
 * Aman dipanggil berkali-kali (best effort).
 */

declare(strict_types=1);

function edugate_v5_column_exists(PDO $pdo, string $table, string $column): bool {
  $st = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
  $st->execute([$table, $column]);
  return ((int)$st->fetchColumn()) > 0;
}

function edugate_v5_index_exists(PDO $pdo, string $table, string $index): bool {
  $st = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
  $st->execute([$table, $index]);
  return ((int)$st->fetchColumn()) > 0;
}

function edugate_v5_drop_index(PDO $pdo, string $table, string $index): void {
  try {
    if (edugate_v5_index_exists($pdo, $table, $index)) {
      $pdo->exec("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
    }
  } catch (Throwable $e) {
    // best effort
  }
}

function edugate_v5_add_index(PDO $pdo, string $table, string $index, string $cols, bool $unique=false): void {
  try {
    if (!edugate_v5_index_exists($pdo, $table, $index)) {
      $uniq = $unique ? 'UNIQUE' : 'INDEX';
      $pdo->exec("ALTER TABLE `{$table}` ADD {$uniq} `{$index}` ({$cols})");
    }
  } catch (Throwable $e) {
    // best effort
  }
}

function edugate_v5_ensure_column(PDO $pdo, string $table, string $column, string $ddl): void {
  try {
    if (!edugate_v5_column_exists($pdo, $table, $column)) {
      $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN {$ddl}");
    }
  } catch (Throwable $e) {
    // best effort
  }
}

function edugate_v5_ensure_tables(PDO $pdo): void {

  // =========================
  // SCHOOLS (TENANT)
  // =========================
  $pdo->exec("CREATE TABLE IF NOT EXISTS schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_sekolah VARCHAR(150) NOT NULL,
    jenjang ENUM('SD','SMP','SMA') NOT NULL,
    subdomain VARCHAR(80) NOT NULL UNIQUE,
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Jakarta',
    settings_json TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  // seed minimal agar instalasi single-sekolah tetap jalan
  try {
    $cnt = (int)($pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn() ?? 0);
    if ($cnt === 0) {
      $pdo->exec("INSERT INTO schools (nama_sekolah, jenjang, subdomain, status, settings_json)
        VALUES ('Sekolah Default','SMA','app','active','{\"max_jp\":12,\"fitur_jurnal\":true,\"fitur_7hebat\":true,\"fitur_cuti_guru\":true,\"fitur_gps\":true}')");
    }
  } catch (Throwable $e) {
    // best effort
  }

// Kolom tambahan untuk fitur Profil Sekolah / Dinas (filter wilayah)
edugate_v5_ensure_column($pdo, 'schools', 'npsn', 'npsn VARCHAR(20) NULL');
edugate_v5_ensure_column($pdo, 'schools', 'provinsi', 'provinsi VARCHAR(120) NULL');
edugate_v5_ensure_column($pdo, 'schools', 'kabupaten', 'kabupaten VARCHAR(120) NULL');
edugate_v5_ensure_column($pdo, 'schools', 'kecamatan', 'kecamatan VARCHAR(120) NULL');
edugate_v5_ensure_column($pdo, 'schools', 'kelurahan', 'kelurahan VARCHAR(120) NULL');
edugate_v5_ensure_column($pdo, 'schools', 'kode_pos', 'kode_pos VARCHAR(12) NULL');
edugate_v5_ensure_column($pdo, 'schools', 'alamat', 'alamat TEXT NULL');

// Index ringan untuk query dinas (best effort)
edugate_v5_add_index($pdo, 'schools', 'idx_schools_jenjang', 'jenjang');
edugate_v5_add_index($pdo, 'schools', 'idx_schools_kecamatan', 'kecamatan');


  // =========================
  // ABSENSI GURU
  // =========================
  $pdo->exec("CREATE TABLE IF NOT EXISTS absensi_guru (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    tanggal DATE NOT NULL,
    username VARCHAR(64) NOT NULL,
    nama VARCHAR(120) NOT NULL,
    status_terakhir VARCHAR(20) DEFAULT NULL,
    jam_masuk VARCHAR(32) DEFAULT NULL,
    jam_pulang VARCHAR(32) DEFAULT NULL,
    lokasi_masuk TEXT DEFAULT NULL,
    lokasi_pulang TEXT DEFAULT NULL,
    foto_masuk VARCHAR(255) DEFAULT NULL,
    foto_pulang VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_absen_guru (tanggal, username)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'absensi_guru', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_drop_index($pdo, 'absensi_guru', 'uniq_absen_guru');
  edugate_v5_add_index($pdo, 'absensi_guru', 'uniq_absen_guru_school', 'school_id, tanggal, username', true);
  edugate_v5_add_index($pdo, 'absensi_guru', 'idx_absen_guru_school', 'school_id');

  edugate_v5_ensure_column($pdo, 'absensi_guru', 'lokasi_masuk', 'lokasi_masuk TEXT DEFAULT NULL');
  edugate_v5_ensure_column($pdo, 'absensi_guru', 'lokasi_pulang', 'lokasi_pulang TEXT DEFAULT NULL');
  edugate_v5_ensure_column($pdo, 'absensi_guru', 'foto_masuk', 'foto_masuk VARCHAR(255) DEFAULT NULL');
  edugate_v5_ensure_column($pdo, 'absensi_guru', 'foto_pulang', 'foto_pulang VARCHAR(255) DEFAULT NULL');

  $pdo->exec("CREATE TABLE IF NOT EXISTS absensi_guru_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    tanggal DATE NOT NULL,
    username VARCHAR(64) NOT NULL,
    waktu DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL,
    ket TEXT DEFAULT NULL,
    lat DECIMAL(10,7) DEFAULT NULL,
    lng DECIMAL(10,7) DEFAULT NULL,
    akurasi FLOAT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_absen_guru_log_user (username),
    KEY idx_absen_guru_log_tgl (tanggal)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'absensi_guru_log', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_add_index($pdo, 'absensi_guru_log', 'idx_absen_guru_log_school', 'school_id');

  // =========================
  // KETERANGAN ABSENSI GURU (IZIN/DINAS/CUTI/SAKIT)
  // =========================
  $pdo->exec("CREATE TABLE IF NOT EXISTS absensi_guru_ket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    tanggal DATE NOT NULL,
    username VARCHAR(64) NOT NULL,
    nama VARCHAR(120) NOT NULL,
    jenis VARCHAR(40) NOT NULL,
    jumlah_hari INT DEFAULT NULL,
    keterangan TEXT DEFAULT NULL,
    bukti_url VARCHAR(255) DEFAULT NULL,
    status VARCHAR(12) NOT NULL DEFAULT 'submitted',
    validator_username VARCHAR(64) DEFAULT NULL,
    validator_name VARCHAR(120) DEFAULT NULL,
    validator_note TEXT DEFAULT NULL,
    validated_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ket (tanggal, username),
    KEY idx_ket_user (username),
    KEY idx_ket_tgl (tanggal)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'absensi_guru_ket', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_drop_index($pdo, 'absensi_guru_ket', 'uniq_ket');
  edugate_v5_add_index($pdo, 'absensi_guru_ket', 'uniq_ket_school', 'school_id, tanggal, username', true);
  edugate_v5_add_index($pdo, 'absensi_guru_ket', 'idx_ket_school', 'school_id');

  // saldo cuti tahunan (default PNS: 12 hari / tahun)
  $pdo->exec("CREATE TABLE IF NOT EXISTS guru_cuti_saldo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    tahun INT NOT NULL,
    username VARCHAR(64) NOT NULL,
    sisa_hari INT NOT NULL DEFAULT 12,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_saldo (tahun, username)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'guru_cuti_saldo', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_drop_index($pdo, 'guru_cuti_saldo', 'uniq_saldo');
  edugate_v5_add_index($pdo, 'guru_cuti_saldo', 'uniq_saldo_school', 'school_id, tahun, username', true);
  edugate_v5_add_index($pdo, 'guru_cuti_saldo', 'idx_saldo_school', 'school_id');

  // =========================
  // JURNAL GURU (JP)
  // =========================
  $pdo->exec("CREATE TABLE IF NOT EXISTS jurnal_guru (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    tanggal DATE NOT NULL,
    jam TIME DEFAULT NULL,
    guru_username VARCHAR(64) NOT NULL,
    guru_nama VARCHAR(120) NOT NULL,
    kelas VARCHAR(64) NOT NULL,
    mapel VARCHAR(80) NOT NULL,
    topik VARCHAR(255) NOT NULL,
    catatan TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_jurnal_guru_user (guru_username),
    KEY idx_jurnal_guru_tgl (tanggal),
    KEY idx_jurnal_guru_kelas (kelas)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'jurnal_guru', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_add_index($pdo, 'jurnal_guru', 'idx_jurnal_school', 'school_id');

  // kolom tambahan: jam range (JP) + bukti foto jurnal
  edugate_v5_ensure_column($pdo, 'jurnal_guru', 'jam_ke_mulai', 'jam_ke_mulai INT DEFAULT NULL');
  edugate_v5_ensure_column($pdo, 'jurnal_guru', 'jam_ke_selesai', 'jam_ke_selesai INT DEFAULT NULL');
  edugate_v5_ensure_column($pdo, 'jurnal_guru', 'foto_json', 'foto_json TEXT DEFAULT NULL');

  // =========================
  // FEEDBACK KBM (SISWA -> JURNAL)
  // =========================
  $pdo->exec("CREATE TABLE IF NOT EXISTS feedback_kbm (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    jurnal_id INT NOT NULL,
    username VARCHAR(64) NOT NULL,
    nama VARCHAR(120) NOT NULL,
    emosi VARCHAR(40) NOT NULL,
    pesan TEXT DEFAULT NULL,
    tanggal DATE NOT NULL,
    waktu DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_feedback (jurnal_id, username)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'feedback_kbm', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_drop_index($pdo, 'feedback_kbm', 'uniq_feedback');
  edugate_v5_add_index($pdo, 'feedback_kbm', 'uniq_feedback_school', 'school_id, jurnal_id, username', true);
  edugate_v5_add_index($pdo, 'feedback_kbm', 'idx_feedback_school', 'school_id');

  // =========================
  // TUGAS TAMBAHAN GURU
  // =========================
  $pdo->exec("CREATE TABLE IF NOT EXISTS guru_tugas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    guru_username VARCHAR(64) NOT NULL,
    guru_nama VARCHAR(120) NOT NULL,
    jenis VARCHAR(40) NOT NULL,
    nama_tugas VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_guru_tugas_guru (guru_username)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'guru_tugas', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_add_index($pdo, 'guru_tugas', 'idx_guru_tugas_school', 'school_id');

  $pdo->exec("CREATE TABLE IF NOT EXISTS guru_tugas_anggota (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    tugas_id INT NOT NULL,
    siswa_username VARCHAR(64) NOT NULL,
    siswa_nama VARCHAR(120) NOT NULL,
    kelas VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tugas_siswa (tugas_id, siswa_username),
    KEY idx_tugas_id (tugas_id),
    CONSTRAINT fk_tugas FOREIGN KEY (tugas_id) REFERENCES guru_tugas(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'guru_tugas_anggota', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_drop_index($pdo, 'guru_tugas_anggota', 'uniq_tugas_siswa');
  edugate_v5_add_index($pdo, 'guru_tugas_anggota', 'uniq_tugas_siswa_school', 'school_id, tugas_id, siswa_username', true);
  edugate_v5_add_index($pdo, 'guru_tugas_anggota', 'idx_tugas_anggota_school', 'school_id');

  // =========================
  // 7 KEBIASAAN
  // =========================
  $pdo->exec("CREATE TABLE IF NOT EXISTS kebiasaan7 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    tanggal DATE NOT NULL,
    siswa_username VARCHAR(64) NOT NULL,
    siswa_nama VARCHAR(120) NOT NULL,
    kelas VARCHAR(64) DEFAULT NULL,
    data_json LONGTEXT NOT NULL,
    catatan TEXT DEFAULT NULL,
    status VARCHAR(10) NOT NULL DEFAULT 'draft',
    validator_username VARCHAR(64) DEFAULT NULL,
    validator_name VARCHAR(120) DEFAULT NULL,
    validator_note TEXT DEFAULT NULL,
    validated_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_k7 (tanggal, siswa_username),
    KEY idx_k7_kelas (kelas),
    KEY idx_k7_tgl (tanggal)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'kebiasaan7', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_drop_index($pdo, 'kebiasaan7', 'uniq_k7');
  edugate_v5_add_index($pdo, 'kebiasaan7', 'uniq_k7_school', 'school_id, tanggal, siswa_username', true);
  edugate_v5_add_index($pdo, 'kebiasaan7', 'idx_k7_school', 'school_id');

  // =========================
  // ABSENSI SISWA
  // =========================
  $pdo->exec("CREATE TABLE IF NOT EXISTS absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    tanggal DATE NOT NULL,
    username VARCHAR(64) NOT NULL,
    nama VARCHAR(120) NOT NULL,
    kelas VARCHAR(64) DEFAULT NULL,
    status_terakhir VARCHAR(10) DEFAULT NULL,
    jam_masuk VARCHAR(32) DEFAULT NULL,
    jam_pulang VARCHAR(32) DEFAULT NULL,
    foto_masuk VARCHAR(255) DEFAULT NULL,
    foto_pulang VARCHAR(255) DEFAULT NULL,
    lokasi_masuk TEXT DEFAULT NULL,
    lokasi_pulang TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_absen (tanggal, username)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'absensi', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_drop_index($pdo, 'absensi', 'uniq_absen');
  edugate_v5_add_index($pdo, 'absensi', 'uniq_absen_school', 'school_id, tanggal, username', true);
  edugate_v5_add_index($pdo, 'absensi', 'idx_absen_school', 'school_id');

  edugate_v5_ensure_column($pdo, 'absensi', 'foto_masuk', 'foto_masuk VARCHAR(255) DEFAULT NULL');
  edugate_v5_ensure_column($pdo, 'absensi', 'foto_pulang', 'foto_pulang VARCHAR(255) DEFAULT NULL');
  edugate_v5_ensure_column($pdo, 'absensi', 'lokasi_masuk', 'lokasi_masuk TEXT DEFAULT NULL');
  edugate_v5_ensure_column($pdo, 'absensi', 'lokasi_pulang', 'lokasi_pulang TEXT DEFAULT NULL');

  $pdo->exec("CREATE TABLE IF NOT EXISTS absensi_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    tanggal DATE NOT NULL,
    username VARCHAR(64) NOT NULL,
    waktu DATETIME NOT NULL,
    status VARCHAR(10) NOT NULL,
    ket TEXT DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_absen_log_user (username),
    KEY idx_absen_log_tgl (tanggal)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'absensi_log', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_add_index($pdo, 'absensi_log', 'idx_absen_log_school', 'school_id');

  // =========================
  // MASTER MAPEL
  // =========================
  $pdo->exec("CREATE TABLE IF NOT EXISTS mapel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    nama_mapel VARCHAR(100) NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'mapel', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_drop_index($pdo, 'mapel', 'nama_mapel');
  edugate_v5_add_index($pdo, 'mapel', 'uniq_mapel_school', 'school_id, nama_mapel', true);
  edugate_v5_add_index($pdo, 'mapel', 'idx_mapel_school', 'school_id');

// =========================
// USERS (AKUN LOGIN)
// =========================
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NOT NULL DEFAULT 1,
  name VARCHAR(160) NOT NULL,
  username VARCHAR(64) NOT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  password VARCHAR(255) DEFAULT NULL,
  role VARCHAR(32) NOT NULL DEFAULT 'siswa',
  kelas VARCHAR(80) DEFAULT NULL,
  foto_profil VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_users_school_username (school_id, username),
  KEY idx_users_role (role),
  KEY idx_users_kelas (kelas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Legacy compatibility: pastikan kolom-kolom penting ada (best effort)
edugate_v5_ensure_column($pdo, 'users', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
edugate_v5_ensure_column($pdo, 'users', 'name', "name VARCHAR(160) NOT NULL DEFAULT '-'" );
edugate_v5_ensure_column($pdo, 'users', 'username', 'username VARCHAR(64) NOT NULL');
edugate_v5_ensure_column($pdo, 'users', 'password_hash', 'password_hash VARCHAR(255) DEFAULT NULL');
edugate_v5_ensure_column($pdo, 'users', 'password', 'password VARCHAR(255) DEFAULT NULL');
edugate_v5_ensure_column($pdo, 'users', 'role', "role VARCHAR(32) NOT NULL DEFAULT 'siswa'" );
edugate_v5_ensure_column($pdo, 'users', 'kelas', 'kelas VARCHAR(80) DEFAULT NULL');
edugate_v5_ensure_column($pdo, 'users', 'foto_profil', 'foto_profil VARCHAR(255) DEFAULT NULL');
edugate_v5_ensure_column($pdo, 'users', 'created_at', 'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
edugate_v5_ensure_column($pdo, 'users', 'updated_at', 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

// Multi-tenant: allow username yang sama di sekolah berbeda
edugate_v5_drop_index($pdo, 'users', 'username');
edugate_v5_drop_index($pdo, 'users', 'uniq_username');
edugate_v5_drop_index($pdo, 'users', 'uniq_users_username');
edugate_v5_add_index($pdo, 'users', 'uniq_users_school_username', 'school_id, username', true);
edugate_v5_add_index($pdo, 'users', 'idx_users_school', 'school_id');

// =========================
// ORTU (RELASI ORTU <-> ANAK)
// =========================
$pdo->exec("CREATE TABLE IF NOT EXISTS ortu_anak (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NOT NULL DEFAULT 1,
  ortu_username VARCHAR(64) NOT NULL,
  siswa_username VARCHAR(64) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_ortu_anak (school_id, ortu_username, siswa_username),
  KEY idx_ortu_username (ortu_username),
  KEY idx_siswa_username (siswa_username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

edugate_v5_ensure_column($pdo, 'ortu_anak', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
edugate_v5_add_index($pdo, 'ortu_anak', 'idx_ortu_anak_school', 'school_id');

  // =========================
  // PENGATURAN SEKOLAH & JAM OPERASIONAL
  // =========================
  $pdo->exec("CREATE TABLE IF NOT EXISTS pengaturan_sekolah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    mode_bebas_pulang TINYINT NOT NULL DEFAULT 0,
    pesan_bebas_pulang TEXT DEFAULT NULL,
    mode_gps TINYINT NOT NULL DEFAULT 1,
    radius_m INT NOT NULL DEFAULT 50,
    lokasi_lat DECIMAL(10,7) DEFAULT NULL,
    lokasi_lng DECIMAL(10,7) DEFAULT NULL,
    akses_siswa TINYINT NOT NULL DEFAULT 1,
    akses_guru TINYINT NOT NULL DEFAULT 1,
    akses_ortu TINYINT NOT NULL DEFAULT 0,
    akses_pejabat TINYINT NOT NULL DEFAULT 1,
    refleksi_ortu TINYINT NOT NULL DEFAULT 0,
    refleksi_guru TINYINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_pengaturan_school (school_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $pdo->exec("CREATE TABLE IF NOT EXISTS jam_operasional (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL DEFAULT 1,
    hari VARCHAR(12) NOT NULL,
    masuk TIME DEFAULT NULL,
    telat TIME DEFAULT NULL,
    pulang TIME DEFAULT NULL,
    is_libur TINYINT NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_jam_school_hari (school_id, hari)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  edugate_v5_ensure_column($pdo, 'pengaturan_sekolah', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_ensure_column($pdo, 'jam_operasional', 'school_id', 'school_id INT NOT NULL DEFAULT 1');
  edugate_v5_add_index($pdo, 'pengaturan_sekolah', 'idx_pengaturan_school', 'school_id');
  edugate_v5_add_index($pdo, 'jam_operasional', 'idx_jam_school', 'school_id');
}