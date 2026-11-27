<?php
/**
 * Class RemoveOperationTest | Parse/Test/RemoveOperationTest.php
 */

namespace Parse\Test;

use Parse\Internal\AddOperation;
use Parse\Internal\DeleteOperation;
use Parse\Internal\RemoveOperation;
use Parse\Internal\SetOperation;
use Parse\ParseObject;

use PHPUnit\Framework\TestCase;

class RemoveOperationTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    /**
     * @group remove-op
     */
    public function testMissingArray()
    {
        $this->expectException(
            '\Parse\ParseException',
            'RemoveOperation requires an array.'
        );
        new RemoveOperation('not an array');
    }

    /**
     * @group remove-op-merge
     */
    public function testMergePrevious()
    {
        $removeOp = new RemoveOperation([
            'key1'          => 'value1'
        ]);

        $this->assertEquals($removeOp, $removeOp->_mergeWithPrevious(null));

        // check delete op
        $merged = $removeOp->_mergeWithPrevious(new DeleteOperation());
        $this->assertTrue($merged instanceof DeleteOperation);

        // check set op
        $merged = $removeOp->_mergeWithPrevious(new SetOperation('newvalue'));
        $this->assertTrue($merged instanceof SetOperation);
        $this->assertEquals([
            'newvalue'
        ], $merged->getValue(), 'Value was not as expected');

        // check self
        $merged = $removeOp->_mergeWithPrevious(new RemoveOperation(['key2'   => 'value2']));
        $this->assertTrue($merged instanceof RemoveOperation);
        $this->assertEquals([
            'key2'  => 'value2',
            'key1'  => 'value1'
        ], $merged->getValue(), 'Value was not as expected');
    }

    /**
     * @group remove-op
     */
    public function testInvalidMerge()
    {
        $this->expectException(
            '\Parse\ParseException',
            'Operation is invalid after previous operation.'
        );
        $removeOp = new RemoveOperation([
            'key1'          => 'value1'
        ]);
        $removeOp->_mergeWithPrevious(new AddOperation(['key'=>'value']));
    }

    /**
     * @group remove-op
     */
    public function testEmptyApply()
    {
        $removeOp = new RemoveOperation([
            'key1'          => 'value1'
        ]);
        $this->assertEmpty($removeOp->_apply([], null, null));
    }

    /**
     * @group remove-op
     */
    public function testApplyRemovesMultipleValues()
    {
        $removeOp = new RemoveOperation([1, 2]);
        $this->assertSame([3], $removeOp->_apply([1, 2, 3], null, null));
    }

    /**
     * @group remove-op
     */
    public function testApplyRemovesAllDuplicates()
    {
        $removeOp = new RemoveOperation(['a', 'b']);
        $this->assertSame(
            ['c'],
            $removeOp->_apply(['a', 'b', 'a', 'c', 'b'], null, null)
        );
    }

    /**
     * @group remove-op
     */
    public function testApplyWithNothingToRemove()
    {
        $removeOp = new RemoveOperation([]);
        $this->assertSame([1, 2], $removeOp->_apply([1, 2], null, null));
    }

    /**
     * @group remove-op
     */
    public function testApplyRemovesParseObjectsByObjectId()
    {
        $removeOp = new RemoveOperation([
            ParseObject::create('TestObject', 'id1'),
            ParseObject::create('TestObject', 'id2'),
        ]);
        $kept = ParseObject::create('TestObject', 'id3');

        $result = $removeOp->_apply([
            ParseObject::create('TestObject', 'id1'),
            ParseObject::create('TestObject', 'id2'),
            $kept,
        ], null, null);

        $this->assertSame([$kept], $result);
    }

    /**
     * @group remove-op-merge
     */
    public function testMergeWithSetRemovesMultipleValues()
    {
        $removeOp = new RemoveOperation([1, 2]);
        $merged = $removeOp->_mergeWithPrevious(new SetOperation([1, 2, 3]));

        $this->assertTrue($merged instanceof SetOperation);
        $this->assertSame([3], $merged->getValue());
    }
}
