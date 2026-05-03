<?php

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;

    private $user = DB_USER;
    private $pass = DB_PASS;
    private $db = DB_NAME;

    private function __construct() {
        $host = DB_HOST;
        $port = (int) DB_PORT;
        
        if ($host === 'localhost') {
            $host = '127.0.0.1';
        }
        
        $this->connection = new mysqli($host, $this->user, $this->pass, $this->db, $port);
        
        if ($this->connection->connect_error) {
            die("Koneksi gagal: " . $this->connection->connect_error);
        }

        $this->connection->query("SET time_zone = '+07:00'");
        $this->connection->set_charset("utf8mb4");
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql) {
        return $this->connection->query($sql);
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    public function getLastId() {
        return $this->connection->insert_id;
    }

    public function getAffectedRows() {
        return $this->connection->affected_rows;
    }

    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

function db() {
    return Database::getInstance();
}

function conn() {
    return db()->getConnection();
}
