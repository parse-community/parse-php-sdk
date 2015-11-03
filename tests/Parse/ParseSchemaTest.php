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
        $schema->addRelation('NewField11', '_User');
        $schema->save();

        $getSchema = new ParseSchema('SchemaTest');
        $result = $getSchema->get();

        $getSchema = new ParseSchema('SchemaTest');
        $result = $getSchema->get();

        if ($result['fields']['NewField1']['type'] != 'String') {
            $this->assertTrue(false);
        }
        if ($result['fields']['NewField2']['type'] != 'Date') {
            $this->assertTrue(false);
        }
        if ($result['fields']['NewField3']['type'] != 'Number') {
            $this->assertTrue(false);
        }
        if ($result['fields']['NewField4']['type'] != 'Boolean') {
            $this->assertTrue(false);
        }
        if ($result['fields']['NewField5']['type'] != 'Date') {
            $this->assertTrue(false);
        }
        if ($result['fields']['NewField6']['type'] != 'File') {
            $this->assertTrue(false);
        }
        if ($result['fields']['NewField7']['type'] != 'GeoPoint') {
            $this->assertTrue(false);
        }
        if ($result['fields']['NewField8']['type'] != 'Array') {
            $this->assertTrue(false);
        }
        if ($result['fields']['NewField9']['type'] != 'Object') {
            $this->assertTrue(false);
        }
        if ($result['fields']['NewField10']['type'] != 'Pointer') {
            $this->assertTrue(false);
        }
        if ($result['fields']['NewField11']['type'] != 'Relation') {
            $this->assertTrue(false);
        }
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

        $getSchema = new ParseSchema('SchemaTest');
        $result = $getSchema->get();

        if (isset($result['fields']['NewField2'])) {
            $this->assertTrue(false);
        }

        if (!isset($result['fields']['quantity'])) {
            $this->assertTrue(false);
        }
        if (!isset($result['fields']['status'])) {
            $this->assertTrue(false);
        }
    }

    public function testUpdateWrongFieldType()
    {
        $schema = new ParseSchema();
        $this->setExpectedException('Exception', 'WrongType is not a valid type.');
        $schema->addField('NewTestField', 'WrongType');
        $result = $schema->update();
    }

    public function testDeleteSchema()
    {
        $createSchema = new ParseSchema('SchemaDeleteTest');
        $createSchema->addField('newField01');
        $createSchema->save();

        $deleteSchema = new ParseSchema('SchemaDeleteTest');
        $deleteSchema->delete();

        $getSchema = new ParseSchema('SchemaDeleteTest');
        $this->setExpectedException('Parse\ParseException', 'class SchemaDeleteTest does not exist');
        $getSchema->get();
    }
}
