<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function jwt_sign(array $payload): string {
  $secret = $_ENV['JWT_SECRET'] ?? '';
  if (!$secret) {
    throw new Exception("JWT_SECRET missing in .env");
  }

  $issuer = $_ENV['JWT_ISSUER'] ?? 'clothing-store';
  $expMin = intval($_ENV['JWT_EXPIRE_MIN'] ?? 120);

  $now = time();
  $token = array_merge($payload, [
    "iss" => $issuer,
    "iat" => $now,
    "exp" => $now + ($expMin * 60),
  ]);

  return JWT::encode($token, $secret, 'HS256');
}

function jwt_verify(string $jwt): array {
  $secret = $_ENV['JWT_SECRET'] ?? '';
  if (!$secret) {
    throw new Exception("JWT_SECRET missing in .env");
  }

  $decoded = JWT::decode($jwt, new Key($secret, 'HS256'));
  return (array)$decoded;
}
