<?php
// public_html/api/migrate_import.php
declare(strict_types=1);

require __DIR__ . "/../auth/guard.php";
require_login(['admin','super']);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . "/../config/database.php";

function jexit(array $payload, int $code = 200): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  jexit(['ok' => false, 'error' => 'Method harus POST'], 405);
}

$school = (string)($_POST['school_code'] ?? 'DEFAULT');
$school = strtoupper(trim($school));
$school = preg_replace('/[^A-Z0-9_-]/', '', $school) ?: 'DEFAULT';

if (!isset($_FILES['zipfile'])) {
  jexit(['ok' => false, 'error' => 'File zipfile tidak ditemukan.'], 400);
}

$f = $_FILES['zipfile'];
if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
  $err = (int)($f['error'] ?? -1);
  jexit(['ok' => false, 'error' => 'Upload gagal. code=' . $err], 400);
}

$tmp = (string)$f['tmp_name'];
if ($tmp === '' || !is_file($tmp)) {
  jexit(['ok' => false, 'error' => 'File upload tidak valid.'], 400);
}

$zip = new ZipArchive();
$open = $zip->open($tmp);
if ($open !== true) {
  jexit(['ok' => false, 'error' => 'ZIP tidak bisa dibuka. code=' . $open], 400);
}

$batch = date('Ymd_His') . '_' . bin2hex(random_bytes(4));

function ensure_tables(PDO $pdo): void {
  // penyimpanan mentah
  $pdo->exec("CREATE TABLE IF NOT EXISTS migration_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id VARCHAR(64) NOT NULL UNIQUE,
    school_code VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    note VARCHAR(255) NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $pdo->exec("CREATE TABLE IF NOT EXISTS firebase_store (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    batch_id VARCHAR(64) NOT NULL,
    school_code VARCHAR(64) NOT NULL,
    source_project VARCHAR(120) NOT NULL,
    collection_name VARCHAR(120) NOT NULL,
    doc_id VARCHAR(220) NOT NULL,
    data_json LONGTEXT NOT NULL,
    imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_doc (school_code, source_project, collection_name, doc_id),
    INDEX idx_coll (school_code, collection_name)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  // tabel-tabel “hasil” (buat dipakai nanti saat pindah total)
  $pdo->exec("CREATE TABLE IF NOT EXISTS fb_absensi (
    school_code VARCHAR(64) NOT NULL,
    tanggal DATE NOT NULL,
    nisn VARCHAR(64) NOT NULL,
    nama VARCHAR(160) NULL,
    kelas VARCHAR(80) NULL,
    status_terakhir VARCHAR(40) NULL,
    jam_masuk DATETIME NULL,
    jam_pulang DATETIME NULL,
    foto_masuk TEXT NULL,
    foto_pulang TEXT NULL,
    source_project VARCHAR(120) NOT NULL,
    doc_id VARCHAR(220) NOT NULL,
    data_json LONGTEXT NOT NULL,
    imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (school_code, tanggal, nisn)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $pdo->exec("CREATE TABLE IF NOT EXISTS fb_dispensasi (
    school_code VARCHAR(64) NOT NULL,
    nisn VARCHAR(64) NOT NULL,
    nama VARCHAR(160) NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    alasan TEXT NULL,
    created_at DATETIME NULL,
    source_project VARCHAR(120) NOT NULL,
    doc_id VARCHAR(220) NOT NULL,
    data_json LONGTEXT NOT NULL,
    imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (school_code, doc_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $pdo->exec("CREATE TABLE IF NOT EXISTS fb_hari_libur (
    school_code VARCHAR(64) NOT NULL,
    tanggal DATE NOT NULL,
    keterangan TEXT NULL,
    source_project VARCHAR(120) NOT NULL,
    doc_id VARCHAR(220) NOT NULL,
    data_json LONGTEXT NOT NULL,
    imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (school_code, tanggal)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $pdo->exec("CREATE TABLE IF NOT EXISTS fb_jurnal_guru (
    school_code VARCHAR(64) NOT NULL,
    tanggal DATE NOT NULL,
    nama_guru VARCHAR(160) NULL,
    kelas VARCHAR(80) NULL,
    mapel VARCHAR(120) NULL,
    materi TEXT NULL,
    waktu DATETIME NULL,
    source_project VARCHAR(120) NOT NULL,
    doc_id VARCHAR(220) NOT NULL,
    data_json LONGTEXT NOT NULL,
    imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (school_code, doc_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $pdo->exec("CREATE TABLE IF NOT EXISTS fb_refleksi_siswa (
    school_code VARCHAR(64) NOT NULL,
    tanggal DATE NOT NULL,
    id_jurnal VARCHAR(220) NULL,
    nama VARCHAR(160) NULL,
    rating INT NULL,
    pesan TEXT NULL,
    source_project VARCHAR(120) NOT NULL,
    doc_id VARCHAR(220) NOT NULL,
    data_json LONGTEXT NOT NULL,
    imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (school_code, doc_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function parse_iso_datetime($v): ?string {
  if ($v === null) return null;

  // timestamp object dari exporter
  if (is_array($v) && isset($v['__type']) && (string)$v['__type'] === 'timestamp') {
    $iso = (string)($v['iso'] ?? '');
    if ($iso !== '') $v = $iso;
  }

  if (is_string($v)) {
    $s = trim($v);
    if ($s === '') return null;
    try {
      $dt = new DateTime($s);
      return $dt->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
      return null;
    }
  }
  return null;
}

function parse_date_only($v): ?string {
  if ($v === null) return null;
  if (is_string($v)) {
    $s = trim($v);
    if ($s === '') return null;
    // sudah format yyyy-mm-dd
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;
    try {
      $dt = new DateTime($s);
      return $dt->format('Y-m-d');
    } catch (Throwable $e) {
      return null;
    }
  }
  if (is_array($v) && isset($v['__type']) && (string)$v['__type'] === 'timestamp') {
    $iso = (string)($v['iso'] ?? '');
    if ($iso !== '') return parse_date_only($iso);
  }
  return null;
}

try {
  ensure_tables($pdo);

  $pdo->beginTransaction();
  $insBatch = $pdo->prepare("INSERT INTO migration_batches (batch_id, school_code, note) VALUES (:b,:s,:n)");
  $insBatch->execute([':b' => $batch, ':s' => $school, ':n' => 'import zip']);

  $upStore = $pdo->prepare("INSERT INTO firebase_store (batch_id, school_code, source_project, collection_name, doc_id, data_json)
    VALUES (:b,:s,:p,:c,:d,:j)
    ON DUPLICATE KEY UPDATE batch_id=VALUES(batch_id), data_json=VALUES(data_json), imported_at=NOW()" );

  $upAbs = $pdo->prepare("INSERT INTO fb_absensi (school_code, tanggal, nisn, nama, kelas, status_terakhir, jam_masuk, jam_pulang, foto_masuk, foto_pulang, source_project, doc_id, data_json)
    VALUES (:s,:t,:n,:nm,:k,:st,:jm,:jp,:fm,:fp,:p,:d,:j)
    ON DUPLICATE KEY UPDATE
      nama=VALUES(nama), kelas=VALUES(kelas), status_terakhir=VALUES(status_terakhir),
      jam_masuk=VALUES(jam_masuk), jam_pulang=VALUES(jam_pulang),
      foto_masuk=VALUES(foto_masuk), foto_pulang=VALUES(foto_pulang),
      data_json=VALUES(data_json), imported_at=NOW()" );

  $upDisp = $pdo->prepare("INSERT INTO fb_dispensasi (school_code, nisn, nama, start_date, end_date, alasan, created_at, source_project, doc_id, data_json)
    VALUES (:s,:n,:nm,:st,:en,:al,:cr,:p,:d,:j)
    ON DUPLICATE KEY UPDATE
      nisn=VALUES(nisn), nama=VALUES(nama), start_date=VALUES(start_date), end_date=VALUES(end_date),
      alasan=VALUES(alasan), created_at=VALUES(created_at), data_json=VALUES(data_json), imported_at=NOW()" );

  $upLib = $pdo->prepare("INSERT INTO fb_hari_libur (school_code, tanggal, keterangan, source_project, doc_id, data_json)
    VALUES (:s,:t,:k,:p,:d,:j)
    ON DUPLICATE KEY UPDATE keterangan=VALUES(keterangan), data_json=VALUES(data_json), imported_at=NOW()" );

  $upJurnal = $pdo->prepare("INSERT INTO fb_jurnal_guru (school_code, tanggal, nama_guru, kelas, mapel, materi, waktu, source_project, doc_id, data_json)
    VALUES (:s,:t,:g,:k,:m,:mt,:w,:p,:d,:j)
    ON DUPLICATE KEY UPDATE
      tanggal=VALUES(tanggal), nama_guru=VALUES(nama_guru), kelas=VALUES(kelas), mapel=VALUES(mapel), materi=VALUES(materi), waktu=VALUES(waktu),
      data_json=VALUES(data_json), imported_at=NOW()" );

  $upRef = $pdo->prepare("INSERT INTO fb_refleksi_siswa (school_code, tanggal, id_jurnal, nama, rating, pesan, source_project, doc_id, data_json)
    VALUES (:s,:t,:ij,:nm,:r,:ps,:p,:d,:j)
    ON DUPLICATE KEY UPDATE
      tanggal=VALUES(tanggal), id_jurnal=VALUES(id_jurnal), nama=VALUES(nama), rating=VALUES(rating), pesan=VALUES(pesan),
      data_json=VALUES(data_json), imported_at=NOW()" );

  $counts = [
    'files' => 0,
    'docs_total' => 0,
    'store_upsert' => 0,
    'absensi' => 0,
    'dispensasi' => 0,
    'hari_libur' => 0,
    'jurnal_guru' => 0,
    'refleksi_siswa' => 0,
    'skipped' => 0,
  ];

  for ($i = 0; $i < $zip->numFiles; $i++) {
    $stat = $zip->statIndex($i);
    if (!$stat) continue;
    $name = (string)($stat['name'] ?? '');

    if (!preg_match('#^data/([^/]+)/([^/]+)\.json$#', $name, $m)) {
      continue;
    }

    $project = (string)$m[1];
    $collection = (string)$m[2];

    $raw = $zip->getFromIndex($i);
    if ($raw === false) continue;

    $data = json_decode($raw, true);
    if (!is_array($data)) {
      $counts['skipped']++;
      continue;
    }

    $counts['files']++;

    foreach ($data as $doc) {
      if (!is_array($doc)) { $counts['skipped']++; continue; }
      $docId = (string)($doc['id'] ?? '');
      $payload = $doc['data'] ?? null;
      if ($docId === '' || !is_array($payload)) { $counts['skipped']++; continue; }

      $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
      if (!is_string($json)) { $counts['skipped']++; continue; }

      $upStore->execute([
        ':b' => $batch,
        ':s' => $school,
        ':p' => $project,
        ':c' => $collection,
        ':d' => $docId,
        ':j' => $json,
      ]);
      $counts['store_upsert']++;
      $counts['docs_total']++;

      // mapping ke tabel-tabel target
      if ($collection === 'absensi') {
        $tanggal = parse_date_only($payload['tanggal'] ?? null);
        $nisn = (string)($payload['nisn'] ?? $payload['username'] ?? $docId);
        $nisn = trim($nisn);
        if ($tanggal && $nisn !== '') {
          $upAbs->execute([
            ':s' => $school,
            ':t' => $tanggal,
            ':n' => $nisn,
            ':nm' => (string)($payload['nama'] ?? null),
            ':k' => (string)($payload['kelas'] ?? null),
            ':st' => (string)($payload['status_terakhir'] ?? $payload['status'] ?? null),
            ':jm' => parse_iso_datetime($payload['jam_masuk'] ?? null),
            ':jp' => parse_iso_datetime($payload['jam_pulang'] ?? null),
            ':fm' => (string)($payload['foto_masuk'] ?? null),
            ':fp' => (string)($payload['foto_pulang'] ?? null),
            ':p' => $project,
            ':d' => $docId,
            ':j' => $json,
          ]);
          $counts['absensi']++;
        } else {
          $counts['skipped']++;
        }
      }

      if ($collection === 'dispensasi') {
        $upDisp->execute([
          ':s' => $school,
          ':n' => (string)($payload['nisn'] ?? $payload['username'] ?? ''),
          ':nm' => (string)($payload['nama'] ?? null),
          ':st' => parse_date_only($payload['start'] ?? $payload['start_date'] ?? null),
          ':en' => parse_date_only($payload['end'] ?? $payload['end_date'] ?? null),
          ':al' => (string)($payload['alasan'] ?? null),
          ':cr' => parse_iso_datetime($payload['created_at'] ?? null),
          ':p' => $project,
          ':d' => $docId,
          ':j' => $json,
        ]);
        $counts['dispensasi']++;
      }

      if ($collection === 'hari_libur') {
        $tanggal = parse_date_only($payload['tanggal'] ?? null);
        if ($tanggal) {
          $upLib->execute([
            ':s' => $school,
            ':t' => $tanggal,
            ':k' => (string)($payload['keterangan'] ?? null),
            ':p' => $project,
            ':d' => $docId,
            ':j' => $json,
          ]);
          $counts['hari_libur']++;
        } else {
          $counts['skipped']++;
        }
      }

      if ($collection === 'jurnal_guru') {
        $tanggal = parse_date_only($payload['tanggal'] ?? null);
        if (!$tanggal) {
          // fallback: coba dari waktu
          $tanggal = parse_date_only($payload['waktu'] ?? null);
        }
        if ($tanggal) {
          $upJurnal->execute([
            ':s' => $school,
            ':t' => $tanggal,
            ':g' => (string)($payload['nama_guru'] ?? null),
            ':k' => (string)($payload['kelas'] ?? null),
            ':m' => (string)($payload['mapel'] ?? null),
            ':mt' => (string)($payload['materi'] ?? null),
            ':w' => parse_iso_datetime($payload['waktu'] ?? null),
            ':p' => $project,
            ':d' => $docId,
            ':j' => $json,
          ]);
          $counts['jurnal_guru']++;
        } else {
          $counts['skipped']++;
        }
      }

      if ($collection === 'refleksi_siswa') {
        $tanggal = parse_date_only($payload['tanggal'] ?? null);
        if ($tanggal) {
          $upRef->execute([
            ':s' => $school,
            ':t' => $tanggal,
            ':ij' => (string)($payload['id_jurnal'] ?? null),
            ':nm' => (string)($payload['nama'] ?? null),
            ':r' => isset($payload['rating']) ? (int)$payload['rating'] : null,
            ':ps' => (string)($payload['pesan'] ?? null),
            ':p' => $project,
            ':d' => $docId,
            ':j' => $json,
          ]);
          $counts['refleksi_siswa']++;
        } else {
          $counts['skipped']++;
        }
      }
    }
  }

  $pdo->commit();
  $zip->close();

  jexit(['ok' => true, 'batch_id' => $batch, 'school_code' => $school, 'counts' => $counts]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  try { $zip->close(); } catch (Throwable $x) {}
  jexit(['ok' => false, 'error' => 'Import gagal: ' . $e->getMessage()], 500);
}
