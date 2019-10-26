<?php

namespace Id4me\Test\Mock;

use GuzzleHttp\Client;
use Id4me\RP\HttpClient;

class HttpClientGuzzle implements HttpClient {

    public function get($url, array $headers = [])
    {
        $httpClient = new Client();

        $options = [
            'headers' => $headers
        ];        
        
        $result = $httpClient->get($url, $options);

        return $result->getBody()->getContents();

    }

    public function post($url, $body, array $headers = [])
    {
        $httpClient = new Client();

        $options = [
            'headers' => $headers,
            'body' => $body
        ];

        $result = $httpClient->post($url, $options);

        return $result->getBody()->getContents();
    }
}

