<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middlewares/auth.php';

// GET /api/orders  (customer) - order history
if ($uri === '/api/orders' && $method === 'GET') {
  $payload = require_auth();
  $userId = intval($payload['sub']);

  $pdo = db();
  $stmt = $pdo->prepare("
    SELECT id, status, total, shipping_address, created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY id DESC
  ");
  $stmt->execute([$userId]);
  $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

  json_ok($orders);
}

// GET /api/orders/{id} (customer) - order details
if (preg_match('#^/api/orders/(\d+)$#', $uri, $m) && $method === 'GET') {
  $payload = require_auth();
  $userId = intval($payload['sub']);
  $orderId = intval($m[1]);

  $pdo = db();

  // order (must belong to user)
  $stmt = $pdo->prepare("
    SELECT id, user_id, status, total, shipping_address, created_at
    FROM orders
    WHERE id = ? AND user_id = ?
  ");
  $stmt->execute([$orderId, $userId]);
  $order = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$order) json_error("Order not found", 404);

  // items
  $stmt = $pdo->prepare("
    SELECT oi.id, oi.product_id, p.name, oi.price, oi.quantity
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
  ");
  $stmt->execute([$orderId]);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // payment
  $stmt = $pdo->prepare("
    SELECT id, method, amount, status, created_at
    FROM payments
    WHERE order_id = ?
  ");
  $stmt->execute([$orderId]);
  $payment = $stmt->fetch(PDO::FETCH_ASSOC);

  json_ok([
    "order" => $order,
    "items" => $items,
    "payment" => $payment
  ]);
}
