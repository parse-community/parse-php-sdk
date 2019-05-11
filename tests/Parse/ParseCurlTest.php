<?php
/**
 * Class ParseCurlTest | Parse/Test/ParseCurlTest.php
 */

namespace Parse\Test;

use Parse\HttpClients\ParseCurl;

use PHPUnit\Framework\TestCase;

class ParseCurlTest extends TestCase
{
    public function testBadExec()
    {
        $this->expectException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->exec();
    }

    public function testBadSetOption()
    {
        $this->expectException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->setOption(1, 1);
    }

    public function testBadSetOptionsArray()
    {
        $this->expectException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->setOptionsArray([]);
    }

    public function testBadGetInfo()
    {
        $this->expectException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->getInfo(1);
    }

    public function testBadGetError()
    {
        $this->expectException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->getError();
    }

    public function testBadErrorCode()
    {
        $this->expectException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->getErrorCode();
    }

    public function testBadClose()
    {
        $this->expectException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->close();
    }
}
