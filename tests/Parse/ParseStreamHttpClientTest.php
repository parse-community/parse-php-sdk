<?php
/**
 * Created by PhpStorm.
 * User: Bfriedman
 * Date: 2/20/17
 * Time: 1:11 PM
 */

namespace Parse\Test;

use Parse\HttpClients\ParseStreamHttpClient;
use Parse\ParseClient;
use Parse\ParseException;

class ParseStreamHttpClientTest extends \PHPUnit_Framework_TestCase
{
    public function testGetResponse()
    {
        $client = new ParseStreamHttpClient();
        $client->send('http://example.com');

        // get response code
        $this->assertEquals(200, $client->getResponseStatusCode());

        // get response headers
        $headers = $client->getResponseHeaders();

        $this->assertEquals('HTTP/1.0 200 OK', $headers['http_code']);
    }

    public function testInvalidUrl()
    {
        $url = 'http://example.com/lots of spaces here';

        $this->setExpectedException(
            '\Parse\ParseException',
            'Url may not contain spaces for stream client: '
            .$url
        );

        $client = new ParseStreamHttpClient();
        $client->send($url);
    }
}
