<?php
/**
 * Created by PhpStorm.
 * User: Bfriedman
 * Date: 1/30/17
 * Time: 12:26 AM
 */

namespace Parse\Test;

use Parse\ParseException;
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
