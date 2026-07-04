<?php

class Database {
    private $host;
    private $db_name;
    private $user;
    private $password;
    private $pdo;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'palmoil_trading';
        $this->user = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
    }

    public function connect() {
        try {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';
            $this->pdo = new PDO(
                $dsn,
                $this->user,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            return $this->pdo;
        } catch (PDOException $e) {
            die('Database Connection Error: ' . $e->getMessage());
        }
    }

    public function getConnection() {
        if (!$this->pdo) {
            $this->connect();
        }
        return $this->pdo;
    }

    public function query($sql) {
        return $this->getConnection()->query($sql);
    }

    public function prepare($sql) {
        return $this->getConnection()->prepare($sql);
    }

    public function execute($stmt, $params = []) {
        return $stmt->execute($params);
    }

    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }

    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    public function commit() {
        return $this->getConnection()->commit();
    }

    public function rollback() {
        return $this->getConnection()->rollBack();
    }
}
