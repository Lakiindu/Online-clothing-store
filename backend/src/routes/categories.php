<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/db.php';

// Public: GET /api/categories
if ($uri === '/api/categories' && $method === 'GET') {
  $pdo = db();
  $rows = $pdo->query("SELECT id, name, created_at FROM categories ORDER BY id DESC")
              ->fetchAll(PDO::FETCH_ASSOC);
  json_ok($rows);
}
