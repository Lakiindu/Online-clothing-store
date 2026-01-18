<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middlewares/auth.php';

// GET /api/orders  (customer)
if ($uri === '/api/orders' && $method === 'GET') {
  // must be logged in
  $user = require_auth(); // returns user array (id, role, email, ...)

  $pdo = db();

  // If admin wants later: we can add role check. For now customer history.
  $stmt = $pdo->prepare("
    SELECT id, user_id, status, shipping_address, total, created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY id DESC
  ");
  $stmt->execute([$user['id']]);
  $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

  json_ok($orders);
}
