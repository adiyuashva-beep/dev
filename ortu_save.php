<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['admin', 'super']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

function json_in() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function randomPassword() {
    $char = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($char), 0, 8);
}

try {
    edugate_v5_ensure_tables($pdo);
    $sid = school_id();
    if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');

    $in = json_in();
    $mode = $in['mode'] ?? ''; // create / update
    $nama = trim($in['nama'] ?? '');
    $username = trim($in['username'] ?? '');
    $password = $in['password'] ?? '';
    $anak = $in['anak'] ?? []; // array of siswa username

    if ($mode === '' || $nama === '' || $username === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'mode, nama, username wajib']);
        exit;
    }
    if (!is_array($anak)) $anak = [];

    $pdo->beginTransaction();

    if ($mode === 'create') {
        // Cek apakah username sudah dipakai di sekolah ini
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE school_id = ? AND username = ?");
        $stmt->execute([$sid, $username]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'Username sudah digunakan']);
            exit;
        }

        // Password: jika kosong, generate random
        if ($password === '') $password = randomPassword();
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (school_id, name, username, password_hash, role) VALUES (?, ?, ?, ?, 'ortu')");
        $stmt->execute([$sid, $nama, $username, $hash]);

    } else { // update
        $original = $in['original_username'] ?? '';
        if (!$original) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'original_username wajib untuk update']);
            exit;
        }

        // Update data user (kecuali username, username tidak diubah)
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, password_hash = ? WHERE school_id = ? AND username = ?");
            $stmt->execute([$nama, $hash, $sid, $original]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE school_id = ? AND username = ?");
            $stmt->execute([$nama, $sid, $original]);
        }

        // Hapus semua relasi anak lama
        $stmt = $pdo->prepare("DELETE FROM ortu_anak WHERE school_id = ? AND ortu_username = ?");
        $stmt->execute([$sid, $original]);

        $username = $original; // untuk relasi anak nanti
    }

    // Insert relasi anak baru
    if (!empty($anak)) {
        // Pastikan setiap anak adalah siswa di sekolah ini
        $placeholders = implode(',', array_fill(0, count($anak), '?'));
        $stmt = $pdo->prepare("SELECT username FROM users WHERE school_id = ? AND role = 'siswa' AND username IN ($placeholders)");
        $params = array_merge([$sid], $anak);
        $stmt->execute($params);
        $validSiswa = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $insert = $pdo->prepare("INSERT INTO ortu_anak (school_id, ortu_username, siswa_username) VALUES (?, ?, ?)");
        foreach ($validSiswa as $siswaUsername) {
            $insert->execute([$sid, $username, $siswaUsername]);
        }
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'message' => 'Data orang tua tersimpan']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}