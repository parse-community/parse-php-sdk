<?php
/**
 * Created by PhpStorm.
 * User: Bfriedman
 * Date: 1/30/17
 * Time: 10:34 AM
 */

namespace Parse\Test;

use Parse\Internal\AddUniqueOperation;
use Parse\Internal\DeleteOperation;
use Parse\Internal\IncrementOperation;
use Parse\Internal\SetOperation;
use Parse\ParseClient;
use Parse\ParseException;
use Parse\ParseObject;
use Symfony\Component\Validator\Constraints\DateTime;

class AddUniqueOperationTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function tearDown()
    {
        Helper::clearClass('TestObject');
    }

    /**
     * @group add-unique-op
     */
    public function testAddUniqueOp()
    {
        $objects = [
            'key1'  => 'val1',
            'key2'  => 'val2'
        ];
        $addUnique = new AddUniqueOperation($objects);

        $this->assertEquals($objects, $addUnique->getValue());
    }

    /**
     * @group add-unique-op
     */
    public function testBadObjects()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'AddUniqueOperation requires an array.'
        );
        $addUnique = new AddUniqueOperation('not-an-array');
    }

    /**
     * @group add-unique-op
     */
    public function testEncode()
    {
        $objects = [
            'key1'  => 'val1',
            'key2'  => 'val2'
        ];
        $addUnique = new AddUniqueOperation($objects);

        $encoded = $addUnique->_encode();

        $this->assertEquals([
            '__op'      => 'AddUnique',
            'objects'   => ParseClient::_encode($objects, true)
        ], $encoded);
    }

    /**
     * @group add-unique-op
     */
    public function testMergePrevious()
    {
        $addOp = new AddUniqueOperation([
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
            'value1'
        ], $merged->getValue(), 'Value was not as expected');

        // check self
        $merged = $addOp->_mergeWithPrevious(new AddUniqueOperation(['key2'   => 'value2']));
        $this->assertTrue($merged instanceof AddUniqueOperation);
        $this->assertEquals([
            'key2'  => 'value2',
            'value1'
        ], $merged->getValue(), 'Value was not as expected');
    }

    /**
     * @group add-unique-op
     */
    public function testInvalidMerge()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'Operation is invalid after previous operation.'
        );
        $addOp = new AddUniqueOperation([
            'key1'          => 'value1'
        ]);
        $addOp->_mergeWithPrevious(new IncrementOperation());
    }

    /**
     * @group add-unique-op
     */
    public function testApply()
    {
        // test a null old value
        $objects = [
            'key1'  => 'value1'
        ];
        $addOp = new AddUniqueOperation($objects);
        $this->assertEquals($objects, $addOp->_apply(null, null, null));

        $addOp = new AddUniqueOperation([
            'key'    => 'string'
        ]);
        $oldValue = $addOp->_apply('string', null, null);
        $this->assertEquals(['string'], $oldValue);

        // test saving an object
        $obj = new \DateTime();
        $addOp = new AddUniqueOperation([
            'object'    => $obj
        ]);
        $oldValue = $addOp->_apply($obj, null, null);
        $this->assertEquals($obj, $oldValue[0]);

        // create a Parse object to save
        $obj1 = new ParseObject('TestObject');
        $obj1->set('name', 'montymxb');
        $obj1->save();

        // test a saved parse object as the old value
        $addOp = new AddUniqueOperation([
            'object'    => $obj1
        ]);
        $oldValue = $addOp->_apply($obj1, null, null);
        $this->assertEquals($obj1, $oldValue[0]);
    }
}
