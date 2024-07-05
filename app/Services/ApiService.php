<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ApiService
{
    // protected $baseUrl;

    // public function __construct($baseUrl)
    // {
    //     $this->baseUrl = $baseUrl;
    // }

    // public function get($endpoint, $params = [])
    // {

    //     $url = $this->baseUrl.$endpoint;

    //     $response = Http::get($url, $params);

    //     return $response->json();
    // }

    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('game.api.url');
    }

    public function get($endpoint, $params = [], $maxRetries = 5)
    {
        return $this->makeRequest('get', $endpoint, $params, $maxRetries);
    }

    public function post($endpoint, $data = [], $maxRetries = 5)
    {
        return $this->makeRequest('post', $endpoint, $data, $maxRetries);
    }

    protected function makeRequest($method, $endpoint, $data, $maxRetries)
    {
        $attempt = 0;
        $backoff = 1;
        $url = $this->baseUrl.$endpoint;

        while ($attempt < $maxRetries) {
            $response = Http::$method($url, $data);

            if ($response->status() == 429) {
                sleep($backoff);
                $backoff *= 2;
                $attempt++;
            } else {
                return $response->json();
            }
        }

        return [
            'status_code' => 429,
            'error' => 'Too many requests',
        ];
    }
}
