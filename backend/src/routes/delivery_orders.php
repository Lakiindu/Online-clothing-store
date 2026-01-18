<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/db.php';

// GET /api/delivery/orders?delivery_user_id=8   (PUBLIC for now)
if ($uri === '/api/delivery/orders' && $method === 'GET') {
  $deliveryUserId = intval($_GET['delivery_user_id'] ?? 0);
  if ($deliveryUserId <= 0) json_error("delivery_user_id required", 422);

  $pdo = db();
  $stmt = $pdo->prepare("
    SELECT da.id AS assignment_id, da.order_id, da.delivery_user_id, da.status AS delivery_status, da.assigned_at,
           o.status AS order_status, o.shipping_address, o.total, o.created_at
    FROM delivery_assignments da
    JOIN orders o ON o.id = da.order_id
    WHERE da.delivery_user_id = ?
    ORDER BY da.id DESC
  ");
  $stmt->execute([$deliveryUserId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  json_ok($rows);
}
