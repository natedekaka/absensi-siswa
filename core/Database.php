<?php

class Database {
    private static $instance = null;
    private $connection;

    private $host = 'db:3306';
    private $user = 'root';
    private $pass = 'rootpass';
    private $db = 'absensi_siswa';

    private function __construct() {
        $this->connection = new mysqli($this->host, $this->user, $this->pass, $this->db);
        
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
