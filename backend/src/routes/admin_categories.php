<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middlewares/auth.php';
require_once __DIR__ . '/../middlewares/role.php';

// Admin: POST /api/admin/categories
if ($uri === '/api/admin/categories' && $method === 'POST') {
  $payload = require_auth();
  require_role($payload, ['admin']);

  $body = json_decode(file_get_contents("php://input"), true) ?? [];
  $name = trim($body['name'] ?? '');

  if ($name === '') {
    json_error("Category name required", 422);
  }

  $pdo = db();
  try {
    $stmt = $pdo->prepare("INSERT INTO categories(name) VALUES(?) RETURNING id, name, created_at");
    $stmt->execute([$name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    json_ok($row, 201);
  } catch (PDOException $e) {
    json_error("Category already exists", 409);
  }
}
