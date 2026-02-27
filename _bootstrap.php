<?php
/**
 * api/_bootstrap.php
 * Bootstrap standar untuk endpoint API EduGate.
 */

declare(strict_types=1);

require_once __DIR__ . '/../auth/guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json; charset=utf-8');

function ensure_schema(): void {
  global $pdo;
  edugate_v5_ensure_tables($pdo);
}

function sid(): int {
  return school_id();
}

function json_in(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '[]', true);
  return is_array($data) ? $data : [];
}

function json_out(array $payload, int $code = 200): void {
  http_response_code($code);
  echo json_encode($payload);
  exit;
}

function require_tenant(): int {
  $sid = sid();
  if ($sid <= 0) {
    json_out(['ok'=>false,'error'=>'Tenant tidak valid'], 400);
  }
  return $sid;
}
