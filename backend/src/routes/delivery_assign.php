<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/db.php';

// POST /api/delivery/assign  (PUBLIC for now)
// body: { order_id, delivery_user_id }
if ($uri === '/api/delivery/assign' && $method === 'POST') {
  $body = json_decode(file_get_contents("php://input"), true) ?? [];
  $orderId = intval($body['order_id'] ?? 0);
  $deliveryUserId = intval($body['delivery_user_id'] ?? 0);

  if ($orderId <= 0 || $deliveryUserId <= 0) {
    json_error("order_id and delivery_user_id required", 422);
  }

  $pdo = db();

  // ensure order exists
  $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
  $stmt->execute([$orderId]);
  if (!$stmt->fetchColumn()) json_error("Order not found", 404);

  // ensure delivery user exists
  $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'delivery'");
  $stmt->execute([$deliveryUserId]);
  if (!$stmt->fetchColumn()) json_error("Delivery user not found", 404);

  // create assignment (or replace)
  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare("
      INSERT INTO delivery_assignments(order_id, delivery_user_id, status)
      VALUES(?, ?, 'Assigned')
      ON CONFLICT (order_id)
      DO UPDATE SET delivery_user_id = EXCLUDED.delivery_user_id,
                    status = 'Assigned',
                    assigned_at = NOW()
      RETURNING id, order_id, delivery_user_id, status, assigned_at
    ");
    $stmt->execute([$orderId, $deliveryUserId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // update order status
    $stmt = $pdo->prepare("UPDATE orders SET status='Assigned' WHERE id=?");
    $stmt->execute([$orderId]);

    $pdo->commit();
    json_ok($row, 201);
  } catch (Exception $e) {
    $pdo->rollBack();
    json_error("Failed to assign delivery", 500);
  }
}
