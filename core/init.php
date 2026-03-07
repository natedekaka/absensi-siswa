<?php
define('BASE_URL', '/absensi-siswa/');

function asset($path) {
    return BASE_URL . 'assets/' . ltrim($path, '/');
}
