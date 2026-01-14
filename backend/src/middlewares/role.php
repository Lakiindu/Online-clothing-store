<?php
require_once __DIR__ . '/../utils/response.php';

function require_role(array $payload, array $roles): void {
  $role = $payload['role'] ?? null;
  if (!$role || !in_array($role, $roles, true)) {
    json_error("Forbidden: insufficient role", 403);
  }
}
