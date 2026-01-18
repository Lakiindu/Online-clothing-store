<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// 1) Load .env first
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// 2) Helpers
require_once __DIR__ . '/../src/utils/response.php';
require_once __DIR__ . '/../src/config/db.php';

// 3) CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// 4) Parse request ONCE
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// 5) Load routes ONCE (after $uri/$method exist)
require_once __DIR__ . '/../src/routes/auth.php';
require_once __DIR__ . '/../src/routes/me.php';
require_once __DIR__ . '/../src/routes/categories.php';
require_once __DIR__ . '/../src/routes/admin_categories.php';
require_once __DIR__ . '/../src/routes/products.php';
require_once __DIR__ . '/../src/routes/cart.php';
require_once __DIR__ . '/../src/routes/checkout.php';
require_once __DIR__ . '/../src/routes/orders.php';
require_once __DIR__ . '/../src/routes/delivery_assign.php';
require_once __DIR__ . '/../src/routes/delivery_orders.php';
require_once __DIR__ . '/../src/routes/delivery_update.php';
require_once __DIR__ . '/../src/routes/orders_list.php';
require_once __DIR__ . '/../src/routes/cart_get.php';

// 6) Basic routes
if ($uri === '/api/health' && $method === 'GET') {
  json_ok(["status" => "API is running"]);
}

if ($uri === '/api/db-test' && $method === 'GET') {
  try {
    $pdo = db();
    $row = $pdo->query("SELECT NOW() AS now")->fetch(PDO::FETCH_ASSOC);
    json_ok(["db" => "connected", "time" => $row["now"]]);
  } catch (Exception $e) {
    json_error("DB connection failed: " . $e->getMessage(), 500);
  }
}

json_error("Not Found", 404);
