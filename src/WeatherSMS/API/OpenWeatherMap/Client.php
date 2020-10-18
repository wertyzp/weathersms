<?php

namespace WeatherSMS\API\OpenWeatherMap;

use \WeatherSMS\HTTP\ClientInterface as HTTPClientInterface;

class Client {
    /**
     *
     * @var HTTPClientInterface 
     */
    private $httpClient;
    
    /**
     *
     * @var string
     */
    private $apiKey;
    
    private const ENDPOINT = "https://api.openweathermap.org/data/";
    private const VERSION = "2.5";
    
    public function __construct(
            string $apiKey,
            HTTPClientInterface $httpClient) {
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;
    }
    
    private function getUrl(string $path, array $query) : string {
        $endpoint = self::ENDPOINT;
        $version = self::VERSION;
        return "$endpoint/$version/$path?" . http_build_query($query);
    }
    
    public function getWeather($cityName) : object {
        $path = "weather";
        $query = [
            'q' => $cityName,
            'appid' =>$this->apiKey,
            'units' => 'metric'
        ];
        $url = $this->getUrl($path, $query);
        $response = $this->httpClient->get($url);
        if (!$response->codeClassIs(2)) { // 4xx and 5xx handled by http client
            throw new \Exception("Unexpected service behaviour");
        }
        return json_decode($response->getBody());
    }
}
