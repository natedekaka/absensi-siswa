<?php
require_once __DIR__ . '/core/init.php';
require_once __DIR__ . '/core/Database.php';

$migrationsDir = __DIR__ . '/migrations/';
$flagFile = $migrationsDir . 'last_migration.txt';

if (!is_dir($migrationsDir)) {
    die("Migrations directory not found: $migrationsDir\n");
}

$migrations = glob($migrationsDir . '*.sql');
sort($migrations);

if (isset($argv[1]) && $argv[1] === '--fresh') {
    echo "WARNING: This will DROP ALL TABLES and re-run migrations.\n";
    echo "Press Ctrl+C to cancel, or wait 5 seconds to continue...\n";
    sleep(5);
    
    $tables = conn()->query("SHOW TABLES");
    while ($row = $tables->fetch_row()) {
        conn()->query("DROP TABLE IF EXISTS `{$row[0]}`");
        echo "Dropped table: {$row[0]}\n";
    }
    
    file_put_contents($flagFile, '');
    echo "All tables dropped. Re-running migrations...\n";
}

$lastRun = file_exists($flagFile) ? trim(file_get_contents($flagFile)) : '';
$ran = [];

foreach ($migrations as $file) {
    $basename = basename($file);
    
    if ($lastRun && $basename <= $lastRun && !isset($argv[1])) {
        continue;
    }
    
    echo "Running: $basename\n";
    
    $sql = file_get_contents($file);
    
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query) || strpos($query, '--') === 0) {
            continue;
        }
        
        if (conn()->query($query)) {
        } else {
            echo "ERROR in $basename: " . conn()->error . "\n";
        }
    }
    
    $ran[] = $basename;
    file_put_contents($flagFile, $basename);
}

if (count($ran) > 0) {
    echo "\nCompleted " . count($ran) . " migration(s):\n";
    foreach ($ran as $m) {
        echo "  - $m\n";
    }
} else {
    echo "No new migrations to run.\n";
}

echo "\nDone.\n";
