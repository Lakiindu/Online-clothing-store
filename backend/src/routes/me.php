<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middlewares/auth.php';

if ($uri === '/api/me' && $method === 'GET') {
  $payload = require_auth();           // JWT payload
  $userId = intval($payload['sub'] ?? 0);

  $pdo = db();
  $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    json_error("User not found", 404);
  }

  json_ok(["user" => $user, "token_payload" => $payload]);
}
