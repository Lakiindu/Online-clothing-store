<?php

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $host = $_ENV['DB_HOST'];
  $port = $_ENV['DB_PORT'];
  $name = $_ENV['DB_NAME'];
  $user = $_ENV['DB_USER'];
  $pass = $_ENV['DB_PASS'];

  $dsn = "pgsql:host=$host;port=$port;dbname=$name";
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  ]);

  return $pdo;
}
