<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middlewares/auth.php';

// POST /api/checkout  (customer)
// body: { shipping_address, payment_method }
if ($uri === '/api/checkout' && $method === 'POST') {
  $payload = require_auth();
  $userId = intval($payload['sub']);

  $body = json_decode(file_get_contents("php://input"), true) ?? [];
  $shipping = trim($body['shipping_address'] ?? '');
  $payMethod = trim($body['payment_method'] ?? 'cod');

  if ($shipping === '') json_error("shipping_address required", 422);

  $pdo = db();

  // Find user's cart
  $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
  $stmt->execute([$userId]);
  $cartId = $stmt->fetchColumn();
  if (!$cartId) json_error("Cart not found", 404);

  // Get cart items + product info
  $stmt = $pdo->prepare("
    SELECT ci.product_id, ci.quantity, p.price, p.stock, p.name
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    WHERE ci.cart_id = ?
    ORDER BY ci.id ASC
  ");
  $stmt->execute([$cartId]);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (!$items) json_error("Cart is empty", 422);

  // Start transaction
  $pdo->beginTransaction();
  try {
    // Check stock & compute total
    $total = 0.0;
    foreach ($items as $it) {
      $qty = intval($it['quantity']);
      $stock = intval($it['stock']);
      if ($qty > $stock) {
        throw new Exception("Not enough stock for: " . $it['name']);
      }
      $total += floatval($it['price']) * $qty;
    }

    // Create order
    $stmt = $pdo->prepare("
      INSERT INTO orders(user_id, status, shipping_address, total)
      VALUES(?, 'Pending', ?, ?)
      RETURNING id, status, total, created_at
    ");
    $stmt->execute([$userId, $shipping, $total]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    $orderId = intval($order['id']);

    // Create order_items + reduce stock
    $insItem = $pdo->prepare("
      INSERT INTO order_items(order_id, product_id, price, quantity)
      VALUES(?,?,?,?)
    ");
    $updStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($items as $it) {
      $insItem->execute([$orderId, $it['product_id'], $it['price'], $it['quantity']]);
      $updStock->execute([$it['quantity'], $it['product_id']]);
    }

    // Record payment (simple)
    $stmt = $pdo->prepare("
      INSERT INTO payments(order_id, method, amount, status)
      VALUES(?, ?, ?, 'Recorded')
      RETURNING id
    ");
    $stmt->execute([$orderId, $payMethod, $total]);
    $paymentId = intval($stmt->fetchColumn());

    // Clear cart
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cartId]);

    $pdo->commit();

    json_ok([
      "order" => $order,
      "payment_id" => $paymentId
    ], 201);

  } catch (Exception $e) {
    $pdo->rollBack();
    json_error($e->getMessage(), 400);
  }
}
