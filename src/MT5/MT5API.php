<?php

namespace App\MT5;

use Exception;

class MT5API {
    private $host;
    private $port;
    private $login;
    private $password;
    private $server;
    private $timeout;
    private $socket;

    public function __construct($config) {
        $this->host = $config['host'] ?? 'localhost';
        $this->port = $config['port'] ?? 5000;
        $this->login = $config['login'];
        $this->password = $config['password'];
        $this->server = $config['server'];
        $this->timeout = $config['timeout'] ?? 10;
    }

    public function connect() {
        try {
            $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
            
            if (!$this->socket) {
                throw new Exception("Connection Error: $errstr ($errno)");
            }

            stream_set_timeout($this->socket, $this->timeout);
            return true;
        } catch (Exception $e) {
            throw new Exception("MT5 Connection Failed: " . $e->getMessage());
        }
    }

    public function disconnect() {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function authenticate() {
        $auth_data = [
            'action' => 'login',
            'login' => $this->login,
            'password' => $this->password,
            'server' => $this->server
        ];
        return $this->sendCommand($auth_data);
    }

    public function getAccountInfo() {
        $command = ['action' => 'account_info'];
        return $this->sendCommand($command);
    }

    public function getSymbolInfo($symbol) {
        $command = [
            'action' => 'symbol_info',
            'symbol' => $symbol
        ];
        return $this->sendCommand($command);
    }

    public function placeOrder($symbol, $type, $volume, $price, $sl = 0, $tp = 0, $comment = '') {
        $command = [
            'action' => 'order_send',
            'symbol' => $symbol,
            'type' => $type,
            'volume' => $volume,
            'price' => $price,
            'stoploss' => $sl,
            'takeprofit' => $tp,
            'comment' => $comment
        ];
        return $this->sendCommand($command);
    }

    public function getPositions() {
        $command = ['action' => 'positions_get'];
        return $this->sendCommand($command);
    }

    public function closePosition($ticket) {
        $command = [
            'action' => 'position_close',
            'ticket' => $ticket
        ];
        return $this->sendCommand($command);
    }

    public function getOrderHistory($from = 0, $to = null) {
        $command = [
            'action' => 'history_get',
            'from' => $from,
            'to' => $to ?? time()
        ];
        return $this->sendCommand($command);
    }

    public function getDealHistory($from = 0, $to = null) {
        $command = [
            'action' => 'deals_get',
            'from' => $from,
            'to' => $to ?? time()
        ];
        return $this->sendCommand($command);
    }

    public function getTicks($symbol, $count = 100) {
        $command = [
            'action' => 'ticks_get',
            'symbol' => $symbol,
            'count' => $count
        ];
        return $this->sendCommand($command);
    }

    private function sendCommand($data) {
        if (!$this->socket) {
            throw new Exception("Not connected to MT5 server");
        }

        $json = json_encode($data);
        $message = $json . "\n";

        if (!fwrite($this->socket, $message)) {
            throw new Exception("Failed to send command to MT5 server");
        }

        $response = fgets($this->socket, 4096);
        
        if ($response === false) {
            throw new Exception("Failed to receive response from MT5 server");
        }

        return json_decode(trim($response), true);
    }

    public function streamPrices($symbols, $callback) {
        if (!$this->socket) {
            throw new Exception("Not connected to MT5 server");
        }

        $command = [
            'action' => 'stream_start',
            'symbols' => $symbols
        ];

        fwrite($this->socket, json_encode($command) . "\n");

        while (true) {
            $line = fgets($this->socket);
            if ($line === false) break;

            $data = json_decode(trim($line), true);
            if ($data) {
                call_user_func($callback, $data);
            }
        }
    }
}
