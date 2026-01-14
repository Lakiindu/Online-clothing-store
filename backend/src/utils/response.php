<?php

function json_ok($data = null, int $code = 200) {
  http_response_code($code);
  header("Content-Type: application/json; charset=UTF-8");
  echo json_encode(["ok" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
  exit;
}

function json_error(string $message, int $code = 400) {
  http_response_code($code);
  header("Content-Type: application/json; charset=UTF-8");
  echo json_encode(["ok" => false, "message" => $message], JSON_UNESCAPED_UNICODE);
  exit;
}
