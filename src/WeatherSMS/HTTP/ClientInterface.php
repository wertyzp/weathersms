<?php

namespace WeatherSMS\HTTP;

interface ClientInterface {
    public function post($url, $postfields, $headers = []) : ResponseInterface;
    public function get($url, $headers = []) : ResponseInterface;
}
