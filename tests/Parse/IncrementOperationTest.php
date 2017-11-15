<?php
/**
 * Class IncrementOperationTest | Parse/Test/IncrementOperationTest.php
 */

namespace Parse\Test;

use Parse\Internal\AddOperation;
use Parse\Internal\DeleteOperation;
use Parse\Internal\IncrementOperation;
use Parse\Internal\SetOperation;

class IncrementOperationTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    /**
     * @group increment-op
     */
    public function testIncrementOperation()
    {
        $addOp = new IncrementOperation(32);
        $this->assertEquals(32, $addOp->getValue());
    }

    /**
     * @group increment-op
     */
    public function testMergePrevious()
    {
        $addOp = new IncrementOperation();

        $this->assertEquals($addOp, $addOp->_mergeWithPrevious(null));

        // check delete op
        $merged = $addOp->_mergeWithPrevious(new DeleteOperation());
        $this->assertTrue($merged instanceof SetOperation);

        // check set op
        $merged = $addOp->_mergeWithPrevious(new SetOperation(12));
        $this->assertTrue($merged instanceof SetOperation);
        $this->assertEquals(13, $merged->getValue(), 'Value was not as expected');

        // check self
        $merged = $addOp->_mergeWithPrevious(new IncrementOperation(32));
        $this->assertTrue($merged instanceof IncrementOperation);
        $this->assertEquals(33, $merged->getValue(), 'Value was not as expected');
    }

    /**
     * @group increment-op
     */
    public function testInvalidMerge()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'Operation is invalid after previous operation.'
        );
        $addOp = new IncrementOperation();
        $addOp->_mergeWithPrevious(new AddOperation(['key'  => 'value']));
    }
}
