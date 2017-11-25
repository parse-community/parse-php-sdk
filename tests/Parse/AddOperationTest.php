<?php
/**
 * Class AddOperationTest | Parse/Test/AddOperationTest.php
 */

namespace Parse\Test;

use Parse\Internal\AddOperation;
use Parse\Internal\DeleteOperation;
use Parse\Internal\SetOperation;

class AddOperationTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    /**
     * @group add-op
     */
    public function testAddOperation()
    {
        $objects = [
            'key'   => 'val'
        ];
        $addOp = new AddOperation($objects);

        $this->assertEquals($objects, $addOp->getValue());
    }

    /**
     * @group add-op
     */
    public function testBadObjects()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'AddOperation requires an array.'
        );
        new AddOperation('not an array');
    }

    /**
     * @group add-op
     */
    public function testMergePrevious()
    {
        $addOp = new AddOperation([
            'key1'          => 'value1'
        ]);

        $this->assertEquals($addOp, $addOp->_mergeWithPrevious(null));

        // check delete op
        $merged = $addOp->_mergeWithPrevious(new DeleteOperation());
        $this->assertTrue($merged instanceof SetOperation);

        // check set op
        $merged = $addOp->_mergeWithPrevious(new SetOperation('newvalue'));
        $this->assertTrue($merged instanceof SetOperation);
        $this->assertEquals([
            'newvalue',
            'key1'  => 'value1'
        ], $merged->getValue(), 'Value was not as expected');

        // check self
        $merged = $addOp->_mergeWithPrevious(new AddOperation(['key2'   => 'value2']));
        $this->assertTrue($merged instanceof SetOperation);
        $this->assertEquals([
            'key2'  => 'value2',
            'key1'  => 'value1'
        ], $merged->getValue(), 'Value was not as expected');
    }

    /**
     * @group add-op
     */
    public function testInvalidMerge()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'Operation is invalid after previous operation.'
        );
        $addOp = new AddOperation([
            'key1'          => 'value1'
        ]);
        $addOp->_mergeWithPrevious(new \DateTime());
    }
}
