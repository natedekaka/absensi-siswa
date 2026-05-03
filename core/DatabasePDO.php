<?php
/**
 * PDO Database Wrapper (Singleton Pattern)
 * Replaces mysqli-based Database class with PDO for better portability
 */

require_once __DIR__ . '/config.php';

class DatabasePDO {
    private static $instance = null;
    private $pdo;
    private $config;
    
    private function __construct() {
        $this->config = [
            'host' => DB_HOST,
            'port' => DB_PORT,
            'dbname' => DB_NAME,
            'user' => DB_USER,
            'pass' => DB_PASS
        ];
        
        $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['dbname']};charset=utf8mb4";
        
        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config['user'],
                $this->config['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Set timezone
            $this->pdo->exec("SET time_zone = '+07:00'");
            
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function query($sql) {
        return $this->pdo->query($sql);
    }
    
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
    
    public function escape($string) {
        // PDO doesn't need escape when using prepared statements
        // This is kept for backward compatibility but should not be used
        return addslashes($string);
    }
    
    public function getLastId() {
        return $this->pdo->lastInsertId();
    }
    
    public function getAffectedRows() {
        // PDO doesn't track affected rows globally
        // Return 0 as placeholder
        return 0;
    }
    
    // New PDO-specific methods
    public function fetchAll($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Backward compatibility functions
function dbPDO() {
    return DatabasePDO::getInstance();
}

function connPDO() {
    return dbPDO()->getConnection();
}
