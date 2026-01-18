<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/db.php';

// PATCH /api/delivery/status
// body: { order_id, delivery_user_id, status }
if ($uri === '/api/delivery/status' && $method === 'PATCH') {
  $body = json_decode(file_get_contents("php://input"), true) ?? [];
  $orderId = intval($body['order_id'] ?? 0);
  $deliveryUserId = intval($body['delivery_user_id'] ?? 0);
  $status = trim($body['status'] ?? '');

  $allowed = ['PickedUp','OnTheWay','Delivered'];
  if ($orderId <= 0 || $deliveryUserId <= 0 || !in_array($status, $allowed, true)) {
    json_error("order_id, delivery_user_id and valid status (PickedUp/OnTheWay/Delivered) required", 422);
  }

  $pdo = db();

  // update assignment only if it belongs to this delivery user
  $stmt = $pdo->prepare("
    UPDATE delivery_assignments
    SET status = ?
    WHERE order_id = ? AND delivery_user_id = ?
    RETURNING id
  ");
  $stmt->execute([$status, $orderId, $deliveryUserId]);
  $ok = $stmt->fetchColumn();
  if (!$ok) json_error("Assignment not found", 404);

  // keep orders.status in sync
  $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
  $stmt->execute([$status, $orderId]);

  json_ok(["order_id" => $orderId, "delivery_user_id" => $deliveryUserId, "status" => $status]);
}
