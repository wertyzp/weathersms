<?php

namespace WeatherSMS\HTTP;

class ClientErrorException extends \Exception {
    private $response;
    
    public function __construct(string $message, int $code,
            ResponseInterface $response) {
        $this->response = $response;
        parent::__construct($message, $code);
    }
    
    public function getResponse(): ResponseInterface {
        return $this->response;
    }

}