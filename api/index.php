<?php
// Vercel runs PHP requests in short-lived, read-only functions.
putenv('ORBIT_SERVERLESS=1');
putenv('ORBIT_MEDIA_STORAGE=database');
if (!getenv('CI_ENVIRONMENT')) putenv('CI_ENVIRONMENT=production');
if (!getenv('APP_BASE_URL') && getenv('VERCEL_URL')) {
    putenv('APP_BASE_URL=https://' . getenv('VERCEL_URL') . '/');
}
foreach (['', 'cache', 'logs', 'session', 'uploads', 'debugbar'] as $directory) {
    $path = sys_get_temp_dir() . '/orbit-writable' . ($directory ? '/' . $directory : '');
    if (!is_dir($path)) mkdir($path, 0700, true);
}
require __DIR__ . '/../public/index.php';
