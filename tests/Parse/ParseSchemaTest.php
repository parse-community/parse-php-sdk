<?php


/**
 * ParseSchema Tests.
 *
 * @see http://docs.parseplatform.org/rest/guide/#schema
 *
 * @author Júlio César Gonçalves de Oliveira <julio@pinguineras.com.br>
 */
namespace Parse\Test;

use Parse\HttpClients\ParseCurlHttpClient;
use Parse\HttpClients\ParseStreamHttpClient;
use Parse\ParseClient;
use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseSchema;
use Parse\ParseUser;

use PHPUnit\Framework\TestCase;

class ParseSchemaTest extends TestCase
{
    /**
     * @var ParseSchema
     */
    private static $schema;

    /**
     * @var string
     */
    private static $badClassName = "<Bad~ Class~ Name>";

    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    public function setup() : void
    {
        self::$schema = new ParseSchema('SchemaTest');
        Helper::clearClass('_User');
        Helper::setHttpClient();
    }

    public function tearDown() : void
    {
        Helper::tearDown();
        self::$schema->delete();

        ParseUser::logOut();
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
        $this->assertEquals(ParseSchema::$POLYGON, $result['fields']['polygonField']['type']);
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
            ->addPolygon('polygonField')
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

    public function testAllSchemaWithUserLoggedIn()
    {
        $user = new ParseUser();
        $user->setUsername('schema-user');
        $user->setPassword('basicpassword');
        $user->signUp();

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

    public function testUpdateSchemaStream()
    {
        ParseClient::setHttpClient(new ParseStreamHttpClient());

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

    public function testUpdateSchemaCurl()
    {
        if (function_exists('curl_init')) {
            ParseClient::setHttpClient(new ParseCurlHttpClient());

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
    }

    public function testUpdateMultipleNoDuplicateFields()
    {
        $schema = self::$schema;
        $schema->save();
        $schema->addString('name');
        $schema->update();

        $getSchema = new ParseSchema('SchemaTest');
        $result = $getSchema->get();
        $this->assertEquals(count($result['fields']), 5);

        $schema->update();

        $getSchema = new ParseSchema('SchemaTest');
        $result = $getSchema->get();
        $this->assertEquals(count($result['fields']), 5);
    }

    public function testUpdateWrongFieldType()
    {
        $this->expectException('Exception', 'WrongType is not a valid type.');

        $schema = new ParseSchema();
        $schema->addField('NewTestField', 'WrongType');
        $schema->update();
    }

    /**
     * @group schema-purge
     */
    public function testPurgeSchema()
    {
        // get a handle to this schema
        $schema = new ParseSchema('SchemaTest');

        // create an object in this schema
        $obj = new ParseObject('SchemaTest');
        $obj->set('field', 'the_one_and_only');
        $obj->save();

        // attempt to delete this schema (expecting to fail)
        try {
            $schema->delete();
            $this->assertTrue(false, 'Did not fail on delete as expected');
        } catch (ParseException $pe) {
            $this->assertEquals(
                'Class SchemaTest is not empty, contains 1 objects, cannot drop schema.',
                $pe->getMessage()
            );
        }

        // purge this schema
        $schema->purge();

        // verify no more objects are present
        $query = new ParseQuery('SchemaTest');
        $this->assertEquals(0, $query->count());

        // delete again
        $schema->delete();
    }

    /**
     * @group schema-purge
     */
    public function testPurgingNonexistentSchema()
    {
        try {
            $schema = new ParseSchema('NotARealSchema');
            $schema->purge();
        } catch (\Exception $e) {
            // exception on earlier versions > 2.8, no exception on >= 2.8
            // thus hard to test for this unless version detection is utilized here
        }
        $this->assertTrue(true);
    }

    public function testDeleteSchema()
    {
        $createSchema = new ParseSchema('SchemaDeleteTest');
        $createSchema->addField('newField01');
        $createSchema->save();

        $deleteSchema = new ParseSchema('SchemaDeleteTest');
        $deleteSchema->delete();

        $getSchema = new ParseSchema('SchemaDeleteTest');
        $this->expectException(
            'Parse\ParseException',
            'Class SchemaDeleteTest does not exist.'
        );
        $getSchema->get();
    }

    public function testAssertClassName()
    {
        $schema = new ParseSchema();
        $this->expectException('\Exception', 'You must set a Class Name before making any request.');
        $schema->assertClassName();
    }

    public function testFieldNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'field name may not be null.');
        $schema->addField(null, '_Type');
    }

    public function testStringFieldNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'field name may not be null.');
        $schema->addString();
    }

    public function testNumberFieldNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'field name may not be null.');
        $schema->addNumber();
    }

    public function testBooleanFieldNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'field name may not be null.');
        $schema->addBoolean();
    }

    public function testDateFieldNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'field name may not be null.');
        $schema->addDate();
    }

    public function testFileFieldNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'field name may not be null.');
        $schema->addFile();
    }

    public function testGeoPointFieldNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'field name may not be null.');
        $schema->addGeoPoint();
    }

    public function testPolygonFieldNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'field name may not be null.');
        $schema->addPolygon();
    }

    public function testArrayFieldNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'field name may not be null.');
        $schema->addArray();
    }

    public function testObjectFieldNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'field name may not be null.');
        $schema->addObject();
    }

    public function testPointFieldNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'field name may not be null.');
        $schema->addPointer(null, '_Type');
    }

    public function testRelationFieldNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'field name may not be null.');
        $schema->addRelation(null, '_Type');
    }

    public function testPointerTargetClassException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'You need to set the targetClass of the Pointer.');
        $schema->addPointer('field', null);
    }

    public function testRelationTargetClassException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'You need to set the targetClass of the Relation.');
        $schema->addRelation('field', null);
    }

    public function testTypeNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'Type name may not be null.');
        $schema->addField('field', null);
    }

    public function testSchemaNotExistException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'Class SchemaTest does not exist');
        $schema->get();
    }

    public function testInvalidTypeException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'StringFormatter is not a valid type.');
        $schema->assertTypes('StringFormatter');
    }

    /**
     * @group schema-test-errors
     */
    public function testBadSchemaGet()
    {
        $this->expectException('\Parse\ParseException');

        $user = new ParseUser();
        $user->setUsername('schema-user');
        $user->setPassword('basicpassword');
        $user->signUp();

        $schema = new ParseSchema(self::$badClassName);
        $schema->get();
    }

    /**
     * @group schema-test-errors
     */
    public function testBadSchemaSave()
    {
        $this->expectException('\Exception');

        $user = new ParseUser();
        $user->setUsername('schema-user');
        $user->setPassword('basicpassword');
        $user->signUp();

        $schema = new ParseSchema(self::$badClassName);
        $schema->save();
    }

    /**
     * @group schema-test-errors
     */
    public function testBadSchemaUpdate()
    {
        $this->expectException('\Exception');

        $user = new ParseUser();
        $user->setUsername('schema-user');
        $user->setPassword('basicpassword');
        $user->signUp();

        $schema = new ParseSchema(self::$badClassName);
        $schema->update();
    }

    /**
     * @group schema-test-errors
     */
    public function testBadSchemaDelete()
    {
        $this->markTestSkipped('Curl is not sending the request and does not complain.');

        $this->expectException('\Parse\ParseException');

        $user = new ParseUser();
        $user->setUsername('schema-user');
        $user->setPassword('basicpassword');
        $user->signUp();
        $schema = new ParseSchema(self::$badClassName);
        $schema->delete();
    }

    public function testCreateIndexSchema()
    {
        $schema = self::$schema;
        $schema->addString('name');
        $index = [ 'name' => 1 ];
        $schema->addIndex('test_index', $index);
        $schema->save();

        $getSchema = new ParseSchema('SchemaTest');
        $result = $getSchema->get();
        $this->assertNotNull($result['indexes']['test_index']);
    }

    public function testUpdateIndexSchema()
    {
        $schema = self::$schema;
        $schema->save();
        $schema->addString('name');
        $index = [ 'name' => 1 ];
        $schema->addIndex('test_index', $index);
        $schema->update();

        $getSchema = new ParseSchema('SchemaTest');
        $result = $getSchema->get();
        $this->assertNotNull($result['indexes']['test_index']);
    }

    public function testDeleteIndexSchema()
    {
        $schema = self::$schema;
        $schema->save();
        $schema->addString('name');
        $index = [ 'name' => 1 ];
        $schema->addIndex('test_index', $index);
        $schema->update();

        $getSchema = new ParseSchema('SchemaTest');
        $result = $getSchema->get();
        $this->assertNotNull($result['indexes']['test_index']);

        $schema->deleteIndex('test_index');
        $schema->update();
        $result = $getSchema->get();
        $this->assertEquals(array_key_exists('text_index', $result['indexes']), false);
    }

    public function testIndexNameException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'index name may not be null.');
        $schema->addIndex(null, null);
    }

    public function testIndexException()
    {
        $schema = self::$schema;
        $this->expectException('\Exception', 'index may not be null.');
        $schema->addIndex('name', null);
    }
}
