<?php
/**
 * Class RemoveOperationTest | Parse/Test/RemoveOperationTest.php
 */

namespace Parse\Test;

use Parse\Internal\AddOperation;
use Parse\Internal\DeleteOperation;
use Parse\Internal\RemoveOperation;
use Parse\Internal\SetOperation;

use PHPUnit\Framework\TestCase;

class RemoveOperationTest extends TestCase
{
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
}
