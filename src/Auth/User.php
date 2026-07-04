<?php

namespace App\Auth;

use App\Database\Connection;
use Exception;

class User {
    private $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function register($email, $password, $first_name, $last_name, $phone) {
        try {
            if ($this->getUserByEmail($email)) {
                throw new Exception("Email already registered");
            }

            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $this->db->prepare("INSERT INTO users (email, password, first_name, last_name, phone, balance, status, created_at) VALUES (?, ?, ?, ?, ?, 0, 'active', NOW())");
            return $stmt->execute([$email, $hashed_password, $first_name, $last_name, $phone]);
        } catch (Exception $e) {
            throw new Exception("Registration failed: " . $e->getMessage());
        }
    }

    public function login($email, $password) {
        try {
            $user = $this->getUserByEmail($email);

            if (!$user) {
                throw new Exception("Invalid credentials");
            }

            if (!password_verify($password, $user['password'])) {
                throw new Exception("Invalid credentials");
            }

            if ($user['status'] !== 'active') {
                throw new Exception("Account is " . $user['status']);
            }

            $this->updateLastLogin($user['id']);
            return $user;
        } catch (Exception $e) {
            throw new Exception("Login failed: " . $e->getMessage());
        }
    }

    public function getUserByEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Failed to fetch user: " . $e->getMessage());
        }
    }

    public function getUserById($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Failed to fetch user: " . $e->getMessage());
        }
    }

    public function updateBalance($user_id, $amount) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET balance = balance + ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$amount, $user_id]);
        } catch (Exception $e) {
            throw new Exception("Failed to update balance: " . $e->getMessage());
        }
    }

    public function getBalance($user_id) {
        try {
            $user = $this->getUserById($user_id);
            return $user ? $user['balance'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    public function updateLastLogin($user_id) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            return $stmt->execute([$user_id]);
        } catch (Exception $e) {
            return false;
        }
    }
}
