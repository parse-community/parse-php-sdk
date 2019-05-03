<?php
/**
 * Class ParseSessionStorageAltTest | Parse/Test/ParseSessionStorageAltTest.php
 */

namespace Parse\Test;

use Parse\ParseSessionStorage;

use PHPUnit\Framework\TestCase;

class ParseSessionStorageAltTest extends TestCase
{
    /**
     * @group session-storage-not-active
     */
    public function testNoSessionActive()
    {
        $this->expectException(
            '\Parse\ParseException',
            'PHP session_start() must be called first.'
        );
        new ParseSessionStorage();
    }
}
