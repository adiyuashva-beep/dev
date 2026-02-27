<?php
// /api/users_list.php
require __DIR__ . "/../auth/guard.php";
require_login(['admin','super','bk','kesiswaan','kurikulum','dinas']);

require __DIR__ . "/../config/database.php"; // PDO: $pdo
require __DIR__ . "/_schema.php";

header('Content-Type: application/json; charset=utf-8');

try {
  edugate_v5_ensure_tables($pdo);
  $sid = (int)($_SESSION['school_id'] ?? 0);
  if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');

  // Auto-normalize: jika role kosong/NULL/'-' tapi punya kelas -> anggap siswa
  // Ini juga sekalian memperbaiki data lama di DB.
  $stNorm = $pdo->prepare("UPDATE users SET role='siswa'
             WHERE school_id=:sid
               AND (role IS NULL OR TRIM(role)='' OR role='-' OR LOWER(TRIM(role))='null')
               AND kelas IS NOT NULL AND TRIM(kelas)<>'' AND kelas<>'-'");
  $stNorm->execute([':sid'=>$sid]);

  $sql = "SELECT id, name AS nama, username, role, kelas
          FROM users
          WHERE school_id=:sid
          ORDER BY role ASC, nama ASC
          LIMIT 50000";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':sid'=>$sid]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Safety: normalize output (kalau masih ada role kosong, tampilkan '-')
  foreach ($rows as &$r) {
    $role = trim((string)($r['role'] ?? ''));
    if ($role === '' || $role === '-' || strtolower($role) === 'null') {
      $kelas = trim((string)($r['kelas'] ?? ''));
      $r['role'] = ($kelas !== '' && $kelas !== '-') ? 'siswa' : '-';
    }
    if (!isset($r['nama']) || trim((string)$r['nama']) === '') {
      $r['nama'] = (string)($r['username'] ?? '-');
    }
  }
  unset($r);

  echo json_encode([
    'ok' => true,
    'data' => $rows,
  ]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => $e->getMessage(),
  ]);
}
