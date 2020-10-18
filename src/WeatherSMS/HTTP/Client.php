<?php
namespace WeatherSMS\HTTP;

use InvalidArgumentException;

class Client implements ClientInterface {
    
    public function post($url, $postfields, $headers = []) : ResponseInterface {
        if (empty($url)) {
            throw new InvalidArgumentException("url argument is empty");
        }
        
        if (empty($postfields)) {
            throw new InvalidArgumentException("postdata argument is empty");
        }
        
        $ch = curl_init($url);
        
        if (!$ch) {
            throw new \Exception("Failed to initialize curl");
        }
        
        $options = [
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_RETURNTRANSFER => true
        ];
        
        return $this->exec($ch, $options, $headers);
    }
    
    public function get($url, $headers = []) : ResponseInterface {
        if (empty($url)) {
            throw new InvalidArgumentException("url argument is empty");
        }
        
        $ch = curl_init($url);
        
        if (!$ch) {
            throw new \Exception("Failed to initialize curl");
        }
        
        $options = [
            CURLOPT_RETURNTRANSFER => 1
        ];
        
        return $this->exec($ch, $options, $headers);
    }
    
    private function exec($ch, $options, $headers = []) : ResponseInterface {
        if (!empty($headers)) {
            $flatHeaders = array_map(function($v, $k) {
                return "$k: $v";
            }, $headers, array_keys($headers));
            $options[CURLOPT_HTTPHEADER] = $flatHeaders;
        }
        
        $options[CURLOPT_HEADER] = true;
        $setOptResult = curl_setopt_array($ch, $options);
        if (!$setOptResult) {
            throw new \Exception("Failed to set curl options");
        }
        $response = curl_exec($ch);
        if ($response === false) {
            throw new \Exception("Curl error ".curl_error($ch), curl_errno($ch));
        }
        
        return $this->createResponse($response);
    }
    
    private function createResponse(string $responseContent) : ResponseInterface {
        if (empty($responseContent)) {
            throw new \Exception("Response is empty");
        }
        // split response
        $crlf = "\r\n";
        $bodyDelimiter = "$crlf$crlf";
        if (!strpos($responseContent, $bodyDelimiter)) {
            throw new \Exception("Response is malformed: no body delimiter");
        }
        
        list($headers, $body) = explode($bodyDelimiter, $responseContent, 2);
        // parse headers
        $headersArray = explode($crlf, $headers);
        $status = array_shift($headersArray);
        if (substr_count($status, " ") < 1) {
            throw new \Exception("Response is malformed: bad status string");
        }
        list(,$statusCode,) = explode(" ", $status, 3);
        
        $parsedHeaders = [];
        foreach ($headersArray as $header) {
            list($name, $value) = explode(": ", $header);
            $parsedHeaders[$name] = $value;
        }
            
        $response = new \WeatherSMS\HTTP\Response($statusCode, $body,
                $parsedHeaders);
        $reasonPhrase = $response->getReasonPhrase();
        if ($response->codeClassIs(5)) {
            throw new ServerErrorException($reasonPhrase, $statusCode, 
                    $response);
        }
        if ($response->codeClassIs(4)) {
            throw new ClientErrorException($reasonPhrase, $statusCode, 
                    $response);
        }

        return $response;
    }
    

}
