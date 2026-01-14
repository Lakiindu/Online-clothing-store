<?php
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/jwt.php';
require_once __DIR__ . '/../config/db.php';

function hash_password(string $password): string {
  return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password(string $password, string $hash): bool {
  return password_verify($password, $hash);
}

/**
 * POST /api/auth/register
 * body: { name, email, password, role }
 */
if ($uri === '/api/auth/register' && $method === 'POST') {
  $body = json_decode(file_get_contents("php://input"), true) ?? [];

  $name = trim($body['name'] ?? '');
  $email = trim($body['email'] ?? '');
  $password = (string)($body['password'] ?? '');
  $role = $body['role'] ?? 'customer';

  // IMPORTANT: for security, only allow customer registration publicly
  if ($role !== 'customer') $role = 'customer';

  if ($name === '' || $email === '' || strlen($password) < 6) {
    json_error("Invalid input (name/email/password)", 422);
  }

  try {
    $pdo = db();

    $stmt = $pdo->prepare("INSERT INTO users(name,email,password_hash,role)
                           VALUES(?,?,?,?)
                           RETURNING id, role, email");
    $stmt->execute([$name, $email, hash_password($password), $role]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    $token = jwt_sign([
      "sub" => $u["id"],
      "email" => $u["email"],
      "role" => $u["role"]
    ]);

    json_ok(["token" => $token, "user" => $u], 201);
  } catch (PDOException $e) {
    // unique email violation
    json_error("Email already exists", 409);
  }
}

/**
 * POST /api/auth/login
 * body: { email, password }
 */
if ($uri === '/api/auth/login' && $method === 'POST') {
  $body = json_decode(file_get_contents("php://input"), true) ?? [];

  $email = trim($body['email'] ?? '');
  $password = (string)($body['password'] ?? '');

  if ($email === '' || $password === '') {
    json_error("Email and password required", 422);
  }

  $pdo = db();
  $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $u = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$u || !verify_password($password, $u['password_hash'])) {
    json_error("Invalid credentials", 401);
  }

  $token = jwt_sign([
    "sub" => $u["id"],
    "email" => $u["email"],
    "role" => $u["role"]
  ]);

  unset($u['password_hash']);
  json_ok(["token" => $token, "user" => $u]);
}
