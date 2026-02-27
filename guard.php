<?php
session_start();

// Multi-tenant (SaaS): resolve sekolah dari subdomain
require_once __DIR__ . '/../config/tenant.php';

function school_id(): int {
  return (int)($_SESSION['school_id'] ?? 0);
}

function require_login(array $roles = []) {
  if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit;
  }
  if (!empty($roles)) {
    $role = $_SESSION['user']['role'] ?? '';
    if (!in_array($role, $roles, true)) {
      header("Location: /index.php");
      exit;
    }
  }
}
