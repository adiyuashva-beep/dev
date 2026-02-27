<?php
require __DIR__ . "/../auth/guard.php";
require_login(['siswa']);

require __DIR__ . "/../config/database.php";
header('Content-Type: application/json; charset=utf-8');

$user = $_SESSION['user'] ?? null;
if(!$user){ echo json_encode(["ok"=>false,"error"=>"Belum login"]); exit; }

$username = (string)($user["username"] ?? "");
$sid = school_id(); // [FIX] Ambil school_id

$sql = "SELECT t.nama_ekstra, t.kategori, t.nama_guru
        FROM kelas_bimbingan_anggota a
        JOIN kelas_bimbingan t ON t.id = a.tim_id
        WHERE a.username = :u 
          AND t.school_id = :sid   -- filter school_id di tabel bimbingan
          AND a.school_id = :sid    -- filter school_id di tabel anggota
        ORDER BY t.nama_ekstra ASC";
$st = $pdo->prepare($sql);
$st->execute([":u"=>$username, ":sid"=>$sid]);

echo json_encode(["ok"=>true,"data"=>$st->fetchAll(PDO::FETCH_ASSOC)]);