<?php
// mapel_list.php
require __DIR__ . "/../auth/guard.php";
require_login(['super','admin','bk','kesiswaan','kurikulum','guru','staff','dinas']);

require __DIR__ . "/../config/database.php";
header('Content-Type: application/json; charset=utf-8');

try {
  $sid = school_id(); // [FIX] Ambil school_id
  $stmt = $pdo->prepare("SELECT id, nama_mapel FROM mapel WHERE school_id = ? ORDER BY nama_mapel ASC");
  $stmt->execute([$sid]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(["ok"=>true, "data"=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>$e->getMessage()]);
}