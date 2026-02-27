<?php
/**
 * config/database.php
 *
 * ✅ Aman untuk dibagikan (tidak menyimpan password di repo).
 *
 * Cara pakai di hosting:
 * 1) Copy "config/database.local.php.example" menjadi "config/database.local.php"
 * 2) Isi credential DB kamu di file local tersebut.
 *
 * Alternatif: set ENV vars (EDUGATE_DB_HOST/EDUGATE_DB_NAME/EDUGATE_DB_USER/EDUGATE_DB_PASS)
 * atau (DB_HOST/DB_NAME/DB_USER/DB_PASS).
 */

declare(strict_types=1);

// 1) Prioritas: file local (tidak ikut di-share)
$local = __DIR__ . '/database.local.php';
if (is_file($local)) {
  /** @noinspection PhpIncludeInspection */
  require $local;
}

// 2) Fallback: ENV vars (dua gaya penamaan, supaya fleksibel)
$db_host = $db_host ?? (getenv('EDUGATE_DB_HOST') ?: (getenv('DB_HOST') ?: 'localhost'));
$db_port = $db_port ?? (getenv('EDUGATE_DB_PORT') ?: (getenv('DB_PORT') ?: ''));
$db_name = $db_name ?? (getenv('EDUGATE_DB_NAME') ?: (getenv('DB_NAME') ?: ''));
$db_user = $db_user ?? (getenv('EDUGATE_DB_USER') ?: (getenv('DB_USER') ?: ''));
$db_pass = $db_pass ?? (getenv('EDUGATE_DB_PASS') ?: (getenv('DB_PASS') ?: ''));

if ($db_name === '' || $db_user === '') {
  // Jangan echo credential apapun.
  throw new RuntimeException(
    'Konfigurasi database belum diisi. Buat file config/database.local.php (lihat database.local.php.example) '
    . 'atau set ENV vars DB_HOST/DB_NAME/DB_USER/DB_PASS.'
  );
}

$portPart = ($db_port !== '') ? ";port={$db_port}" : "";
$dsn = "mysql:host={$db_host}{$portPart};dbname={$db_name};charset=utf8mb4";

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $db_user, $db_pass, $options);
