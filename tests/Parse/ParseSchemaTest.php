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
    /**
     * @var ParseSchema
     */
    private static $schema;

    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function setUp()
    {
        self::$schema = new ParseSchema('SchemaTest');
    }

    public function tearDown()
    {
        Helper::tearDown();
        self::$schema->delete();
    }

    public function testSaveSchema()
    {
        $schema = self::$schema;

        $schema->save();

        $this->assertEquals('SchemaTest', $schema->get()['className']);
    }

    public function testGetFieldsSchema()
    {
        self::createFieldsOfSchema();

        // get schema
        $getSchema = new ParseSchema('SchemaTest');
        $result = $getSchema->get();

        $this->assertEquals(ParseSchema::$STRING, $result['fields']['defaultFieldString']['type']);
        $this->assertEquals(ParseSchema::$STRING, $result['fields']['stringField']['type']);
        $this->assertEquals(ParseSchema::$NUMBER, $result['fields']['numberField']['type']);
        $this->assertEquals(ParseSchema::$BOOLEAN, $result['fields']['booleanField']['type']);
        $this->assertEquals(ParseSchema::$DATE, $result['fields']['dateField']['type']);
        $this->assertEquals(ParseSchema::$FILE, $result['fields']['fileField']['type']);
        $this->assertEquals(ParseSchema::$GEO_POINT, $result['fields']['geoPointField']['type']);
        $this->assertEquals(ParseSchema::$ARRAY, $result['fields']['arrayField']['type']);
        $this->assertEquals(ParseSchema::$OBJECT, $result['fields']['objectField']['type']);
        $this->assertEquals(ParseSchema::$POINTER, $result['fields']['pointerField']['type']);
        $this->assertEquals(ParseSchema::$RELATION, $result['fields']['relationField']['type']);
    }

    private static function createFieldsOfSchema()
    {
        $schema = self::$schema;
        // add fields
        $schema
            ->addField('defaultFieldString')
            ->addString('stringField')
            ->addNumber('numberField')
            ->addBoolean('booleanField')
            ->addDate('dateField')
            ->addFile('fileField')
            ->addGeoPoint('geoPointField')
            ->addArray('arrayField')
            ->addObject('objectField')
            ->addPointer('pointerField', '_User')
            ->addRelation('relationField', '_User');
        // save schema
        $schema->save();
    }

    public function testAllSchema()
    {
        $schema_1 = new ParseSchema('SchemaTest_1');
        $schema_2 = new ParseSchema('SchemaTest_2');
        $schema_1->save();
        $schema_2->save();

        $schemas = new ParseSchema();
        $results = $schemas->all();

        $this->assertGreaterThanOrEqual(2, count($results));

        $schema_1->delete();
        $schema_2->delete();
    }

    public function testUpdateSchema()
    {
        // create
        $schema = self::$schema;
        $schema->addString('name');
        $schema->save();
        // update
        $schema->deleteField('name');
        $schema->addNumber('quantity');
        $schema->addField('status', 'Boolean');
        $schema->update();
        // get
        $getSchema = new ParseSchema('SchemaTest');
        $result = $getSchema->get();

        if (isset($result['fields']['name'])) {
            $this->fail('Field not deleted in update action');
        }
        $this->assertNotNull($result['fields']['quantity']);
        $this->assertNotNull($result['fields']['status']);
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

    public function testAssertClassName()
    {
        $schema = new ParseSchema();
        $this->setExpectedException('\Exception', 'You must set a Class Name before make any request.');
        $schema->assertClassName();
    }

    public function testFieldNameException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'field name may not be null.');
        $schema->addField(null, '_Type');
    }

    public function testStringFieldNameException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'field name may not be null.');
        $schema->addString();
    }

    public function testNumberFieldNameException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'field name may not be null.');
        $schema->addNumber();
    }

    public function testBooleanFieldNameException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'field name may not be null.');
        $schema->addBoolean();
    }

    public function testDateFieldNameException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'field name may not be null.');
        $schema->addDate();
    }

    public function testFileFieldNameException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'field name may not be null.');
        $schema->addFile();
    }

    public function testGeoPointFieldNameException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'field name may not be null.');
        $schema->addGeoPoint();
    }

    public function testArrayFieldNameException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'field name may not be null.');
        $schema->addArray();
    }

    public function testObjectFieldNameException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'field name may not be null.');
        $schema->addObject();
    }

    public function testPointFieldNameException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'field name may not be null.');
        $schema->addPointer(null, '_Type');
    }

    public function testRelationFieldNameException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'field name may not be null.');
        $schema->addRelation(null, '_Type');
    }

    public function testPointerTargetClassException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'You need set the targetClass of the Pointer.');
        $schema->addPointer('field', null);
    }

    public function testRelationTargetClassException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'You need set the targetClass of the Relation.');
        $schema->addRelation('field', null);
    }

    public function testTypeNameException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'Type name may not be null.');
        $schema->addField('field', null);
    }

    public function testSchemaNotExistException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'class SchemaTest does not exist');
        $schema->get();
    }

    public function testInvalidTypeException()
    {
        $schema = self::$schema;
        $this->setExpectedException('\Exception', 'StringFormatter is not a valid type.');
        $schema->assertTypes('StringFormatter');
    }
}
