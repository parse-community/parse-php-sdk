<?php

namespace Parse\Test;

use Parse\ParseObject;
use Parse\ParseQuery;

class ParseQueryFullTextTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function setUp()
    {
        Helper::clearClass('TestObject');
    }

    public function tearDown()
    {
        Helper::tearDown();
    }

    /**
     * This function used as a helper function in test functions to save objects.
     */
    public function provideTestObjects()
    {
        $subjects = [
            'coffee',
            'Coffee Shopping',
            'Baking a cake',
            'baking',
            'Café Con Leche',
            'Сырники',
            'coffee and cream',
            'Cafe con Leche'
        ];

        $allObjects = [];
        for ($i = 0; $i < count($subjects); ++$i) {
            $obj = new ParseObject('TestObject');
            $obj->set('subject', $subjects[$i]);
            $allObjects[] = $obj;
        }
        ParseObject::saveAll($allObjects);
    }

    public function testFullTextQuery()
    {
        $this->provideTestObjects();
        $query = new ParseQuery('TestObject');
        $query->fullText('subject', 'coffee');
        $results = $query->find();
        $this->assertEquals(
            3,
            count($results),
            'Did not return correct objects.'
        );
    }

    public function testFullTextSort()
    {
        $this->provideTestObjects();
        $query = new ParseQuery('TestObject');
        $query->fullText('subject', 'coffee');
        $query->ascending('$score');
        $query->select('$score');
        $results = $query->find();
        $this->assertEquals(
            3,
            count($results),
            'Did not return correct number of objects.'
        );
        $this->assertEquals(1, $results[0]->get('score'));
        $this->assertEquals(0.75, $results[1]->get('score'));
        $this->assertEquals(0.75, $results[2]->get('score'));
    }
}
