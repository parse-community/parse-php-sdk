<?php
/**
 * Class ParseSchema | Parse/ParseSchema.php
 */

namespace Parse;

use Exception;
use InvalidArgumentException;

/**
 * Class ParseSchema - Handles schemas data from Parse.
 * All the schemas methods need use of the master key for your application.
 *
 * @see http://docs.parseplatform.org/rest/guide/#schema
 *
 * @author Júlio César Gonçalves de Oliveira <julio@pinguineras.com.br>
 * @package Parse
 */
class ParseSchema
{
    /**
     * String data type
     *
     * @var string
     */
    public static $STRING = 'String';

    /**
     * Number data type
     *
     * @var string
     */
    public static $NUMBER = 'Number';

    /**
     * Boolean data type
     *
     * @var string
     */
    public static $BOOLEAN = 'Boolean';

    /**
     * Date data type
     *
     * @var string
     */
    public static $DATE = 'Date';

    /**
     * File data type
     *
     * @var string
     */
    public static $FILE = 'File';

    /**
     * GeoPoint data type
     *
     * @var string
     */
    public static $GEO_POINT = 'GeoPoint';

    /**
     * Polygon data type
     *
     * @var string
     */
    public static $POLYGON = 'Polygon';

    /**
     * Array data type
     *
     * @var string
     */
    public static $ARRAY = 'Array';

    /**
     * Object data type
     *
     * @var string
     */
    public static $OBJECT = 'Object';

    /**
     * Pointer data type
     *
     * @var string
     */
    public static $POINTER = 'Pointer';

    /**
     * Relation data type
     *
     * @var string
     */
    public static $RELATION = 'Relation';

    /**
     * Class name for data stored on Parse.
     *
     * @var string
     */
    private $className;

    /**
     * Fields to create.
     *
     * @var array
     */
    private $fields = [];

    /**
     * Indexes to create.
     *
     * @var array
     */
    private $indexes = [];

    /**
     * Force to use master key in Schema Methods.
     *
     * @see http://docs.parseplatform.org/rest/guide/#schema
     *
     * @var bool
     */
    private $useMasterKey = true;

    /**
     * Create a Parse Schema.
     *
     * @param string|null $className Class Name of data on Parse.
     */
    public function __construct($className = null)
    {
        if ($className) {
            $this->className = $className;
        }
    }

    /**
     * Get all the Schema data on Parse.
     *
     * @throws ParseException
     *
     * @return array
     */
    public function all()
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'GET',
            'schemas/',
            $sessionToken,
            null,
            $this->useMasterKey
        );

        if (!isset($result['results']) || empty($result['results'])) {
            throw new ParseException('Schema not found.', 101);
        }

        return $result['results'];
    }

    /**
     * Get the Schema from Parse.
     *
     * @throws ParseException
     *
     * @return array
     */
    public function get()
    {
        self::assertClassName();

        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'GET',
            'schemas/'.$this->className,
            $sessionToken,
            null,
            $this->useMasterKey
        );

        if (empty($result)) {
            throw new ParseException('Schema not found.', 101);
        }

        return $result;
    }

    /**
     * Create a new Schema on Parse.
     *
     * @throws \Exception
     *
     * @return array
     */
    public function save()
    {
        self::assertClassName();

        $schema = [];

        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        // Schema
        $schema['className'] = $this->className;
        if (!empty($this->fields)) {
            $schema['fields'] = $this->fields;
        }

        if (!empty($this->indexes)) {
            $schema['indexes'] = $this->indexes;
        }

        $result = ParseClient::_request(
            'POST',
            'schemas/'.$this->className,
            $sessionToken,
            json_encode($schema),
            $this->useMasterKey
        );

        if (empty($result)) {
            throw new Exception('Error on create Schema "'.$this->className.'"', 0);
        }

        return $result;
    }

    /**
     * Update a Schema from Parse.
     *
     * @throws \Exception
     *
     * @return array
     */
    public function update()
    {
        self::assertClassName();

        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        // Schema
        $schema['className'] = $this->className;
        $schema['fields'] = $this->fields;
        $schema['indexes'] = $this->indexes;

        $this->fields = [];
        $this->indexes = [];

        $result = ParseClient::_request(
            'PUT',
            'schemas/'.$this->className,
            $sessionToken,
            json_encode($schema),
            $this->useMasterKey
        );

        if (empty($result)) {
            throw new Exception('Error on update Schema "'.$this->className.'"', 101);
        }

        return $result;
    }

    /**
     * Removes all objects from a Schema (class) in Parse.
     * EXERCISE CAUTION, running this will delete all objects for this schema and cannot be reversed
     *
     * @throws Exception
     */
    public function purge()
    {
        self::assertClassName();

        $result = ParseClient::_request(
            'DELETE',
            'purge/'.$this->className,
            null,
            null,
            $this->useMasterKey
        );

        if (!empty($result)) {
            throw new Exception('Error on purging all objects from schema "'.$this->className.'"');
        }
    }

    /**
     * Removing a Schema from Parse.
     * You can only remove a schema from your app if it is empty (has 0 objects).
     *
     * @throws \Exception
     *
     * @return array
     */
    public function delete()
    {
        self::assertClassName();

        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'DELETE',
            'schemas/'.$this->className,
            $sessionToken,
            null,
            $this->useMasterKey
        );

        if (!empty($result)) {
            throw new Exception('Error on delete Schema "'.$this->className.'"', 101);
        }

        return $result;
    }

    /**
     * Adding a Field to Create / Update a Schema.
     *
     * @param string $fieldName Name of the field that will be created on Parse
     * @param string $fieldType Can be a (String|Number|Boolean|Date|File|GeoPoint|Array|Object|Pointer|Relation)
     *
     * @throws \Exception
     *
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addField($fieldName = null, $fieldType = 'String')
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.', 105);
        }
        if (!$fieldType) {
            throw new Exception('Type name may not be null.');
        }

        $this->assertTypes($fieldType);

        $this->fields[$fieldName] = [
            'type' => $fieldType,
        ];

        return $this;
    }

      /**
     * Adding an Index to Create / Update a Schema.
     *
     * @param string $indexName Name of the index that will be created on Parse
     * @param string $index Key / Value to be saved
     *
     * @throws \Exception
     *
     * @return ParseSchema indexes return self to create index on Parse
     */
    public function addIndex($indexName, $index)
    {
        if (!$indexName) {
            throw new Exception('index name may not be null.', 105);
        }

        if (!$index) {
            throw new Exception('index may not be null.', 105);
        }

        $this->indexes[$indexName] = $index;

        return $this;
    }

    /**
     * Adding String Field.
     *
     * @param string $fieldName Name of the field that will be created on Parse
     *
     * @throws \Exception
     *
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addString($fieldName = null)
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.', 105);
        }

        $this->fields[$fieldName] = [
            'type' => self::$STRING,
        ];

        return $this;
    }

    /**
     * Adding Number Field.
     *
     * @param string $fieldName Name of the field will created on Parse
     *
     * @throws \Exception
     *
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addNumber($fieldName = null)
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.', 105);
        }

        $this->fields[$fieldName] = [
            'type' => self::$NUMBER,
        ];

        return $this;
    }

    /**
     * Adding Boolean Field.
     *
     * @param string $fieldName Name of the field will created on Parse
     *
     * @throws \Exception
     *
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addBoolean($fieldName = null)
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.', 105);
        }

        $this->fields[$fieldName] = [
            'type' => self::$BOOLEAN,
        ];

        return $this;
    }

    /**
     * Adding Date Field.
     *
     * @param string $fieldName Name of the field will created on Parse
     *
     * @throws \Exception
     *
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addDate($fieldName = null)
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.', 105);
        }

        $this->fields[$fieldName] = [
            'type' => self::$DATE,
        ];

        return $this;
    }

    /**
     * Adding File Field.
     *
     * @param string $fieldName Name of the field will created on Parse
     *
     * @throws \Exception
     *
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addFile($fieldName = null)
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.', 105);
        }

        $this->fields[$fieldName] = [
            'type' => self::$FILE,
        ];

        return $this;
    }

    /**
     * Adding GeoPoint Field.
     *
     * @param string $fieldName Name of the field will created on Parse
     *
     * @throws \Exception
     *
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addGeoPoint($fieldName = null)
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.', 105);
        }

        $this->fields[$fieldName] = [
            'type' => self::$GEO_POINT,
        ];

        return $this;
    }

    /**
     * Adding Polygon Field.
     *
     * @param string $fieldName Name of the field will created on Parse
     *
     * @throws \Exception
     *
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addPolygon($fieldName = null)
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.', 105);
        }

        $this->fields[$fieldName] = [
            'type' => self::$POLYGON,
        ];

        return $this;
    }

    /**
     * Adding Array Field.
     *
     * @param string $fieldName Name of the field will created on Parse
     *
     * @throws \Exception
     *
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addArray($fieldName = null)
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.', 105);
        }

        $this->fields[$fieldName] = [
            'type' => self::$ARRAY,
        ];

        return $this;
    }

    /**
     * Adding Object Field.
     *
     * @param string $fieldName Name of the field will created on Parse
     *
     * @throws \Exception
     *
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addObject($fieldName = null)
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.', 105);
        }

        $this->fields[$fieldName] = [
            'type' => self::$OBJECT,
        ];

        return $this;
    }

    /**
     * Adding Pointer Field.
     *
     * @param string $fieldName   Name of the field will created on Parse
     * @param string $targetClass Name of the target Pointer Class
     *
     * @throws \Exception
     *
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addPointer($fieldName = null, $targetClass = null)
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.', 105);
        }

        if (!$targetClass) {
            throw new Exception('You need to set the targetClass of the Pointer.', 103);
        }

        $this->fields[$fieldName] = [
            'type'        => self::$POINTER,
            'targetClass' => $targetClass,
        ];

        return $this;
    }

    /**
     * Adding Relation Field.
     *
     * @param string $fieldName   Name of the field will created on Parse
     * @param string $targetClass Name of the target Pointer Class
     *
     * @throws \Exception
     *
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addRelation($fieldName = null, $targetClass = null)
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.', 105);
        }

        if (!$targetClass) {
            throw new Exception('You need to set the targetClass of the Relation.', 103);
        }

        $this->fields[$fieldName] = [
            'type'        => self::$RELATION,
            'targetClass' => $targetClass,
        ];

        return $this;
    }

    /**
     * Deleting a Field to Update on a Schema.
     *
     * @param string $fieldName Name of the field will be deleted
     */
    public function deleteField($fieldName = null)
    {
        $this->fields[$fieldName] = [
            '__op' => 'Delete',
        ];
    }

    /**
     * Deleting an Index to Update on a Schema.
     *
     * @param string $indexName Name of the index that will be deleted
     */
    public function deleteIndex($indexName = null)
    {
        $this->indexes[$indexName] = [
            '__op' => 'Delete',
        ];
    }

    /**
     * Assert if ClassName has filled.
     *
     * @throws \Exception
     */
    public function assertClassName()
    {
        if ($this->className === null) {
            throw new Exception('You must set a Class Name before making any request.', 103);
        }
    }

    /**
     * Assert types of fields.
     *
     * @param string $type
     *
     * @throws \InvalidArgumentException
     */
    public function assertTypes($type = null)
    {
        if ($type !== self::$STRING &&
            $type !== self::$NUMBER &&
            $type !== self::$BOOLEAN &&
            $type !== self::$DATE &&
            $type !== self::$FILE &&
            $type !== self::$GEO_POINT &&
            $type !== self::$POLYGON &&
            $type !== self::$ARRAY &&
            $type !== self::$OBJECT &&
            $type !== self::$POINTER &&
            $type !== self::$RELATION) {
            throw new InvalidArgumentException($type.' is not a valid type.', 1);
        }
    }
}
