<?php

namespace App\Trading;

use App\Database\Connection;
use Exception;

class Order {
    private $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function create($user_id, $symbol, $type, $volume, $entry_price, $sl = null, $tp = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO orders (user_id, symbol, type, volume, entry_price, stoploss, takeprofit, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            return $stmt->execute([$user_id, $symbol, $type, $volume, $entry_price, $sl, $tp]);
        } catch (Exception $e) {
            throw new Exception("Order creation failed: " . $e->getMessage());
        }
    }

    public function getUserOrders($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM orders 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Failed to fetch orders: " . $e->getMessage());
        }
    }

    public function getOrderById($order_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Failed to fetch order: " . $e->getMessage());
        }
    }

    public function updateStatus($order_id, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$status, $order_id]);
        } catch (Exception $e) {
            throw new Exception("Failed to update order: " . $e->getMessage());
        }
    }

    public function closeOrder($order_id, $exit_price) {
        try {
            $order = $this->getOrderById($order_id);
            
            if (!$order) {
                throw new Exception("Order not found");
            }

            $profit = ($exit_price - $order['entry_price']) * $order['volume'];
            if ($order['type'] === 'SELL') {
                $profit = ($order['entry_price'] - $exit_price) * $order['volume'];
            }

            $stmt = $this->db->prepare("
                UPDATE orders 
                SET status = 'closed', exit_price = ?, profit_loss = ?, closed_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$exit_price, $profit, $order_id]);
        } catch (Exception $e) {
            throw new Exception("Failed to close order: " . $e->getMessage());
        }
    }

    public function cancelOrder($order_id) {
        return $this->updateStatus($order_id, 'cancelled');
    }

    public function getStats($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN profit_loss > 0 THEN 1 ELSE 0 END) as winning_orders,
                    SUM(profit_loss) as total_profit_loss,
                    AVG(profit_loss) as avg_profit_loss
                FROM orders
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Failed to get statistics: " . $e->getMessage());
        }
    }
}
