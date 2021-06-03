<?php
/**
 * Class ParseQuery | Parse/ParseQuery.php
 */

namespace Parse;

use Exception;

/**
 * Class ParseQuery - Handles querying data from Parse.
 *
 * @author Fosco Marotto <fjm@fb.com>
 * @package Parse
 */
class ParseQuery
{
    /**
     * Class name for data stored on Parse.
     *
     * @var string
     */
    private $className;

    /**
     * Where constraints.
     *
     * @var array
     */
    private $where = [];

    /**
     * Order By keys.
     *
     * @var array
     */
    private $orderBy = [];

    /**
     * Include nested objects.
     *
     * @var array
     */
    private $includes = [];

    /**
     * Exclude keys.
     *
     * @var array
     */
    private $excludes = [];

    /**
     * Include certain keys only.
     *
     * @var array
     */
    private $selectedKeys = [];

    /**
     * Skip from the beginning of the search results.
     *
     * @var int
     */
    private $skip = 0;

    /**
     * Determines if the query is a count query or a results query.
     *
     * @var int
     */
    private $count;

    /**
     * Limit of results, defaults to 100 when not explicitly set.
     *
     * @var int
     */
    private $limit = -1;

    /**
     * The read preference for the main query.
     *
     * @var string
     */
    private $readPreference;

    /**
     * The read preference for the queries to include pointers.
     *
     * @var string
     */
    private $includeReadPreference;

    /**
     * The read preference for the sub queries.
     *
     * @var string
     */
    private $subqueryReadPreference;

    /**
     * Create a Parse Query for a given Parse Class.
     *
     * @param mixed $className Class Name of data on Parse.
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * Execute a query to retrieve a specific object.
     *
     * @param string $objectId     Unique object id to retrieve.
     * @param bool   $useMasterKey If the query should use the master key
     *
     * @throws ParseException
     *
     * @return array|ParseObject Returns the selected object or an empty array
     */
    public function get($objectId, $useMasterKey = false)
    {
        $this->equalTo('objectId', $objectId);
        $result = $this->first($useMasterKey);
        if (empty($result)) {
            throw new ParseException('Object not found.', 101);
        }

        return $result;
    }

    /**
     * Set a constraint for a field matching a given value.
     *
     * @param string $key   Key to set up an equals constraint.
     * @param mixed  $value Value the key must equal.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function equalTo($key, $value)
    {
        $this->where[$key] = $value;

        return $this;
    }

    /**
     * Helper for condition queries.
     *
     * @param string $key       The key to where constraints
     * @param string $condition The condition name
     * @param mixed  $value     The condition value, can be a string or an array of strings
     *
     * @throws Exception
     */
    private function addCondition($key, $condition, $value)
    {
        if (!isset($this->where[$key])) {
            $this->where[$key] = [];
        }
        $this->where[$key][$condition] = ParseClient::_encode($value, true);
    }

    /**
     * Sets the conditions of this parse query from an array
     *
     * @param array $conditions Array of Conditions to set
     * @throws ParseException
     */
    public function _setConditions($conditions)
    {
        if (!is_array($conditions)) {
            throw new ParseException("Conditions must be in an array");
        }

        // iterate over and add each condition
        foreach ($conditions as $key => $entry) {
            switch ($key) {
                case 'where':
                    $this->where = $entry;
                    break;

                case 'include':
                    $this->includes = explode(',', $entry);
                    break;

                case 'excludeKeys':
                    $this->excludes = explode(',', $entry);
                    break;

                case 'keys':
                    $this->selectedKeys = explode(',', $entry);
                    break;

                case 'limit':
                    $this->limit = $entry;
                    break;

                case 'readPreference':
                    $this->readPreference = $entry;
                    break;

                case 'includeReadPreference':
                    $this->includeReadPreference = $entry;
                    break;

                case 'subqueryReadPreference':
                    $this->subqueryReadPreference = $entry;
                    break;

                // skip
                case 'skip':
                    $this->skip = $entry;
                    break;

                // orderBy
                case 'order':
                    $this->orderBy = explode(',', $entry);
                    break;

                // whether this query is for count or not
                case 'count':
                    $this->count = $entry;
                    break;

                default:
                    throw new ParseException("Unknown condition to set '{$key}''");
            }
        }
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be not equal to the provided value.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The value that must not be equalled.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function notEqualTo($key, $value)
    {
        $this->addCondition($key, '$ne', $value);

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be less than the provided value.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The value that provides an Upper bound.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function lessThan($key, $value)
    {
        $this->addCondition($key, '$lt', $value);

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be less than the provided relative time string.
     *
     * @param string $key           The key to check
     * @param string $relativeTime  The relative time that provides an upper bound
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function lessThanRelativeTime($key, $relativeTime)
    {
        $this->lessThan($key, [
            '$relativeTime' => $relativeTime
        ]);

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be greater than the provided value.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The value that provides an Lower bound.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function greaterThan($key, $value)
    {
        $this->addCondition($key, '$gt', $value);

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be greater than the provided relative time string.
     *
     * @param string $key          The key to check
     * @param string $relativeTime The relative time that provides a lower bound
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function greaterThanRelativeTime($key, $relativeTime)
    {
        $this->greaterThan($key, [
            '$relativeTime' => $relativeTime
        ]);

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be greater than or equal to the provided value.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The value that provides a lower bound.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function greaterThanOrEqualTo($key, $value)
    {
        $this->addCondition($key, '$gte', $value);

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be greater than or equal to the provided relative time string.
     *
     * @param string $key           The key to check.
     * @param string $relativeTime  The relative time that provides a lower bound
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function greaterThanOrEqualToRelativeTime($key, $relativeTime)
    {
        $this->greaterThanOrEqualTo($key, [
            '$relativeTime' => $relativeTime
        ]);

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be less than or equal to the provided value.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The value that provides an upper bound.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function lessThanOrEqualTo($key, $value)
    {
        $this->addCondition($key, '$lte', $value);

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be less than or equal to the provided date string.
     *
     * @param string $key           The key to check.
     * @param string $relativeTime  The relative time that provides an upper bound
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function lessThanOrEqualToRelativeTime($key, $relativeTime)
    {
        $this->lessThanOrEqualTo($key, [
            '$relativeTime' => $relativeTime
        ]);

        return $this;
    }

    /**
     * Converts a string into a regex that matches it.
     * Surrounding with \Q .. \E does this, we just need to escape \E's in
     * the text separately.
     *
     * @param mixed $s The string or array being replaced.
     *
     * @return string Returns the string converted.
     */
    private function quote($s)
    {
        return '\\Q'.str_replace('\\E', '\\E\\\\E\\Q', $s).'\\E';
    }

    /**
     * Converts a string into a regex that matches it at the beginning
     *
     * @param mixed $s The string or array being replaced.
     *
     * @return string Returns the string converted.
     */
    private function regexStartWith($s)
    {
        return '^' . $this->quote($s);
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * start with the provided value.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The substring that the value must start with.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function startsWith($key, $value)
    {
        $this->addCondition($key, '$regex', $this->regexStartWith($value));

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * end with the provided value.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The substring that the value must end with.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function endsWith($key, $value)
    {
        $this->addCondition($key, '$regex', $this->quote($value).'$');

        return $this;
    }

    /**
     * Adds a constraint for finding string values that contain a provided
     * string. This may be slow for large datasets.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The substring that the value must contain.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function contains($key, $value)
    {
        $this->addCondition($key, '$regex', $this->quote($value));

        return $this;
    }

    /**
     * Adds a constraint to the query that requires a particular key's value to
     * be contained by the provided list of values. Get objects where all array elements match.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The values that will match.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function containedBy($key, $value)
    {
        $this->addCondition($key, '$containedBy', $value);

        return $this;
    }

    /**
     * Adds a constraint for finding string values that contain a provided
     * string using Full Text Search
     *
     * @param string $key   The key to check.
     * @param mixed  $value The substring that the value must contain.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function fullText($key, $value)
    {
        $this->addCondition(
            $key,
            '$text',
            ['$search' => ['$term' => $value]]
        );

        return $this;
    }

    /**
     * Returns an associative array of the query constraints.
     *
     * @return array
     */
    public function _getOptions()
    {
        $opts = [];
        if (!empty($this->where)) {
            $opts['where'] = $this->where;
        }
        if (count($this->includes)) {
            $opts['include'] = implode(',', $this->includes);
        }
        if (count($this->excludes)) {
            $opts['excludeKeys'] = implode(',', $this->excludes);
        }
        if (count($this->selectedKeys)) {
            $opts['keys'] = implode(',', $this->selectedKeys);
        }
        if ($this->limit >= 0) {
            $opts['limit'] = $this->limit;
        }
        if ($this->skip > 0) {
            $opts['skip'] = $this->skip;
        }
        if ($this->orderBy) {
            $opts['order'] = implode(',', $this->orderBy);
        }
        if ($this->count) {
            $opts['count'] = $this->count;
        }
        if ($this->readPreference) {
            $opts['readPreference'] = $this->readPreference;
        }
        if ($this->includeReadPreference) {
            $opts['includeReadPreference'] = $this->includeReadPreference;
        }
        if ($this->subqueryReadPreference) {
            $opts['subqueryReadPreference'] = $this->subqueryReadPreference;
        }

        return $opts;
    }

    /**
     * Execute a query to get only the first result.
     *
     * @param bool $useMasterKey If the query should use the master key
     * @param bool $decodeObjects If set to false, will return raw data instead of ParseObject instances
     *
     * @return array|ParseObject Returns the first object or an empty array
     */
    public function first($useMasterKey = false, $decodeObjects = true)
    {
        $this->limit = 1;
        $result = $this->find($useMasterKey, $decodeObjects);
        if (count($result)) {
            return $result[0];
        } else {
            return [];
        }
    }

    /**
     * Build query string from query constraints.
     *
     * @param array $queryOptions Associative array of the query constraints.
     *
     * @return string Query string.
     */
    private function buildQueryString($queryOptions)
    {
        if (isset($queryOptions['where'])) {
            $queryOptions['where'] = ParseClient::_encode($queryOptions['where'], true);
            $queryOptions['where'] = json_encode($queryOptions['where']);
        }

        return http_build_query($queryOptions, null, '&');
    }

    /**
     * Execute a count query and return the count.
     *
     * @param bool $useMasterKey If the query should use the master key
     *
     * @return int
     */
    public function count($useMasterKey = false)
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }
        $this->limit = 0;
        $this->count = 1;
        $queryString = $this->buildQueryString($this->_getOptions());
        $result = ParseClient::_request(
            'GET',
            'classes/'.$this->className.'?'.$queryString,
            $sessionToken,
            null,
            $useMasterKey
        );

        return $result['count'];
    }

    /**
     * The response will include the total number of objects satisfying this query,
     * dispite limit / skip. Might be useful for pagination.
     *
     * Note: the results will be an object
     * `results`: holding {ParseObject} array and `count`: integer holding total number
     *
     * @param bool $includeCount If response should include count, true by default.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function withCount($includeCount = true)
    {
        $this->count = (int)$includeCount;
        return $this;
    }

    /**
     * Execute a distinct query and return unique values.
     *
     * @param string $key field to find distinct values
     *
     * @return array
     */
    public function distinct($key)
    {
        $sessionToken = null;
        if ($user = ParseUser::getCurrentUser()) {
            $sessionToken = $user->getSessionToken();
        }
         $opts = [];
        if (!empty($this->where)) {
            $opts['where'] = $this->where;
        }
        $opts['distinct'] = $key;
        $queryString = $this->buildQueryString($opts);
        $result = ParseClient::_request(
            'GET',
            'aggregate/'.$this->className.'?'.$queryString,
            $sessionToken,
            null,
            true
        );

        return $result['results'];
    }

    /**
     * Execute an aggregate query and returns aggregate results.
     *
     * @param array $pipeline stages to process query
     *
     * @return array
     */
    public function aggregate($pipeline)
    {
        $sessionToken = null;
        if ($user = ParseUser::getCurrentUser()) {
            $sessionToken = $user->getSessionToken();
        }
        $stages = [];
        foreach ($pipeline as $stage => $value) {
            $stages[$stage] = json_encode($value);
        }
        $queryString = $this->buildQueryString($stages);
        $result = ParseClient::_request(
            'GET',
            'aggregate/'.$this->className.'?'.$queryString,
            $sessionToken,
            null,
            true
        );
        if (!isset($result['results'])) {
            return [];
        }
        return $result['results'];
    }

    /**
     * Execute a find query and return the results.
     *
     * @param bool $useMasterKey
     * @param bool $decodeObjects If set to false, will return raw data instead of ParseObject instances
     *
     * @return ParseObject[]
     */
    public function find($useMasterKey = false, $decodeObjects = true)
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }
        $queryString = $this->buildQueryString($this->_getOptions());
        $result = ParseClient::_request(
            'GET',
            'classes/'.$this->className.'?'.$queryString,
            $sessionToken,
            null,
            $useMasterKey
        );

        $response = [];
        if (isset($result['count'])) {
            $response['count'] = $result['count'];
            $response['results'] = $this->handleQueryResult($result, $decodeObjects);
            return $response;
        }

        return $this->handleQueryResult($result, $decodeObjects);
    }

    /**
     * Handles result from ParseClient::_request
     *
     * @param array $result Array of ParseObject raw data.
     * @param bool $decodeObjects If set to false, will return raw data instead of ParseObject instances
     *
     * @return Array Array of ParseObjects or raw data.
     */
    public function handleQueryResult($result, $decodeObjects)
    {
        if (!isset($result['results'])) {
            return [];
        }
        if (!$decodeObjects) {
            return $result['results'];
        }
        $output = [];
        foreach ($result['results'] as $row) {
            $obj = ParseObject::create($this->className, $row['objectId']);
            $obj->_mergeAfterFetchWithSelectedKeys($row, $this->selectedKeys);
            $output[] = $obj;
        }

        return $output;
    }

    /**
     * Set the skip parameter as a query constraint.
     *
     * @param int $n Number of objects to skip from start of results.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function skip($n)
    {
        $this->skip = $n;

        return $this;
    }

    /**
     * Set the limit parameter as a query constraint.
     *
     * @param int $n Number of objects to return from the query.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function limit($n)
    {
        $this->limit = $n;

        return $this;
    }

    /**
     * Set the query orderBy to ascending for the given key(s). It overwrites the
     * existing order criteria.
     *
     * @param mixed $key Key(s) to sort by, which is a string or an array of strings.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function ascending($key)
    {
        $this->orderBy = [];

        return $this->addAscending($key);
    }

    /**
     * Set the query orderBy to ascending for the given key(s). It can also add
     * secondary sort descriptors without overwriting the existing order.
     *
     * @param mixed $key Key(s) to sort by, which is a string or an array of strings.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function addAscending($key)
    {
        if (is_array($key)) {
            $this->orderBy = array_merge($this->orderBy, $key);
        } else {
            $this->orderBy[] = $key;
        }

        return $this;
    }

    /**
     * Set the query orderBy to descending for a given key(s). It overwrites the
     * existing order criteria.
     *
     * @param mixed $key Key(s) to sort by, which is a string or an array of strings.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function descending($key)
    {
        $this->orderBy = [];

        return $this->addDescending($key);
    }

    /**
     * Set the query orderBy to descending for a given key(s). It can also add
     * secondary sort descriptors without overwriting the existing order.
     *
     * @param mixed $key Key(s) to sort by, which is a string or an array of strings.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function addDescending($key)
    {
        if (is_array($key)) {
            $key = array_map(
                function ($element) {
                    return '-'.$element;
                },
                $key
            );
            $this->orderBy = array_merge($this->orderBy, $key);
        } else {
            $this->orderBy[] = '-'.$key;
        }

        return $this;
    }

    /**
     * Add a proximity based constraint for finding objects with key point
     * values near the point given.
     *
     * @param string        $key   The key that the ParseGeoPoint is stored in.
     * @param ParseGeoPoint $point The reference ParseGeoPoint that is used.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function near($key, $point)
    {
        $this->addCondition($key, '$nearSphere', $point);

        return $this;
    }

    /**
     * Add a proximity based constraint for finding objects with key point
     * values near the point given and within the maximum distance given.
     *
     * @param string        $key         The key of the ParseGeoPoint
     * @param ParseGeoPoint $point       The ParseGeoPoint that is used.
     * @param int           $maxDistance Maximum distance (in radians)
     * @param bool          $sort        Return objects sorted by distance
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function withinRadians($key, $point, $maxDistance, $sort = true)
    {
        if ($sort) {
            $this->near($key, $point);
            $this->addCondition($key, '$maxDistance', $maxDistance);
        } else {
            $this->addCondition(
                $key,
                '$geoWithin',
                [
                '$centerSphere' => [
                    [$point->getLongitude(), $point->getLatitude()],
                    $maxDistance
                ]
                ]
            );
        }

        return $this;
    }

    /**
     * Add a proximity based constraint for finding objects with key point
     * values near the point given and within the maximum distance given.
     * Radius of earth used is 3958.8 miles.
     *
     * @param string        $key         The key of the ParseGeoPoint
     * @param ParseGeoPoint $point       The ParseGeoPoint that is used.
     * @param int           $maxDistance Maximum distance (in miles)
     * @param bool          $sort        Return objects sorted by distance
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function withinMiles($key, $point, $maxDistance, $sort = true)
    {
        return $this->withinRadians($key, $point, $maxDistance / 3958.8, $sort);
    }

    /**
     * Add a proximity based constraint for finding objects with key point
     * values near the point given and within the maximum distance given.
     * Radius of earth used is 6371.0 kilometers.
     *
     * @param string        $key         The key of the ParseGeoPoint
     * @param ParseGeoPoint $point       The ParseGeoPoint that is used.
     * @param int           $maxDistance Maximum distance (in kilometers)
     * @param bool          $sort        Return objects sorted by distance
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function withinKilometers($key, $point, $maxDistance, $sort = true)
    {
        return $this->withinRadians($key, $point, $maxDistance / 6371.0, $sort);
    }

    /**
     * Add a constraint to the query that requires a particular key's
     * coordinates be contained within a given rectangular geographic bounding
     * box.
     *
     * @param string        $key       The key of the ParseGeoPoint
     * @param ParseGeoPoint $southwest The lower-left corner of the box.
     * @param ParseGeoPoint $northeast The upper-right corner of the box.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function withinGeoBox($key, $southwest, $northeast)
    {
        $this->addCondition(
            $key,
            '$within',
            ['$box' => [$southwest, $northeast]]
        );

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's
     * coordinates be contained within and on the bounds of a given polygon
     * Supports closed and open (last point is connected to first) paths
     *
     * Polygon must have at least 3 points
     *
     * @param string        $key       The key of the ParseGeoPoint
     * @param array         $points    Array of ParseGeoPoint
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function withinPolygon($key, $points)
    {
        $this->addCondition(
            $key,
            '$geoWithin',
            ['$polygon' => $points]
        );

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's
     * coordinates that contains a ParseGeoPoint
     *
     * @param string        $key      The key of the ParsePolygon
     * @param ParseGeoPoint $point    The point that will be contained.
     *
     * @return ParseQuery Returns this query, so you can chain this call.
     */
    public function polygonContains($key, $point)
    {
        $this->addCondition(
            $key,
            '$geoIntersects',
            ['$point' => $point]
        );

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be contained in the provided list of values.
     *
     * @param string $key    The key to check.
     * @param array  $values The values that will match.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function containedIn($key, $values)
    {
        $this->addCondition($key, '$in', $values);

        return $this;
    }

    /**
     * Iterates over each result of a query, calling a callback for each one. The
     * items are processed in an unspecified order. The query may not have any
     * sort order, and may not use limit or skip.
     *
     * @param callable $callback     Callback that will be called with each result
     *                               of the query.
     * @param bool     $useMasterKey
     * @param int      $batchSize
     *
     * @throws \Exception If query has sort, skip, or limit.
     */
    public function each($callback, $useMasterKey = false, $batchSize = 100)
    {
        if ($this->orderBy || $this->skip || ($this->limit >= 0)) {
            throw new Exception(
                'Cannot iterate on a query with sort, skip, or limit.'
            );
        }
        $query = new self($this->className);
        $query->where = $this->where;
        $query->includes = $this->includes;
        $query->limit = $batchSize;
        $query->ascending('objectId');

        $finished = false;
        while (!$finished) {
            $results = $query->find($useMasterKey);
            $length = count($results);
            for ($i = 0; $i < $length; $i++) {
                $callback($results[$i]);
            }
            if ($length == $query->limit) {
                $query->greaterThan('objectId', $results[$length - 1]->getObjectId());
            } else {
                $finished = true;
            }
        }
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * not be contained in the provided list of values.
     *
     * @param string $key    The key to check.
     * @param array  $values The values that will not match.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function notContainedIn($key, $values)
    {
        $this->addCondition($key, '$nin', $values);

        return $this;
    }

    /**
     * Adds a regular expression constraint for finding string values that match
     * the provided regular expression.
     * This may be slow for large datasets.
     *
     * @param string $key       The key that the string to match is stored in.
     * @param string $regex     The regular expression pattern to match.
     * @param string $modifiers  Modifies the search, supports i, m
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function matches($key, $regex, $modifiers = '')
    {
        $this->addCondition($key, '$regex', $regex);
        if (strlen($modifiers)) {
            $this->addCondition($key, '$options', $modifiers);
        }

        return $this;
    }

    /**
     * Add a constraint that requires that a key's value matches a ParseQuery
     * constraint.
     *
     * @param string     $key   The key that the contains the object to match
     *                          the query.
     * @param ParseQuery $query The query that should match.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function matchesQuery($key, $query)
    {
        $queryParam = $query->_getOptions();
        $queryParam['className'] = $query->className;
        $this->addCondition($key, '$inQuery', $queryParam);

        return $this;
    }

    /**
     * Add a constraint that requires that a key's value not matches a ParseQuery
     * constraint.
     *
     * @param string     $key   The key that the contains the object not to
     *                          match the query.
     * @param ParseQuery $query The query that should not match.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function doesNotMatchQuery($key, $query)
    {
        $queryParam = $query->_getOptions();
        $queryParam['className'] = $query->className;
        $this->addCondition($key, '$notInQuery', $queryParam);

        return $this;
    }

    /**
     * Add a constraint that requires that a key's value matches a value in an
     * object returned by the given query.
     *
     * @param string     $key      The key that contains the value that is being
     *                             matched.
     * @param string     $queryKey The key in objects returned by the query to
     *                             match against.
     * @param ParseQuery $query    The query to run.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function matchesKeyInQuery($key, $queryKey, $query)
    {
        $queryParam = $query->_getOptions();
        $queryParam['className'] = $query->className;
        $this->addCondition(
            $key,
            '$select',
            ['key' => $queryKey, 'query' => $queryParam]
        );

        return $this;
    }

    /**
     * Add a constraint that requires that a key's value not match a value in an
     * object returned by the given query.
     *
     * @param string     $key      The key that contains teh value that is being
     *                             excluded.
     * @param string     $queryKey The key in objects returned by the query to
     *                             match against.
     * @param ParseQuery $query    The query to run.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function doesNotMatchKeyInQuery($key, $queryKey, $query)
    {
        $queryParam = $query->_getOptions();
        $queryParam['className'] = $query->className;
        $this->addCondition(
            $key,
            '$dontSelect',
            ['key' => $queryKey, 'query' => $queryParam]
        );

        return $this;
    }

    /**
     * Constructs a ParseQuery object that is the OR of the passed in queries objects.
     * All queries must have same class name.
     *
     * @param array $queryObjects Array of ParseQuery objects to OR.
     *
     * @throws \Exception If all queries don't have same class.
     *
     * @return ParseQuery The query that is the OR of the passed in queries.
     */
    public static function orQueries($queryObjects)
    {
        $className = self::_matchClassName($queryObjects);
        $query = new self($className);
        $query->_or($queryObjects);

        return $query;
    }

    /**
     * Constructs a ParseQuery object that is the NOR of the passed in queries objects.
     * All queries must have same class name.
     *
     * @param array $queryObjects Array of ParseQuery objects to NOR.
     *
     * @throws \Exception If all queries don't have same class.
     *
     * @return ParseQuery The query that is the NOR of the passed in queries.
     */
    public static function norQueries($queryObjects)
    {
        $className = self::_matchClassName($queryObjects);
        $query = new self($className);
        $query->_nor($queryObjects);

        return $query;
    }

    /**
     * Constructs a ParseQuery object that is the AND of the passed in queries objects.
     * All queries must have same class name.
     *
     * @param array $queryObjects Array of ParseQuery objects to AND.
     *
     * @throws \Exception If all queries don't have same class.
     *
     * @return ParseQuery The query that is the AND of the passed in queries.
     */
    public static function andQueries($queryObjects)
    {
        $className = self::_matchClassName($queryObjects);
        $query = new self($className);
        $query->_and($queryObjects);

        return $query;
    }

    /**
     * All queries must have same class name.
     *
     * @param array $queryObjects Array of ParseQuery objects.
     *
     * @throws \Exception If all queries don't have same class.
     *
     * @return string class name.
     */
    private static function _matchClassname($queryObjects)
    {
        $className = null;
        $length = count($queryObjects);
        for ($i = 0; $i < $length; $i++) {
            if (is_null($className)) {
                $className = $queryObjects[$i]->className;
            }
            if ($className != $queryObjects[$i]->className) {
                throw new Exception('All queries must be for the same class', 103);
            }
        }
        return $className;
    }

    /**
     * Add constraint that at least one of the passed in queries matches.
     *
     * @param array $queries The list of queries to OR.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    private function _or($queries)
    {
        return $this->_mergeQueries('$or', $queries);
    }

    /**
     * Add constraint that at none of the passed in queries matches.
     *
     * @param array $queries The list of queries to NOR.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    private function _nor($queries)
    {
        return $this->_mergeQueries('$nor', $queries);
    }

    /**
     * Add constraint that at all of the passed in queries matches.
     *
     * @param array $queries The list of queries to OR.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    private function _and($queries)
    {
        return $this->_mergeQueries('$and', $queries);
    }

    /**
     * Combines queries for NOR, AND, OR queries.
     *
     * @param string $key The condition $and, $or, $nor.
     * @param array $queries The list of queries to combine.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    private function _mergeQueries($key, $queries)
    {
        $this->where[$key] = [];
        $length = count($queries);
        for ($i = 0; $i < $length; $i++) {
            $this->where[$key][] = $queries[$i]->where;
        }

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * contain each one of the provided list of values.
     *
     * @param string $key    The key to check. This key's value must be an array.
     * @param array  $values The values that will match.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function containsAll($key, $values)
    {
        $this->addCondition($key, '$all', $values);

        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * contain each one of the provided list of values starting with the given string.
     *
     * @param string $key    The key to check. This key's value must be an array.
     * @param array  $values The values that will match as starting string.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function containsAllStartingWith($key, $values)
    {
        $opts = [];
        for ($i = 0; $i < count($values); $i += 1) {
            $opts[] = ['$regex' => $this->regexStartWith($values[$i])];
        }

        return $this->containsAll($key, $opts);
    }

    /**
     * Add a constraint for finding objects that contain the given key.
     *
     * @param string $key The key that should exist.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function exists($key)
    {
        $this->addCondition($key, '$exists', true);

        return $this;
    }

    /**
     * Add a constraint for finding objects that not contain the given key.
     *
     * @param string $key The key that should not exist.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function doesNotExist($key)
    {
        $this->addCondition($key, '$exists', false);

        return $this;
    }

    /**
     * Restrict the fields of the returned Parse Objects to include only the
     * provided keys. If this is called multiple times, then all of the keys
     * specified in each of the calls will be included.
     *
     * @param mixed $key The name(s) of the key(s) to include. It could be
     *                   string, or an Array of string.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function select($key)
    {
        if (is_array($key)) {
            $this->selectedKeys = array_merge($this->selectedKeys, $key);
        } else {
            $this->selectedKeys[] = $key;
        }

        return $this;
    }

     /**
     * Restricts the fields of the returned Parse.Objects to all keys except the
     * provided keys. Exclude takes precedence over select and include.
     *
     * (Requires Parse Server 3.6.0+)
     *
     * @param mixed $key The name(s) of the key(s) to exclude. It could be
     *                   string, or an Array of string.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function excludeKey($key)
    {
        if (is_array($key)) {
            $this->excludes = array_merge($this->excludes, $key);
        } else {
            $this->excludes[] = $key;
        }

        return $this;
    }

    /**
     * Include nested Parse Objects for the provided key.    You can use dot
     * notation to specify which fields in the included object are also fetch.
     *
     * You can include all nested Parse Objects by passing in '*'. (Requires Parse Server 3.0.0+)
     *
     * @param mixed $key The name(s) of the key(s) to include. It could be
     *                   string, or an Array of string.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function includeKey($key)
    {
        if (is_array($key)) {
            $this->includes = array_merge($this->includes, $key);
        } else {
            $this->includes[] = $key;
        }

        return $this;
    }

    /**
     * Includes all nested Parse.Objects.
     *
     * Requires Parse Server 3.0.0+
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function includeAllKeys()
    {
        return $this->includeKey('*');
    }

    /**
     * Add constraint for parse relation.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function relatedTo($key, $value)
    {
        $this->addCondition('$relatedTo', $key, $value);

        return $this;
    }

    /**
     * Changes the read preference that the backend will use when performing the query to the database.
     *
     * @param string $readPreference The read preference for the main query.
     * @param string $includeReadPreference The read preference for the queries to include pointers.
     * @param string $subqueryReadPreference The read preference for the sub queries.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    public function readPreference($readPreference, $includeReadPreference = null, $subqueryReadPreference = null)
    {
        $this->readPreference = $readPreference;
        $this->includeReadPreference = $includeReadPreference;
        $this->subqueryReadPreference = $subqueryReadPreference;

        return $this;
    }
}
