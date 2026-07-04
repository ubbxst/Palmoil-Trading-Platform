<?php

namespace App\Trading;

use App\MT5\MT5API;
use App\Database\Connection;
use Exception;

class Market {
    private $mt5;
    private $db;

    public function __construct($mt5_config = null) {
        if ($mt5_config) {
            $this->mt5 = new MT5API($mt5_config);
        }
        $this->db = Connection::getInstance()->getConnection();
    }

    public function getMarketData($symbol) {
        try {
            $cached = $this->getFromCache($symbol);
            if ($cached && time() - strtotime($cached['updated_at']) < 60) {
                return $cached;
            }

            if ($this->mt5) {
                $data = $this->mt5->getSymbolInfo($symbol);
                $this->cacheMarketData($symbol, $data);
                return $data;
            }

            return null;
        } catch (Exception $e) {
            return $this->getFromCache($symbol);
        }
    }

    public function getMultipleSymbols($symbols) {
        $data = [];
        foreach ($symbols as $symbol) {
            $data[$symbol] = $this->getMarketData($symbol);
        }
        return $data;
    }

    public function getPriceHistory($symbol, $period = 'D1', $count = 100) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM price_history 
                WHERE symbol = ? AND period = ?
                ORDER BY timestamp DESC
                LIMIT ?
            ");
            $stmt->execute([$symbol, $period, $count]);
            return array_reverse($stmt->fetchAll());
        } catch (Exception $e) {
            throw new Exception("Failed to fetch price history: " . $e->getMessage());
        }
    }

    private function cacheMarketData($symbol, $data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO market_data (symbol, data, updated_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
            ");
            return $stmt->execute([$symbol, json_encode($data)]);
        } catch (Exception $e) {
            error_log("Cache error: " . $e->getMessage());
        }
    }

    private function getFromCache($symbol) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM market_data WHERE symbol = ?");
            $stmt->execute([$symbol]);
            $result = $stmt->fetch();

            if ($result) {
                $result['data'] = json_decode($result['data'], true);
            }
            return $result;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getTrendingSymbols($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT symbol, COUNT(*) as volume
                FROM orders
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY symbol
                ORDER BY volume DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Failed to fetch trending symbols: " . $e->getMessage());
        }
    }

    public function getMarketOverview() {
        try {
            $symbols = ['PALMOIL', 'CRUDE_OIL', 'GOLD', 'EUR_USD'];
            $overview = [];

            foreach ($symbols as $symbol) {
                $overview[$symbol] = $this->getMarketData($symbol);
            }
            return $overview;
        } catch (Exception $e) {
            throw new Exception("Failed to get market overview: " . $e->getMessage());
        }
    }
}
