<?php

namespace WeatherSMS\API\Routee;

use \WeatherSMS\HTTP\ClientInterface as HTTPClientInterface;

class Client {
    private const AUTH_ENDPOINT = "https://auth.routee.net/oauth/token";
    private const AUTH_TYPE = 'Basic';
    
    private const ENDPOINT = "https://connect.routee.net";
    
    private $authString;
    private $httpClient;
    private $accessToken;
    private $tokenType;
    private $expiresIn;
    private $tokenTimestamp;
    
    public function __construct(
            string $applicationId,
            string $applicationSecret,
            HTTPClientInterface $httpClient) {
        $this->httpClient = $httpClient;
        $this->authString = base64_encode("$applicationId:$applicationSecret");
    }
    
    private function checkFieldsPresent($data, ...$fields) : void {
        $absentFields = [];
        foreach ($fields as $field) {
            if (!isset($data->$field)) {
                $absentFields[] = $field;
            }
        }
        if (!empty($absentFields)) {
            $fieldList = implode(",", $absentFields);
            $message = "Fields $fieldList are not present in response";
            throw new \Exception($message);
        }
    }
    
    private function getHeaders() : array {
        return [
            'Authorization' => "Bearer $this->accessToken",
            'Content-Type' => 'application/json',
            'Expect' => ''
        ];
    }

    private function checkToken() : void {
        if (empty($this->accessToken)) {
            $this->updateToken();
            return;
        }
        $expired = ($this->tokenTimestamp + $this->expiresIn) > time();
        if ($expired) {
            $this->updateToken();
        }
    }

    private function updateToken() : void {
        /*
        curl --request POST \
  --url https://auth.routee.net/oauth/token \
  --header 'authorization: Basic NTc1NmE0MTFlNGIwNmEzM2Q1MDUxN2M3OnZiNlFwakNJT0c=' \
  --header 'content-type: application/x-www-form-urlencoded' \
  --data grant_type=client_credentials
         * 
         */
        $url = self::AUTH_ENDPOINT;
        $authType = self::AUTH_TYPE;
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => "$authType $this->authString"
        ];
        $postfields = "grant_type=client_credentials";
        $tokenTimestamp = time();
        $response = $this->httpClient->post($url, $postfields, $headers);
        $data = json_decode($response->getBody());
        if (!is_object($data)) {
            throw new \Exception("Invalid token response");
        }
        $this->checkFieldsPresent($data, 'access_token', 'token_type', 
                'expires_in');
        $this->accessToken = $data->access_token;
        $this->tokenType = $data->token_type;
        $this->expiresIn = $data->expires_in;
        $this->tokenTimestamp = $tokenTimestamp;
    }
    
    public function sms(string $to, string $body, string $from) : void {
        $this->checkToken();
        $url = self::ENDPOINT . "/sms";
        $data = [
            'body' => $body,
            'to' => $to,
            'from' => $from
        ];
        $response = $this->httpClient->post($url, json_encode($data), $this->getHeaders());
        if (!$response->codeClassIs(2)) { // 4xx and 5xx handled by http client
            throw new \Exception("Unexpected service behaviour");
        }
    }
    
}
