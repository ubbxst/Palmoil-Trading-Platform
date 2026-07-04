<?php

namespace App\Paystack;

use Exception;

class PaymentProcessor {
    private $secret_key;
    private $base_url = 'https://api.paystack.co';

    public function __construct($secret_key) {
        $this->secret_key = $secret_key;
    }

    public function initializePayment($email, $amount, $reference = null) {
        $amount_in_kobo = $amount * 100;
        
        $data = [
            'email' => $email,
            'amount' => $amount_in_kobo,
            'reference' => $reference ?? uniqid('palmoil_')
        ];

        $response = $this->makeRequest('transaction/initialize', $data);
        return $response;
    }

    public function verifyPayment($reference) {
        $endpoint = 'transaction/verify/' . $reference;
        $response = $this->makeRequest($endpoint, [], 'GET');
        return $response;
    }

    public function getPaymentDetails($reference) {
        return $this->verifyPayment($reference);
    }

    public function createPlan($name, $description, $amount, $interval) {
        $amount_in_kobo = $amount * 100;
        
        $data = [
            'name' => $name,
            'description' => $description,
            'amount' => $amount_in_kobo,
            'interval' => $interval
        ];

        return $this->makeRequest('plan', $data);
    }

    private function makeRequest($endpoint, $data = [], $method = 'POST') {
        $url = $this->base_url . '/api/' . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->secret_key,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Payment API Error: ' . $error);
        }

        $decoded = json_decode($response, true);
        
        if ($http_code !== 200 && !isset($decoded['status'])) {
            throw new Exception('Payment processing failed: ' . ($decoded['message'] ?? 'Unknown error'));
        }

        return $decoded;
    }
}
