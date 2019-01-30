<?php
/**
 * Class HttpClientMock | Parse/Test/HttpClientMock.php
 */

namespace Parse\Test;

use Parse\HttpClients\ParseCurlHttpClient;

class HttpClientMock extends ParseCurlHttpClient
{
    private $response = '';

    public function send($url, $method = 'GET', $data = array())
    {
        return $this->response;
    }

    public function setResponse($resp)
    {
        $this->response = $resp;
    }
}
