<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/jwt.php';

function require_auth(): array {
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

  if (!preg_match('/Bearer\s(\S+)/', $hdr, $matches)) {
    json_error("Missing Authorization: Bearer <token>", 401);
  }

  try {
    return jwt_verify($matches[1]); // returns payload array
  } catch (Exception $e) {
    json_error("Invalid or expired token", 401);
  }
}
