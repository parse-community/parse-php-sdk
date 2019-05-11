<?php
/**
 * Class ParseCurlHttpClientTest | Parse/Test/ParseCurlHttpClientTest.php
 */

namespace Parse\Test;

use Parse\HttpClients\ParseCurlHttpClient;

use PHPUnit\Framework\TestCase;

class ParseCurlHttpClientTest extends TestCase
{
    public function testResponseStatusCode()
    {
        if (function_exists('curl_init')) {
            $client = new ParseCurlHttpClient();
            $client->setup();
            $client->send("http://example.com");

            $this->assertEquals(200, $client->getResponseStatusCode());
        }
    }
}
