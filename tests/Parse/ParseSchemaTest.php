<?php 

/**
 * ParseSchema Tests.
 *
 * @see https://parse.com/docs/rest/guide#schemas
 *
 * @author Júlio César Gonçalves de Oliveira <julio@pinguineras.com.br>
 */

namespace Parse\Test;

use Parse\ParseSchema;

class ParseSchemaTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function tearDown()
    {
        Helper::tearDown();
    }


    // Tests

    public function testSchemas()
    {
        $schemas = new ParseSchema();
        $results = $schemas->all();
    	
    }

    public function testCreateSchema()
    {
        $schema = new ParseSchema('SchemaTest');
        $schema->addField('NewField1');
        $schema->addField('NewField2', 'Date');
        $schema->addNumber('NewField3');
        $schema->addBoolean('NewField4');
        $schema->addDate('NewField5');
        $schema->addFile('NewField6');
        $schema->addGeoPoint('NewField7');
        $schema->addArray('NewField8');
        $schema->addObject('NewField9');
        $schema->addPointer('NewField10', '_User');
        $schema->addRelation('NewField11',  '_User');
        $schema->save();
    }

    public function testGetSchema()
    {
        $schema = new ParseSchema('SchemaTest');
        $schema->get();
    }

    public function testUpdateSchema()
    {
        $schema = new ParseSchema('SchemaTest');
        $schema->deleteField('NewField2');
        $schema->addNumber('quantity');
        $schema->addField('status', 'Boolean');
        $schema->update();
    }

    public function testDeleteSchema()
    {
        $schema = new ParseSchema('SchemaTest');
        $schema->delete();
    }
}