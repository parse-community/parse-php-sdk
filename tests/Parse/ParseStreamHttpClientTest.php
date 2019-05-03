<?php
/**
 * Class ParseStreamHttpClientTest | Parse/Test/ParseStreamHttpClientTest.php
 */

namespace Parse\Test;

use Parse\HttpClients\ParseStreamHttpClient;

use PHPUnit\Framework\TestCase;

class ParseStreamHttpClientTest extends TestCase
{
    /**
     * @group test-get-response
     */
    public function testGetResponse()
    {
        $client = new ParseStreamHttpClient();
        $client->send('http://example.com');

        // get response code
        $this->assertEquals(200, $client->getResponseStatusCode());

        // get response headers
        $headers = $client->getResponseHeaders();

        $this->assertTrue(preg_match('|HTTP/1\.\d\s200\sOK|', $headers['http_code']) === 1);
    }

    public function testInvalidUrl()
    {
        $url = 'http://example.com/lots of spaces here';

        $this->expectException(
            '\Parse\ParseException',
            'Url may not contain spaces for stream client: '
            .$url
        );

        $client = new ParseStreamHttpClient();
        $client->send($url);
    }
}
