<?php
/**
 * config/tenant.php
 * Resolve tenant (sekolah) dari subdomain dan simpan ke session.
 * Wajib dipanggil sebelum akses data agar school_id selalu tersedia.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/database.php';

function edugate_get_subdomain(string $host): ?string {
  $host = strtolower(trim($host));
  // buang port
  $host = preg_replace('/:\\d+$/', '', $host);
  if ($host === 'localhost' || $host === '127.0.0.1') return 'app';

  $parts = explode('.', $host);
  if (count($parts) < 3) return null; // tidak ada subdomain
  $sub = $parts[0];
  if ($sub === 'www') return null;
  return $sub ?: null;
}

function edugate_tenant_fail(bool $as_json, string $msg = 'Sekolah belum terdaftar / nonaktif'): void {
  http_response_code(404);
  if ($as_json) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>$msg]);
  } else {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!doctype html><html><head><meta charset='utf-8'><title>Tenant Not Found</title></head><body style='font-family:system-ui;padding:24px'><h2>{$msg}</h2><p>Hubungi admin EduGate untuk aktivasi subdomain sekolah.</p></body></html>";
  }
  exit;
}

function edugate_ensure_schools_table(PDO $pdo): void {
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
}

// jika sudah ada di session, skip (tapi tetap aman bila tabel belum ada)
if (!isset($_SESSION['school_id']) || !isset($_SESSION['school'])) {
  $host = $_SERVER['HTTP_HOST'] ?? '';
  $sub = edugate_get_subdomain($host) ?? 'app';

  // deteksi request api untuk output json
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  $is_api = str_starts_with($uri, '/api/');

  try {
    edugate_ensure_schools_table($pdo);

    $st = $pdo->prepare("SELECT * FROM schools WHERE subdomain=? AND status='active' LIMIT 1");
    $st->execute([$sub]);
    $school = $st->fetch(PDO::FETCH_ASSOC);

    if (!$school) {
      edugate_tenant_fail($is_api);
    }

    $_SESSION['school_id'] = (int)$school['id'];
    $_SESSION['school'] = [
      'id' => (int)$school['id'],
      'nama' => (string)$school['nama_sekolah'],
      'jenjang' => (string)$school['jenjang'],
      'subdomain' => (string)$school['subdomain'],
      'timezone' => (string)($school['timezone'] ?? 'Asia/Jakarta'),
      'settings' => json_decode($school['settings_json'] ?? '{}', true) ?: []
    ];
  } catch (Throwable $e) {
    edugate_tenant_fail($is_api, 'Tenant error: ' . $e->getMessage());
  }
}
