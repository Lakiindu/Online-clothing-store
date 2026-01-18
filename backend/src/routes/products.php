<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/db.php';

// GET /api/products (list)
if ($uri === '/api/products' && $method === 'GET') {
  $pdo = db();
  $rows = $pdo->query("
    SELECT p.id, p.name, p.description, p.price, p.stock, p.category_id, p.created_at,
           c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.id DESC
  ")->fetchAll(PDO::FETCH_ASSOC);

  json_ok($rows);
}

// GET /api/products/{id} (single)
if (preg_match('#^/api/products/(\d+)$#', $uri, $m) && $method === 'GET') {
  $id = intval($m[1]);
  $pdo = db();

  $stmt = $pdo->prepare("
    SELECT p.id, p.name, p.description, p.price, p.stock, p.category_id, p.created_at,
           c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?
  ");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) json_error("Product not found", 404);
  json_ok($row);
}

// POST /api/products (create) - PUBLIC for now
if ($uri === '/api/products' && $method === 'POST') {
  $body = json_decode(file_get_contents("php://input"), true) ?? [];

  $name = trim($body['name'] ?? '');
  $description = trim($body['description'] ?? '');
  $price = $body['price'] ?? null;
  $stock = $body['stock'] ?? 0;
  $category_id = $body['category_id'] ?? null;

  if ($name === '' || !is_numeric($price)) {
    json_error("name and numeric price required", 422);
  }

  $pdo = db();
  $stmt = $pdo->prepare("
    INSERT INTO products(name, description, price, stock, category_id)
    VALUES(?,?,?,?,?)
    RETURNING id, name, description, price, stock, category_id, created_at
  ");
  $stmt->execute([$name, $description, $price, $stock, $category_id]);

  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  json_ok($row, 201);
}
