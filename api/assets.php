<?php
$requested = (string) ($_GET['path'] ?? '');
if (!preg_match('~^(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_-]+\.(?:css|js|svg|png|jpg|jpeg|webp|ico|txt)$~D', $requested)) {
    http_response_code(404);
    exit;
}
$path = realpath(__DIR__ . '/../public/assets/' . $requested);
$root = realpath(__DIR__ . '/../public/assets');
if (!$path || !$root || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
    http_response_code(404);
    exit;
}
$mime = [
    'css'=>'text/css', 'js'=>'application/javascript', 'svg'=>'image/svg+xml',
    'png'=>'image/png', 'jpg'=>'image/jpeg', 'jpeg'=>'image/jpeg',
    'webp'=>'image/webp', 'ico'=>'image/x-icon', 'txt'=>'text/plain',
][strtolower(pathinfo($path, PATHINFO_EXTENSION))];
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
readfile($path);
