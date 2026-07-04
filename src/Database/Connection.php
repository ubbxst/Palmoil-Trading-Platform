<?php

namespace App\Database;

use PDO;
use PDOException;

class Connection {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            $dsn = 'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4';
            $this->pdo = new PDO(
                $dsn,
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die('Database Connection Error: ' . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function __clone() {
        throw new \Exception("Cannot clone singleton");
    }

    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
