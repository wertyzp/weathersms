<?php

namespace WeatherSMS;

use \WeatherSMS\API\Routee\Client as RouteeClient;
use \WeatherSMS\API\OpenWeatherMap\Client as OpenWeatherMapClient;
use \WeatherSMS\HTTP\Client as HTTPClient;

class Application {
    private const CONFIG_FILE = "etc/config.ini";
    public function run() {
        try {
            do {
                $this->_run();
                // sleep 10 seconds
                sleep(60*10);
                // can be stopped with ctrl-c (SIGINT)
            } while(true);
        } catch (\Exception $ex) {
            // default handler just display
            echo $ex;
        }
    }
    
    private function _run() {
        $configFile = self::CONFIG_FILE;
        $config = parse_ini_file($configFile, true);
        $httpClient = new HTTPClient();
        $openWeatherMapClient = 
                $this->createOpenWeatherMapClient($httpClient, $config);
        $routeeClient = $this->createRouteeClient($httpClient, $config);
        $city = $config['global']['city'];
        $weather = $openWeatherMapClient->getWeather($city);
        $temperature = $this->getTemperature($weather);
        $to = $config['global']['to'];
        $from = $config['global']['from'];
        $format = $config['global']['template'];
        $body = sprintf($format, $temperature > 20 ? "more" : "less or equal", $temperature);
        $routeeClient->sms($to, $body, $from);
    }
    
    private function getTemperature($weather) : float {
        if (!$weather->main) {
            throw new \Exception("Unexpected weather response");
        }
        $temp = $weather->main->temp;
        return $temp;
    }
    
    public function createOpenWeatherMapClient($httpClient, 
            $config) : OpenWeatherMapClient {
        $apiKey = $config['openweathermap.org']['api_key'];
        return new OpenWeatherMapClient($apiKey, $httpClient);
    }
    
    private function createRouteeClient($httpClient, $config) : RouteeClient {
        $applicationId = $config['routee.net']['application_id'];
        $applicationSecret = $config['routee.net']['application_secret'];
        return new RouteeClient($applicationId, $applicationSecret, 
                $httpClient);
    }
    
}


