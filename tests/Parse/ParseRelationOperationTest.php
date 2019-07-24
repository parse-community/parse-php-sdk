<?php
/**
 * Class ParseRelationOperationTest | Parse/Test/ParseRelationOperationTest.php
 */

namespace Parse\Test;

use Parse\Internal\ParseRelationOperation;
use Parse\ParseObject;
use Parse\ParseRelation;

use PHPUnit\Framework\TestCase;

class ParseRelationOperationTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    public function tearDown() : void
    {
        Helper::clearClass('Class1');
    }

    /**
     * @group parse-relation-op
     */
    public function testMissingObjects()
    {
        $this->expectException(
            '\Exception',
            'Cannot create a ParseRelationOperation with no objects.'
        );
        new ParseRelationOperation(null, null);
    }

    /**
     * @group parse-relation-op
     */
    public function testMixedClasses()
    {
        $this->expectException(
            '\Exception',
            'All objects in a relation must be of the same class.'
        );

        $objects = [];
        $objects[] = new ParseObject('Class1');
        $objects[] = new ParseObject('Class2');

        new ParseRelationOperation($objects, null);
    }

    /**
     * @group parse-relation-op
     */
    public function testSingleObjects()
    {
        $addObj = new ParseObject('Class1');
        $addObj->save();
        $delObj = new ParseObject('Class1');
        $delObj->save();

        $op = new ParseRelationOperation($addObj, $delObj);

        $encoded = $op->_encode();

        $this->assertEquals('AddRelation', $encoded['ops'][0]['__op']);
        $this->assertEquals('RemoveRelation', $encoded['ops'][1]['__op']);

        ParseObject::destroyAll([$addObj, $delObj]);
    }

    /**
     * @group parse-relation-op
     */
    public function testApplyDifferentClassRelation()
    {
        $this->expectException(
            '\Exception',
            'Related object object must be of class '
            .'Class1, but DifferentClass'
            .' was passed in.'
        );

        // create one op
        $addObj = new ParseObject('Class1');
        $relOp1 = new ParseRelationOperation($addObj, null);

        $relOp1->_apply(new ParseRelation(null, null, 'DifferentClass'), null, null);
    }

    /**
     * @group parse-relation-op
     */
    public function testInvalidApply()
    {
        $this->expectException(
            '\Exception',
            'Operation is invalid after previous operation.'
        );
        $addObj = new ParseObject('Class1');
        $op = new ParseRelationOperation($addObj, null);
        $op->_apply('bad value', null, null);
    }

    /**
     * @group parse-relation-op
     */
    public function testMergeNone()
    {
        $addObj = new ParseObject('Class1');
        $op = new ParseRelationOperation($addObj, null);
        $this->assertEquals($op, $op->_mergeWithPrevious(null));
    }

    /**
     * @group parse-relation-op
     */
    public function testMergeDifferentClass()
    {
        $this->expectException(
            '\Exception',
            'Related object must be of class '
            .'Class1, but AnotherClass'
            .' was passed in.'
        );

        $addObj = new ParseObject('Class1');
        $op = new ParseRelationOperation($addObj, null);

        $diffObj = new ParseObject('AnotherClass');
        $mergeOp = new ParseRelationOperation($diffObj, null);

        $this->assertEquals($op, $op->_mergeWithPrevious($mergeOp));
    }

    /**
     * @group parse-relation-op
     */
    public function testInvalidMerge()
    {
        $this->expectException(
            '\Exception',
            'Operation is invalid after previous operation.'
        );
        $obj = new ParseObject('Class1');
        $op = new ParseRelationOperation($obj, null);
        $op->_mergeWithPrevious('not a relational op');
    }

    /**
     * @group parse-relation-op
     */
    public function testRemoveElementsFromArray()
    {
        // test without passing an array
        $array = [
          'removeThis'
        ];
        ParseRelationOperation::removeElementsFromArray('removeThis', $array);

        $this->assertEmpty($array);
    }

    /**
     * @group relation-remove-missing-object-id
     */
    public function testRemoveMissingObjectId()
    {
        $obj = new ParseObject('Class1');
        $op = new ParseRelationOperation(null, $obj);
        $op->_mergeWithPrevious(new ParseRelationOperation(null, $obj));
        $this->assertTrue(true);
    }
}
