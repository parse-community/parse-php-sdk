<?php
/**
 * Class ParseCurlTest | Parse/Test/ParseCurlTest.php
 */

namespace Parse\Test;

use Parse\HttpClients\ParseCurl;

class ParseCurlTest extends \PHPUnit_Framework_TestCase
{
    public function testBadExec()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->exec();
    }

    public function testBadSetOption()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->setOption(1, 1);
    }

    public function testBadSetOptionsArray()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->setOptionsArray([]);
    }

    public function testBadGetInfo()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->getInfo(1);
    }

    public function testBadGetError()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->getError();
    }

    public function testBadErrorCode()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->getErrorCode();
    }

    public function testBadClose()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'You must call ParseCurl::init first'
        );

        $parseCurl = new ParseCurl();
        $parseCurl->close();
    }
}
