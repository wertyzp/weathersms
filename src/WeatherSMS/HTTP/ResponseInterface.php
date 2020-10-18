<?php

namespace WeatherSMS\HTTP;

interface ResponseInterface {
    public function getStatusCode() : int;
    public function getHeader($name) : ?string;
    public function getHeaders() : array;
    public function getBody() : string;
    public function codeClassIs(int $codeClass);
}
