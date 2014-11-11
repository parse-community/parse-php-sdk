<?php

namespace Parse;

use Parse\Internal\Encodable;
use Parse\Internal\RemoveOperation;
use Parse\Internal\FieldOperation;
use Parse\Internal\SetOperation;
use Parse\Internal\AddOperation;
use Parse\Internal\AddUniqueOperation;
use Parse\Internal\IncrementOperation;
use Parse\Internal\DeleteOperation;

use \Exception;

/**
 * ParseObject - Representation of an object stored on Parse.
 *
 * @package  Parse
 * @author   Fosco Marotto <fjm@fb.com>
 */
class ParseObject implements Encodable
{

  /**
   * @var array - Data as it exists on the server.
   */
  protected $serverData;
  /**
   * @var array - Set of unsaved operations.
   */
  protected $operationSet;
  /**
   * @var array - Estimated value of applying operationSet to serverData.
   */
  private $estimatedData;
  /**
   * @var array - Determine if data available for a given key or not.
   */
  private $dataAvailability;
  /**
   * @var - Class Name for data on Parse.
   */
  private $className;
  /**
   * @var string - Unique identifier on Parse.
   */
  private $objectId;
  /**
   * @var \DateTime - Timestamp when object was created.
   */
  private $createdAt;
  /**
   * @var \DateTime - Timestamp when object was last updated.
   */
  private $updatedAt;
  /**
   * @var bool - Whether the object has been fully fetched from Parse.
   */
  private $hasBeenFetched;

  /**
   * @var array - Holds the registered subclasses and Parse class names.
   */
  private static $registeredSubclasses = array();

  /**
   * Create a Parse Object
   *
   * Creates a pointer object if an objectId is provided,
   * otherwise creates a new object.
   *
   * @param string $className Class Name for data on Parse.
   * @param mixed  $objectId  Object Id for Existing object.
   * @param bool   $isPointer
   *
   * @throws Exception
   */
  public function __construct($className = null, $objectId = null,
                              $isPointer = false)
  {
    if (empty(self::$registeredSubclasses)) {
      throw new Exception(
        'You must initialize the ParseClient using ParseClient::initialize ' .
        'and your Parse API keys before you can begin working with Objects.'
      );
    }
    $subclass = static::getSubclass();
    $class = get_called_class();
    if (!$className && $subclass !== false) {
      $className = $subclass;
    }
    if ($class !== __CLASS__ && $className !== $subclass) {
      throw new Exception(
        'You must specify a Parse class name or register the appropriate ' .
        'subclass when creating a new Object.  Use ParseObject::create to ' .
        'create a subclass object.'
      );
    }

    $this->className = $className;
    $this->serverData = array();
    $this->operationSet = array();
    $this->estimatedData = array();
    $this->dataAvailability = array();
    if ($objectId || $isPointer) {
      $this->objectId = $objectId;
      $this->hasBeenFetched = false;
    } else {
      $this->hasBeenFetched = true;
    }
  }

  /**
   * Gets the Subclass className if exists, otherwise false.
   */
  private static function getSubclass()
  {
    return array_search(get_called_class(), self::$registeredSubclasses);
  }

  /**
   * Setter to catch property calls and protect certain fields.
   *
   * @param string $key   Key to set a value on.
   * @param mixed  $value Value to assign.
   *
   * @return null
   * @throws Exception
   * @ignore
   */
  public function __set($key, $value)
  {
    if ($key != 'objectId'
      && $key != 'createdAt'
      && $key != 'updatedAt'
      && $key != 'className'
    ) {
      $this->set($key, $value);
    } else {
      throw new Exception('Protected field could not be set.');
    }
  }

  /**
   * Getter to catch direct property calls and pass them to the get function.
   *
   * @param string $key Key to retrieve from the Object.
   *
   * @return mixed
   * @ignore
   */
  public function __get($key)
  {
    return $this->get($key);
  }

  /**
   * Get current value for an object property.
   *
   * @param string $key Key to retrieve from the estimatedData array.
   *
   * @return mixed
   *
   * @throws \Exception
   */
  public function get($key)
  {
    if (!$this->_isDataAvailable($key)) {
      throw new \Exception(
          'ParseObject has no data for this key. Call fetch() to get the data.');
    }
    if (isset($this->estimatedData[$key])) {
      return $this->estimatedData[$key];
    }
    return null;
  }

  /**
   * Check if the object has a given key
   *
   * @param string $key Key to check
   *
   * @return boolean
   */
  public function has($key)
  {
    return isset($this->estimatedData[$key]);
  }

  /**
   * Check if the a value associated with a key has been
   * added/updated/removed and not saved yet.
   *
   * @param string $key
   * @return bool
   */
  public function isKeyDirty($key)
  {
    return isset($this->operationSet[$key]);
  }

  /**
   * Check if the object or any of its child objects have unsaved operations.
   *
   * @return bool
   */
  public function isDirty()
  {
    return $this->_isDirty(true);
  }

  /**
   * Detects if the object (and optionally the child objects) has unsaved
   * changes.
   *
   * @param $considerChildren
   *
   * @return bool
   * @ignore
   */
  protected function _isDirty($considerChildren)
  {
    return
      (count($this->operationSet) || $this->objectId === null) ||
      ($considerChildren && $this->hasDirtyChildren());
  }

  private function hasDirtyChildren()
  {
    $result = false;
    self::traverse(true, $this->estimatedData, function ($object) use (&$result) {
      if ($object instanceof ParseObject) {
        if ($object->isDirty()) {
          $result = true;
        }
      }
    });
    return $result;
  }

  /**
   * Validate and set a value for an object key.
   *
   * @param string $key   Key to set a value for on the object.
   * @param mixed  $value Value to set on the key.
   *
   * @return null
   * @throws Exception
   */
  public function set($key, $value)
  {
    if (!$key) {
      throw new Exception('key may not be null.');
    }
    if (is_array($value)) {
      throw new Exception(
        'Must use setArray() or setAssociativeArray() for this value.'
      ); 
    }
    $this->_performOperation($key, new SetOperation($value));
  }

  /**
   * Set an array value for an object key.
   * 
   * @param string $key Key to set the value for on the object.
   * @param array $value Value to set on the key.
   *                     
   * @return null
   * @throws Exception
   */
  public function setArray($key, $value)
  {
    if (!$key) {
      throw new Exception('key may not be null.');
    }
    if (!is_array($value)) {
      throw new Exception(
        'Must use set() for non-array values.'
      );
    }
    $this->_performOperation($key, new SetOperation($value));
  }

  /**
   * Set an associative array value for an object key.
   *
   * @param string $key Key to set the value for on the object.
   * @param array $value Value to set on the key.
   *
   * @return null
   * @throws Exception
   */
  public function setAssociativeArray($key, $value)
  {
    if (!$key) {
      throw new Exception('key may not be null.');
    }
    if (!is_array($value)) {
      throw new Exception(
        'Must use set() for non-array values.'
      );
    }
    $this->_performOperation($key, new SetOperation($value, true));
  }

  /**
   * Remove a value from an array for an object key.
   *
   * @param string $key Key to remove the value from on the object.
   * @param mixed $value Value to remove from the array.
   *
   * @return null
   * @throws Exception
   */
  public function remove($key, $value)
  {
    if (!$key) {
      throw new Exception('key may not be null.');
    }
    if (!is_array($value)) {
      $value = [$value];
    }
    $this->_performOperation($key, new RemoveOperation($value));
  }

  /**
   * Revert all unsaved operations.
   *
   * @return null
   */
  public function revert()
  {
    $this->operationSet = array();
    $this->rebuildEstimatedData();
  }

  /**
   * Clear all keys on this object by creating delete operations
   * for each key.
   *
   * @return null
   */
  public function clear()
  {
    foreach ($this->estimatedData as $key => $value) {
      $this->delete($key);
    }
  }

  /**
   * Perform an operation on an object property.
   *
   * @param string         $key       Key to perform an operation upon.
   * @param FieldOperation $operation Operation to perform.
   *
   * @return null
   * @ignore
   */
  public function _performOperation($key, FieldOperation $operation)
  {
    $oldValue = null;
    if (isset($this->estimatedData[$key])) {
      $oldValue = $this->estimatedData[$key];
    }
    $newValue = $operation->_apply($oldValue, $this, $key);
    if ($newValue !== null) {
      $this->estimatedData[$key] = $newValue;
    } else if (isset($this->estimatedData[$key])) {
      unset($this->estimatedData[$key]);
    }

    if (isset($this->operationSet[$key])) {
      $oldOperations = $this->operationSet[$key];
      $newOperations = $operation->_mergeWithPrevious($oldOperations);
      $this->operationSet[$key] = $newOperations;
    } else {
      $this->operationSet[$key] = $operation;
    }
    $this->dataAvailability[$key] = true;
  }

  /**
   * Get the Parse Class Name for the object.
   *
   * @return string
   */
  public function getClassName()
  {
    return $this->className;
  }

  /**
   * Get the objectId for the object, or null if unsaved.
   *
   * @return string|null
   */
  public function getObjectId()
  {
    return $this->objectId;
  }

  /**
   * Get the createdAt for the object, or null if unsaved.
   *
   * @return \DateTime|null
   */
  public function getCreatedAt()
  {
    return $this->createdAt;
  }

  /**
   * Returns true if the object has been fetched.
   *
   * @return bool
   */
  public function isDataAvailable()
  {
    return $this->hasBeenFetched;
  }

  private function _isDataAvailable($key)
  {
    return $this->isDataAvailable() || isset($this->dataAvailability[$key]);

  }

  /**
   * Get the updatedAt for the object, or null if unsaved.
   *
   * @return \DateTime|null
   */
  public function getUpdatedAt()
  {
    return $this->updatedAt;
  }

  /**
   * Static method which returns a new Parse Object for a given class
   * Optionally creates a pointer object if the objectId is provided.
   *
   * @param string $className Class Name for data on Parse.
   * @param string $objectId  Unique identifier for existing object.
   * @param bool   $isPointer If the object is a pointer.
   *
   * @return Object
   */
  public static function create($className, $objectId = null,
                                $isPointer = false)
  {
    if (isset(self::$registeredSubclasses[$className])) {
      return new self::$registeredSubclasses[$className](
        $className, $objectId, $isPointer
      );
    } else {
      return new ParseObject($className, $objectId, $isPointer);
    }
  }

  /**
   * Fetch the whole object from the server and update the local object.
   *
   * @return null
   */
  public function fetch()
  {
    $sessionToken = null;
    if (ParseUser::getCurrentUser()) {
      $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
    }
    $response = ParseClient::_request(
      'GET',
      '/1/classes/' . $this->className . '/' . $this->objectId,
      $sessionToken
    );
    $this->_mergeAfterFetch($response);
  }

  /**
   * Merges data received from the server.
   *
   * @param array $result       Data retrieved from the server.
   * @param bool  $completeData Fetch all data or not.
   *
   * @return null
   * @ignore
   */
  public function _mergeAfterFetch($result, $completeData = true)
  {
    // This loop will clear operations for keys provided by the server
    // It will not clear operations for new keys the server doesn't have.
    foreach ($result as $key => $value) {
      if (isset($this->operationSet[$key])) {
        unset($this->operationSet[$key]);
      }
    }
    $this->serverData = array();
    $this->dataAvailability = array();
    $this->mergeFromServer($result, $completeData);
    $this->rebuildEstimatedData();
  }

  /**
   * Merges data received from the server with a given selected keys.
   *
   * @param array  $result       Data retrieved from the server.
   * @param array  $selectedKeys Keys to be fetched. Null or empty means all
   *                             data will be fetched.
   * @return null
   * @ignore
   */
  public function _mergeAfterFetchWithSelectedKeys($result, $selectedKeys)
  {
    $this->_mergeAfterFetch($result, $selectedKeys ? empty($selectedKeys) : true);
    foreach ($selectedKeys as $key) {
      $this->dataAvailability[$key] = true;
    }
  }

  /**
   * Merges data received from the server.
   *
   * @param array $data Data retrieved from server.
   * @param bool $completeData Fetch all data or not.
   *
   * @return null
   */
  private function mergeFromServer($data, $completeData = true)
  {
    $this->hasBeenFetched = ($this->hasBeenFetched || $completeData) ? true : false;
    $this->mergeMagicFields($data);
    foreach ($data as $key => $value) {
      if ($key === '__type' && $value === 'className') {
        continue;
      }

      $decodedValue = ParseClient::_decode($value);

      if (is_array($decodedValue)) {
        if (isset($decodedValue['__type'])) {
          if ($decodedValue['__type'] === 'Relation') {
            $className = $decodedValue['className'];
            $decodedValue = new ParseRelation($this, $key, $className);
          }
        }
        if ($key == 'ACL') {
          $decodedValue = ParseACL::_createACLFromJSON($decodedValue);
        }
      }
      $this->serverData[$key] = $decodedValue;
      $this->dataAvailability[$key] = true;

    }
    if (!$this->updatedAt && $this->createdAt) {
      $this->updatedAt = $this->createdAt;
    }
  }

  /**
   * Handle merging of special fields for the object.
   *
   * @param array &$data Data received from server.
   *
   * @return null
   */
  private function mergeMagicFields(&$data)
  {
    if (isset($data['objectId'])) {
      $this->objectId = $data['objectId'];
      unset($data['objectId']);
    }
    if (isset($data['createdAt'])) {
      $this->createdAt = new \DateTime($data['createdAt']);
      unset($data['createdAt']);
    }
    if (isset($data['updatedAt'])) {
      $this->updatedAt = new \DateTime($data['updatedAt']);
      unset($data['updatedAt']);
    }
    if (isset($data['ACL'])) {
      $acl = ParseACL::_createACLFromJSON($data['ACL']);
      $this->serverData['ACL'] = $acl;
      unset($data['ACL']);
    }

  }

  /**
   * Start from serverData and process operations to generate the current
   * value set for an object.
   *
   * @return null
   */
  protected function rebuildEstimatedData()
  {
    $this->estimatedData = array();
    foreach ($this->serverData as $key => $value) {
      $this->estimatedData[$key] = $value;
    }
    $this->applyOperations($this->operationSet, $this->estimatedData);
  }

  /**
   * Apply operations to a target object
   *
   * @param array $operations Operations set to apply.
   * @param array &$target    Target data to affect.
   *
   * @return null
   */
  private function applyOperations($operations, &$target)
  {
    foreach ($operations as $key => $operation) {
      $oldValue = (isset($target[$key]) ? $target[$key] : null);
      $newValue = $operation->_apply($oldValue, $this, $key);
      if (empty($newValue) && !is_array($newValue)
        && $newValue !== null && !is_scalar($newValue)
      ) {
        unset($target[$key]);
        unset($this->dataAvailability[$key]);
      } else {
        $target[$key] = $newValue;
        $this->dataAvailability[$key] = true;
      }
    }
  }

  /**
   * Delete the object from Parse.
   *
   * @param bool $useMasterKey Whether to use the master key.
   *
   * @return null
   */
  public function destroy($useMasterKey = false)
  {
    if (!$this->objectId) {
      return;
    }
    $sessionToken = null;
    if (ParseUser::getCurrentUser()) {
      $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
    }
    ParseClient::_request(
      'DELETE', '/1/classes/' . $this->className .
      '/' . $this->objectId, $sessionToken, null, $useMasterKey
    );
  }

  /**
   * Delete an array of objects.
   *
   * @param array   $objects      Objects to destroy.
   * @param boolean $useMasterKey Whether to use the master key or not.
   *
   * @throws ParseAggregateException
   * @return null
   */
  public static function destroyAll(array $objects, $useMasterKey = false)
  {
    $errors = [];
    $count = count($objects);
    if ($count) {
      $batchSize = 40;
      $processed = 0;
      $currentBatch = [];
      $currentcount = 0;
      while ($processed < $count) {
        $currentcount++;
        $currentBatch[] = $objects[$processed++];
        if ($currentcount == $batchSize || $processed == $count) {
          $results = static::destroyBatch($currentBatch);
          $errors = array_merge($errors, $results);
          $currentBatch = [];
          $currentcount = 0;
        }
      }
      if (count($errors)) {
        throw new ParseAggregateException(
          "Errors during batch destroy.", $errors
        );
      }
    }
    return null;
  }

  private static function destroyBatch(array $objects, $useMasterKey = false)
  {
    $data = [];
    $errors = [];
    foreach ($objects as $object) {
      $data[] = array(
        "method" => "DELETE",
        "path" => "/1/classes/" . $object->getClassName() .
          "/" . $object->getObjectId()
      );
    }
    $sessionToken = null;
    if (ParseUser::getCurrentUser()) {
      $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
    }
    $result = ParseClient::_request(
      "POST", "/1/batch", $sessionToken,
      json_encode(array("requests" => $data)),
      $useMasterKey
    );
    foreach ($objects as $key => $object) {
      if (isset($result[$key]['error'])) {
        $error = $result[$key]['error']['error'];
        $code = isset($result[$key]['error']['code']) ?
          $result[$key]['error']['code'] : -1;
        $errors[] = array(
          'error' => $error,
          'code' => $code
        );
      }
    }
    return $errors;
  }

  /**
   * Increment a numeric key by a certain value.
   *
   * @param string $key   Key for numeric value on object to increment.
   * @param int    $value Value to increment by.
   *
   * @return null
   */
  public function increment($key, $value = 1)
  {
    $this->_performOperation($key, new IncrementOperation($value));
  }

  /**
   * Add a value to an array property.
   *
   * @param string $key   Key for array value on object to add a value to.
   * @param mixed  $value Value to add.
   *
   * @return null
   */
  public function add($key, $value)
  {
    $this->_performOperation($key, new AddOperation($value));
  }

  /**
   * Add unique values to an array property.
   *
   * @param string $key   Key for array value on object.
   * @param mixed  $value Value list to add uniquely.
   *
   * @return null
   */
  public function addUnique($key, $value)
  {
    $this->_performOperation($key, new AddUniqueOperation($value));
  }

  /**
   * Delete a key from an object.
   *
   * @param string $key Key to remove from object.
   *
   * @return null
   */
  public function delete($key)
  {
    $this->_performOperation($key, new DeleteOperation());
  }

  /**
   * Return a JSON encoded value of the object.
   *
   * @return string
   * @ignore
   */
  public function _encode()
  {
    $out = array();
    if ($this->objectId) {
      $out['objectId'] = $this->objectId;
    }
    if ($this->createdAt) {
      $out['createdAt'] = $this->createdAt;
    }
    if ($this->updatedAt) {
      $out['updatedAt'] = $this->updatedAt;
    }
    foreach ($this->serverData as $key => $value) {
      $out[$key] = $value;
    }
    foreach ($this->estimatedData as $key => $value) {
      if (is_object($value) && $value instanceof \Parse\Internal\Encodable) {
        $out[$key] = $value->_encode();
      } else if (is_array($value)) {
        $out[$key] = array();
        foreach ($value as $item) {
          if (is_object($item) && $item instanceof \Parse\Internal\Encodable) {
            $out[$key][] = $item->_encode();
          } else {
            $out[$key][] = $item;
          }
        }
      } else {
        $out[$key] = $value;
      }
    }
    return json_encode($out);
  }

  /**
   * Returns JSON object of the unsaved operations.
   *
   * @return array
   */
  private function getSaveJSON()
  {
    return ParseClient::_encode($this->operationSet, true);
  }

  /**
   * Save Object to Parse
   *
   * @param bool   $useMasterKey Whether to use the Master Key.
   *
   * @return null
   */
  public function save($useMasterKey = false)
  {
    if (!$this->isDirty()) {
      return;
    }
    static::deepSave($this, $useMasterKey);
  }

  /**
   * Save all the objects in the provided array
   *
   * @param array $list
   * @param bool   $useMasterKey Whether to use the Master Key.
   *
   * @return null
   */
  public static function saveAll($list, $useMasterKey = false)
  {
    static::deepSave($list, $useMasterKey);
  }

  /**
   * Save Object and unsaved children within.
   *
   * @param $target
   * @param bool   $useMasterKey Whether to use the Master Key.
   *
   * @return null
   *
   * @throws ParseException
   */
  private static function deepSave($target, $useMasterKey = false)
  {
    $unsavedChildren = array();
    $unsavedFiles = array();
    static::findUnsavedChildren($target, $unsavedChildren, $unsavedFiles);
    $sessionToken = null;
    if (ParseUser::getCurrentUser()) {
      $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
    }

    foreach ($unsavedFiles as &$file) {
      $file->save();
    }

    $objects = array();
    // Get the set of unique objects among the children.
    foreach ($unsavedChildren as &$obj) {
      if (!in_array($obj, $objects, true)) {
        $objects[] = $obj;
      }
    }
    $remaining = $objects;

    while (count($remaining) > 0) {

      $batch = array();
      $newRemaining = array();

      foreach ($remaining as $key => &$object) {
        if (count($batch) > 40) {
          $newRemaining[] = $object;
          continue;
        }
        if ($object->canBeSerialized()) {
          $batch[] = $object;
        } else {
          $newRemaining[] = $object;
        }
      }
      $remaining = $newRemaining;

      if (count($batch) === 0) {
        throw new Exception("Tried to save a batch with a cycle.");
      }

      $requests = array();
      foreach ($batch as $obj) {
        $json = $obj->getSaveJSON();
        $method = 'POST';
        $path = '/1/classes/' . $obj->getClassName();
        if ($obj->getObjectId()) {
          $path .= '/' . $obj->getObjectId();
          $method = 'PUT';
        }
        $requests[] = array('method' => $method,
          'path' => $path,
          'body' => $json
        );
      }

      if (count($requests) === 1) {
        $req = $requests[0];
        $result = ParseClient::_request($req['method'],
          $req['path'], $sessionToken, json_encode($req['body']), $useMasterKey);
        $batch[0]->mergeAfterSave($result);
      } else {
        $result = ParseClient::_request('POST', '/1/batch', $sessionToken,
          json_encode(array("requests" => $requests)), $useMasterKey);

        $errorCollection = array();

        foreach ($batch as $key => &$obj) {
          if (isset($result[$key]['success'])) {
            $obj->mergeAfterSave($result[$key]['success']);
          } else if (isset($result[$key]['error'])) {
            $response = $result[$key];
            $error = $response['error']['error'];
            $code = isset($response['error']['code']) ?
              $response['error']['code'] : -1;
            $errorCollection[] = array(
              'error' => $error,
              'code' => $code,
              'object' => $obj
            );
          } else {
            $errorCollection[] = array(
              'error' => 'Unknown error in batch save.',
              'code' => -1,
              'object' => $obj
            );
          }
        }
        if (count($errorCollection)) {
          throw new ParseAggregateException(
              "Errors during batch save.", $errorCollection
          );
        }
      }
    }
  }

  /**
   * Find unsaved children inside an object.
   *
   * @param ParseObject $object           Object to search.
   * @param array       &$unsavedChildren Array to populate with children.
   * @param array       &$unsavedFiles    Array to populate with files.
   */
  private static function findUnsavedChildren($object,
                                               &$unsavedChildren, &$unsavedFiles)
  {
    static::traverse(true, $object, function ($obj) use (
      &$unsavedChildren,
      &$unsavedFiles
    ) {
      if ($obj instanceof ParseObject) {
        if ($obj->_isDirty(false)) {
          $unsavedChildren[] = $obj;
        }
      } else if ($obj instanceof ParseFile) {
        if (!$obj->getURL()) {
          $unsavedFiles[] = $obj;
        }
      }

    });
  }

  /**
   * Traverse object to find children.
   *
   * @param boolean           $deep        Should this call traverse deeply
   * @param ParseObject|array &$object     Object to traverse.
   * @param callable          $mapFunction Function to call for every item.
   * @param array             $seen        Objects already seen.
   *
   * @return mixed The result of calling mapFunction on the root object.
   */
  private static function traverse($deep, &$object, $mapFunction,
                                    $seen = array())
  {
    if ($object instanceof ParseObject) {
      if (in_array($object, $seen, true)) {
        return null;
      }
      $seen[] = $object;
      if ($deep) {
        self::traverse(
          $deep, $object->estimatedData, $mapFunction, $seen
        );
      }
      return $mapFunction($object);
    }
    if ($object instanceof ParseRelation || $object instanceof ParseFile) {
      return $mapFunction($object);
    }
    if (is_array($object)) {
      foreach ($object as $key => $value) {
        self::traverse($deep, $value, $mapFunction, $seen);
      }
      return $mapFunction($object);
    }
    return $mapFunction($object);
  }

  /**
   * Determine if the current object can be serialized for saving.
   *
   * @return bool
   */
  private function canBeSerialized()
  {
    return self::canBeSerializedAsValue($this->estimatedData);
  }

  /**
   * Checks the given object and any children to see if the whole object
   * can be serialized for saving.
   *
   * @param mixed $object The value to check.
   *
   * @return bool
   */
  private static function canBeSerializedAsValue($object)
  {
    $result = true;
    self::traverse(false, $object, function ($obj) use (&$result) {
      // short circuit as soon as possible.
      if ($result === false) {
        return;
      }
      // cannot make a pointer to an unsaved object.
      if ($obj instanceof ParseObject) {
        if (!$obj->getObjectId()) {
          $result = false;
          return;
        }
      }
    });
    return $result;
  }

  /**
   * Merge server data after a save completes.
   *
   * @param array $result      Data retrieved from server.
   *
   * @return null
   */
  private function mergeAfterSave($result)
  {
    $this->applyOperations($this->operationSet, $this->serverData);
    $this->mergeFromServer($result);
    $this->operationSet = array();
    $this->rebuildEstimatedData();
  }

  /**
   * Access or create a Relation value for a key.
   *
   * @param string $key The key to access the relation for.
   * @return ParseRelation The ParseRelation object if the relation already
   *                       exists for the key or can be created for this key.
   */
  public function getRelation($key)
  {
    $relation = new ParseRelation($this, $key);
    if (isset($this->estimatedData[$key])) {
      $object = $this->estimatedData[$key];
      if ($object instanceof ParseRelation) {
        $relation->setTargetClass($object->getTargetClass());
      }
    }
    return $relation;
  }

  /**
   * Gets a Pointer referencing this Object.
   *
   * @return array
   *
   * @throws \Exception
   * @ignore
   */
  public function _toPointer()
  {
    if (!$this->objectId) {
      throw new \Exception("Can't serialize an unsaved Parse.Object");
    }
    return array(
        '__type' => "Pointer",
        'className' => $this->className,
        'objectId' => $this->objectId);
  }

  /**
   * Set ACL for this object.
   *
   * @param ParseACL $acl
   */
  public function setACL($acl)
  {
    $this->_performOperation('ACL', new SetOperation($acl));
  }

  /**
   * Get ACL assigned to the object.
   *
   * @return ParseACL
   */
  public function getACL()
  {
    return $this->getACLWithCopy(true);
  }

  private function getACLWithCopy($mayCopy)
  {
    if (!isset($this->estimatedData['ACL'])) {
      return null;
    }
    $acl = $this->estimatedData['ACL'];
    if ($mayCopy && $acl->_isShared()) {
      return clone $acl;
    }
    return $acl;
  }

  /**
   * Register a subclass.  Should be called before any other Parse functions.
   * Cannot be called on the base class ParseObject.
   * @throws \Exception
   */
  public static function registerSubclass()
  {
    if (isset(static::$parseClassName)) {
      if (!in_array(static::$parseClassName, self::$registeredSubclasses)) {
        self::$registeredSubclasses[static::$parseClassName] =
          get_called_class();
      }
    } else {
      throw new \Exception(
        "Cannot register a subclass that does not have a parseClassName"
      );
    }
  }

  /**
   * Un-register a subclass.
   * Cannot be called on the base class ParseObject.
   * @ignore
   */
  public static function _unregisterSubclass()
  {
    $subclass = static::getSubclass();
    unset(self::$registeredSubclasses[$subclass]);
  }

  /**
   * Creates a ParseQuery for the subclass of ParseObject.
   * Cannot be called on the base class ParseObject.
   *
   * @return ParseQuery
   *
   * @throws \Exception
   */
  public static function query()
  {
    $subclass = static::getSubclass();
    if ($subclass === false) {
      throw new Exception(
        'Cannot create a query for an unregistered subclass.'
      );
    } else {
      return new ParseQuery($subclass);
    }
  }

}
