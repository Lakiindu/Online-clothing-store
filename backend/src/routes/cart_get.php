<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middlewares/auth.php';

// GET /api/cart  (customer)
if ($uri === '/api/cart' && $method === 'GET') {
  $user = require_auth();
  $pdo = db();

  // find user's cart (create if not exists)
  $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
  $stmt->execute([$user['id']]);
  $cartId = $stmt->fetchColumn();

  if (!$cartId) {
    $stmt = $pdo->prepare("INSERT INTO carts (user_id) VALUES (?) RETURNING id");
    $stmt->execute([$user['id']]);
    $cartId = $stmt->fetchColumn();
  }

  // load items with product details
  $stmt = $pdo->prepare("
    SELECT ci.id,
           ci.product_id,
           p.name,
           p.price,
           ci.quantity,
           (p.price * ci.quantity) AS line_total
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    WHERE ci.cart_id = ?
    ORDER BY ci.id DESC
  ");
  $stmt->execute([$cartId]);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $total = 0;
  foreach ($items as $it) $total += floatval($it['line_total']);

  json_ok([
    "cart_id" => intval($cartId),
    "items" => $items,
    "total" => number_format($total, 2, '.', '')
  ]);
}
