<?php

function loadEnv($filePath = null) {
    if ($filePath === null) {
        $filePath = dirname(__DIR__) . '/.env';
    }
    
    $config = [];
    
    $isDocker = getenv('DOCKER_CONTAINER') || file_exists('/.dockerenv');
    $isLocal = isset($_SERVER['SERVER_NAME']) && (strpos($_SERVER['SERVER_NAME'], 'local') !== false || $_SERVER['SERVER_ADDR'] === '127.0.0.1');
    
    if ($isDocker) {
        $defaults = [
            'DB_HOST' => 'db',
            'DB_PORT' => '3306',
            'DB_NAME' => 'absensi_siswa',
            'DB_USER' => 'root',
            'DB_PASS' => 'rootpass',
            'BASE_URL' => '/',
            'APP_ENV' => 'docker',
            'TIMEZONE' => 'Asia/Jakarta',
            'APP_SECRET' => 'default_secret_change_me',
            'UPLOAD_MAX_SIZE' => '2M',
            'ALLOWED_EXTENSIONS' => 'jpg,jpeg,png,pdf'
        ];
    } elseif ($isLocal) {
        $defaults = [
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_NAME' => 'absensi_siswa',
            'DB_USER' => 'root',
            'DB_PASS' => '',
            'BASE_URL' => '/',
            'APP_ENV' => 'development',
            'TIMEZONE' => 'Asia/Jakarta',
            'APP_SECRET' => 'default_secret_change_me',
            'UPLOAD_MAX_SIZE' => '2M',
            'ALLOWED_EXTENSIONS' => 'jpg,jpeg,png,pdf'
        ];
    } else {
        $defaults = [
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_NAME' => 'absensi_siswa',
            'DB_USER' => 'root',
            'DB_PASS' => '',
            'BASE_URL' => '/',
            'APP_ENV' => 'production',
            'TIMEZONE' => 'Asia/Jakarta',
            'APP_SECRET' => 'change_this_to_random_string_min_32_chars',
            'UPLOAD_MAX_SIZE' => '2M',
            'ALLOWED_EXTENSIONS' => 'jpg,jpeg,png,pdf'
        ];
    }
    
    if (file_exists($filePath)) {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                $config[$key] = $value;
            }
        }
    }
    
    $config = array_merge($defaults, $config);
    
    return $config;
}

$appConfig = loadEnv();

define('DB_HOST', $appConfig['DB_HOST']);
define('DB_PORT', $appConfig['DB_PORT']);
define('DB_NAME', $appConfig['DB_NAME']);
define('DB_USER', $appConfig['DB_USER']);
define('DB_PASS', $appConfig['DB_PASS']);
define('BASE_URL', $appConfig['BASE_URL']);
define('APP_ENV', $appConfig['APP_ENV']);
define('APP_TIMEZONE', $appConfig['TIMEZONE']);
define('APP_SECRET', $appConfig['APP_SECRET']);

if (!defined('BASE_URL')) {
    define('BASE_URL', $appConfig['BASE_URL']);
}
