<?php

namespace Parse;

use Exception;

/**
 * ParseSchema - Handles schemas data from Parse.
 * All the schemas methods needs use the master key of your application.
 *
 * @see https://parse.com/docs/rest/guide#schemas
 *
 * @author Júlio César Gonçalves de Oliveira <julio@pinguineras.com.br>
 */
class ParseSchema
{
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
     * Force to use master key in Schema Methods.
     * 
     * @see https://parse.com/docs/rest/guide#schemas
     * 
     * @var bool
     */
    public static $useMasterKey = true;

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
     * @param bool $useMasterKey Need to be true to make schema requests
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
            true
        );

        if (empty($result)) {
            throw new ParseException('Schema not found.', 101);
        }

        return $result;
    }

    /**
     * Get the Schema from Parse.
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
            self::$useMasterKey
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

        $Schema = [];

        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        // Schema
        $Schema['className'] = $this->className;
        $Schema['fields'] = $this->fields;

        $result = ParseClient::_request(
            'POST',
            'schemas/'.$this->className,
            $sessionToken,
            json_encode($Schema),
            self::$useMasterKey
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
        $Schema['className'] = $this->className;
        $Schema['fields'] = $this->fields;

        $result = ParseClient::_request(
            'PUT',
            'schemas/'.$this->className,
            $sessionToken,
            json_encode($Schema),
            self::$useMasterKey
        );

        if (empty($result)) {
            throw new Exception('Error on update Schema "'.$this->className.'"', 101);
        }

        return $result;
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
            self::$useMasterKey
        );

        if (!empty($result)) {
            throw new Exception('Error on delete Schema "'.$this->className.'"', 101);
        }

        return true;
    }

    /**
     * Adding a Field to Create / Update a Schema.
     *
     * @param string $fieldName Name of the field will created on Parse
     * @param string $fieldType ( String | Number | Boolean | Date | File | GeoPoint | Array | Object | Pointer | Relation )
     * 
     * @throws \Exception
     * 
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addField($fieldName = null, $fieldType = 'String')
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.');
        }
        if (!$fieldType) {
            throw new Exception('Type name may not be null.');
        }

        $this->assertTypes($fieldType);

        $this->fields[$fieldName] = [
            'type' => $fieldType,
        ];
    }

    /**
     * Adding String Field.
     * 
     * @param string $fieldName Name of the field will created on Parse
     * 
     * @throws \Exception
     * 
     * @return ParseSchema fields return self to create field on Parse
     */
    public function addString($fieldName = null)
    {
        if (!$fieldName) {
            throw new Exception('field name may not be null.');
        }

        $this->fields[$fieldName] = [
            'type' => 'String',
        ];
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
            throw new Exception('field name may not be null.');
        }

        $this->fields[$fieldName] = [
            'type' => 'Number',
        ];
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
            throw new Exception('field name may not be null.');
        }

        $this->fields[$fieldName] = [
            'type' => 'Boolean',
        ];
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
            throw new Exception('field name may not be null.');
        }

        $this->fields[$fieldName] = [
            'type' => 'Date',
        ];
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
            throw new Exception('field name may not be null.');
        }

        $this->fields[$fieldName] = [
            'type' => 'File',
        ];
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
            throw new Exception('field name may not be null.');
        }

        $this->fields[$fieldName] = [
            'type' => 'GeoPoint',
        ];
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
            throw new Exception('field name may not be null.');
        }

        $this->fields[$fieldName] = [
            'type' => 'Array',
        ];
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
            throw new Exception('field name may not be null.');
        }

        $this->fields[$fieldName] = [
            'type' => 'Object',
        ];
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
            throw new Exception('field name may not be null.');
        }

        if (!$targetClass) {
            throw new Exception('You need set the targetClass of the Pointer.');
        }

        $this->fields[$fieldName] = [
            'type'        => 'Pointer',
            'targetClass' => $targetClass,
        ];
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
            throw new Exception('field name may not be null.');
        }

        if (!$targetClass) {
            throw new Exception('You need set the targetClass of the Pointer.');
        }

        $this->fields[$fieldName] = [
            'type'        => 'Relation',
            'targetClass' => $targetClass,
        ];
    }

    /**
     * Deleting a Field to Update on a Schema.
     * 
     * @param string $fieldName Name of the field will be deleted
     * 
     * @throws \Exception
     * 
     * @return array to $fields
     */
    public function deleteField($fieldName = null)
    {
        $this->fields[$fieldName] = [
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
            throw new Exception('You must set a Class Name before make any request.');
        }
    }

    /**
     *  Assert types of fields.
     * 
     * @throws Exception
     */
    public function assertTypes($type = null)
    {
        if ($type !== 'String' &&
            $type !== 'Number' &&
            $type !== 'Boolean' &&
            $type !== 'Date' &&
            $type !== 'File' &&
            $type !== 'GeoPoint' &&
            $type !== 'Array' &&
            $type !== 'Object' &&
            $type !== 'Pointer' &&
            $type !== 'Relation') {
            throw new Exception($type.' is not a valid type.', 1);
        }
    }
}
