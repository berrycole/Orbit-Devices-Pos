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
register_shutdown_function(static function (): void {
    if (http_response_code() < 500) return;
    $logs = glob(sys_get_temp_dir() . '/orbit-writable/logs/log-*.log');
    if (!$logs) return;
    $log = file_get_contents(end($logs));
    if ($log !== false) error_log('Orbit server error: ' . substr($log, -4000));
});
require __DIR__ . '/../public/index.php';
