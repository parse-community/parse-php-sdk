<?php
/**
 * Class ParseStreamHttpClientTest | Parse/Test/ParseStreamHttpClientTest.php
 */

namespace Parse\Test;

use Parse\HttpClients\ParseStreamHttpClient;
use Parse\HttpClients\ParseStream;
use Parse\ParseException;

use PHPUnit\Framework\TestCase;

class ParseStreamHttpClientTest extends TestCase
{
    /**
     * @group test-get-response
     */
    public function testGetResponse()
    {
        $client = new ParseStreamHttpClient();
        $client->send('https://example.org');

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

    /**
     * @group test-stream-context-error
     */
    public function testStreamContextError()
    {
        $client = $this->getMockBuilder(ParseStream::class)
            ->onlyMethods(['getFileContents'])
            ->getMock();
        
        $client->expects($this->once())
            ->method('getFileContents')
            ->willThrowException(new ParseException('Cannot retrieve data.', 1));

        $client->get('https://example.org');

        $this->assertEquals('Cannot retrieve data.', $client->getErrorMessage());
        $this->assertEquals('1', $client->getErrorCode());
    }
}
