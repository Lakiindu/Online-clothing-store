<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middlewares/auth.php';

// Helper: get or create cart for user
function get_or_create_cart_id(PDO $pdo, int $userId): int {
  $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
  $stmt->execute([$userId]);
  $cartId = $stmt->fetchColumn();

  if ($cartId) return intval($cartId);

  $stmt = $pdo->prepare("INSERT INTO carts(user_id) VALUES(?) RETURNING id");
  $stmt->execute([$userId]);
  return intval($stmt->fetchColumn());
}

/**
 * GET /api/cart  (customer)
 */
if ($uri === '/api/cart' && $method === 'GET') {
  $payload = require_auth();
  $userId = intval($payload['sub']);

  $pdo = db();
  $cartId = get_or_create_cart_id($pdo, $userId);

  $stmt = $pdo->prepare("
    SELECT ci.id AS cart_item_id, ci.quantity,
           p.id AS product_id, p.name, p.price
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    WHERE ci.cart_id = ?
    ORDER BY ci.id DESC
  ");
  $stmt->execute([$cartId]);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // total
  $total = 0;
  foreach ($items as $it) {
    $total += floatval($it['price']) * intval($it['quantity']);
  }

  json_ok(["cart_id" => $cartId, "items" => $items, "total" => $total]);
}

/**
 * POST /api/cart/items  (customer)
 * body: { product_id, quantity }
 */
if ($uri === '/api/cart/items' && $method === 'POST') {
  $payload = require_auth();
  $userId = intval($payload['sub']);

  $body = json_decode(file_get_contents("php://input"), true) ?? [];
  $productId = intval($body['product_id'] ?? 0);
  $qty = intval($body['quantity'] ?? 1);
  if ($productId <= 0 || $qty <= 0) json_error("product_id and quantity required", 422);

  $pdo = db();
  $cartId = get_or_create_cart_id($pdo, $userId);

  // upsert: if exists update, else insert
  $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id=? AND product_id=?");
  $stmt->execute([$cartId, $productId]);
  $existing = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($existing) {
    $newQty = intval($existing['quantity']) + $qty;
    $upd = $pdo->prepare("UPDATE cart_items SET quantity=? WHERE id=?");
    $upd->execute([$newQty, $existing['id']]);
    json_ok(["message" => "updated", "cart_item_id" => intval($existing['id']), "quantity" => $newQty], 200);
  } else {
    $ins = $pdo->prepare("INSERT INTO cart_items(cart_id, product_id, quantity) VALUES(?,?,?) RETURNING id");
    $ins->execute([$cartId, $productId, $qty]);
    $id = intval($ins->fetchColumn());
    json_ok(["message" => "added", "cart_item_id" => $id, "quantity" => $qty], 201);
  }
}

/**
 * PATCH /api/cart/items/{cart_item_id} (customer)
 * body: { quantity }
 */
if (preg_match('#^/api/cart/items/(\d+)$#', $uri, $m) && $method === 'PATCH') {
  $payload = require_auth();
  $userId = intval($payload['sub']);
  $cartItemId = intval($m[1]);

  $body = json_decode(file_get_contents("php://input"), true) ?? [];
  $qty = intval($body['quantity'] ?? 0);
  if ($qty <= 0) json_error("quantity must be > 0", 422);

  $pdo = db();
  $cartId = get_or_create_cart_id($pdo, $userId);

  // make sure this item belongs to the user's cart
  $stmt = $pdo->prepare("UPDATE cart_items SET quantity=? WHERE id=? AND cart_id=? RETURNING id");
  $stmt->execute([$qty, $cartItemId, $cartId]);
  $updated = $stmt->fetchColumn();

  if (!$updated) json_error("Cart item not found", 404);
  json_ok(["message" => "quantity updated", "cart_item_id" => $cartItemId, "quantity" => $qty]);
}

/**
 * DELETE /api/cart/items/{cart_item_id} (customer)
 */
if (preg_match('#^/api/cart/items/(\d+)$#', $uri, $m) && $method === 'DELETE') {
  $payload = require_auth();
  $userId = intval($payload['sub']);
  $cartItemId = intval($m[1]);

  $pdo = db();
  $cartId = get_or_create_cart_id($pdo, $userId);

  $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id=? AND cart_id=?");
  $stmt->execute([$cartItemId, $cartId]);

  if ($stmt->rowCount() === 0) json_error("Cart item not found", 404);
  json_ok(["message" => "deleted", "cart_item_id" => $cartItemId]);
}
