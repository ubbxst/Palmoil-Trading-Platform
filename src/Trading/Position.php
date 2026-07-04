<?php

namespace App\Trading;

use App\Database\Connection;
use Exception;

class Position {
    private $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function open($user_id, $symbol, $type, $volume, $entry_price, $sl = null, $tp = null, $mt5_ticket = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO positions (user_id, symbol, type, volume, entry_price, stoploss, takeprofit, mt5_ticket, status, opened_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open', NOW())
            ");
            return $stmt->execute([$user_id, $symbol, $type, $volume, $entry_price, $sl, $tp, $mt5_ticket]);
        } catch (Exception $e) {
            throw new Exception("Position opening failed: " . $e->getMessage());
        }
    }

    public function getOpenPositions($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM positions 
                WHERE user_id = ? AND status = 'open'
                ORDER BY opened_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Failed to fetch positions: " . $e->getMessage());
        }
    }

    public function getPositionById($position_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM positions WHERE id = ?");
            $stmt->execute([$position_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Failed to fetch position: " . $e->getMessage());
        }
    }

    public function updatePrice($position_id, $current_price) {
        try {
            $position = $this->getPositionById($position_id);
            
            if (!$position) {
                throw new Exception("Position not found");
            }

            $unrealized_profit = ($current_price - $position['entry_price']) * $position['volume'];
            if ($position['type'] === 'SELL') {
                $unrealized_profit = ($position['entry_price'] - $current_price) * $position['volume'];
            }

            $stmt = $this->db->prepare("
                UPDATE positions 
                SET current_price = ?, unrealized_profit = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$current_price, $unrealized_profit, $position_id]);
        } catch (Exception $e) {
            throw new Exception("Failed to update position: " . $e->getMessage());
        }
    }

    public function close($position_id, $exit_price) {
        try {
            $position = $this->getPositionById($position_id);
            
            if (!$position) {
                throw new Exception("Position not found");
            }

            $profit = ($exit_price - $position['entry_price']) * $position['volume'];
            if ($position['type'] === 'SELL') {
                $profit = ($position['entry_price'] - $exit_price) * $position['volume'];
            }

            $stmt = $this->db->prepare("
                UPDATE positions 
                SET status = 'closed', exit_price = ?, realized_profit = ?, closed_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$exit_price, $profit, $position_id]);
        } catch (Exception $e) {
            throw new Exception("Failed to close position: " . $e->getMessage());
        }
    }

    public function modifyLevels($position_id, $sl = null, $tp = null) {
        try {
            $updates = [];
            $values = [];

            if ($sl !== null) {
                $updates[] = "stoploss = ?";
                $values[] = $sl;
            }

            if ($tp !== null) {
                $updates[] = "takeprofit = ?";
                $values[] = $tp;
            }

            if (empty($updates)) {
                return false;
            }

            $updates[] = "updated_at = NOW()";
            $values[] = $position_id;

            $sql = "UPDATE positions SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (Exception $e) {
            throw new Exception("Failed to modify position: " . $e->getMessage());
        }
    }

    public function getStats($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(CASE WHEN status = 'open' THEN 1 END) as open_positions,
                    COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_positions,
                    SUM(CASE WHEN status = 'open' THEN unrealized_profit ELSE 0 END) as total_unrealized,
                    SUM(CASE WHEN status = 'closed' THEN realized_profit ELSE 0 END) as total_realized,
                    SUM(CASE WHEN status = 'closed' AND realized_profit > 0 THEN 1 ELSE 0 END) as winning_positions
                FROM positions
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Failed to get statistics: " . $e->getMessage());
        }
    }
}
