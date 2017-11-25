<?php
/**
 * Class ParseSessionStorageAltTest | Parse/Test/ParseSessionStorageAltTest.php
 */

namespace Parse\Test;

use Parse\ParseSessionStorage;

class ParseSessionStorageAltTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group session-storage-not-active
     */
    public function testNoSessionActive()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'PHP session_start() must be called first.'
        );
        new ParseSessionStorage();
    }
}
