<?php
spl_autoload_register(function ($class) {
    $prefix = 'Parse\\';
    $base = __DIR__ . '/src/Parse/';
    if (strpos($class, $prefix) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = $base . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require_once $file;
});

use Parse\ParseClient;
use Parse\ParseObject;
use Parse\ParseQuery;

ParseClient::initialize('appId', 'restKey', 'https://api.example.com');

$passed = 0;
$failed = 0;

function check($name, $condition) {
    global $passed, $failed;
    if ($condition) { echo "  PASS: $name\n"; $passed++; }
    else { echo "  FAIL: $name\n"; $failed++; }
}

echo "=== equalTo Fix Verification ===\n\n";

// Test 1: ParseObject value should use direct pointer match at query build time
echo "Test 1: ParseObject uses direct pointer match in built query\n";
$user = ParseObject::create('_User', 'user123');
$query = new ParseQuery('ParseRole');
$query->equalTo('users', $user);
$opts = $query->_getOptions();
$encoded = json_encode($opts['where'] ?? []);
echo "  Built query where = $encoded\n";
check('should NOT have $eq in output', strpos($encoded, '$eq') === false);
check('should NOT have $eq_pointer in output', strpos($encoded, '$eq_pointer') === false);
check('should have __type=Pointer in output', strpos($encoded, '"__type":"Pointer"') !== false);
check('should have className=_User', strpos($encoded, '"className":"_User"') !== false);
check('should have objectId=user123', strpos($encoded, '"objectId":"user123"') !== false);

// Test 2: String value still uses $eq
echo "\nTest 2: String uses \$eq\n";
$query2 = new ParseQuery('TestClass');
$query2->equalTo('foo', 'bar');
$opts2 = $query2->_getOptions();
$encoded2 = json_encode($opts2['where'] ?? []);
echo "  where = $encoded2\n";
check('foo should have $eq', strpos($encoded2, '$eq') !== false);
check('foo $eq should be bar', strpos($encoded2, '"bar"') !== false);

// Test 3: Number value still uses $eq
echo "\nTest 3: Number uses \$eq\n";
$query3 = new ParseQuery('TestClass');
$query3->equalTo('number', 17);
$opts3 = $query3->_getOptions();
$encoded3 = json_encode($opts3['where'] ?? []);
echo "  where = $encoded3\n";
check('number should have $eq', strpos($encoded3, '$eq') !== false);

// Test 4: Chained constraints on same key still work
echo "\nTest 4: Chained constraints on same key\n";
$query4 = new ParseQuery('TestClass');
$query4->equalTo('foo', 'bar');
$query4->greaterThan('foo', 10);
$opts4 = $query4->_getOptions();
$encoded4 = json_encode($opts4['where'] ?? []);
echo "  where = $encoded4\n";
check('foo should have both $eq and $gt', strpos($encoded4, '$eq') !== false && strpos($encoded4, '$gt') !== false);

// Test 5: ParseObject + another constraint on SAME key (mixed case)
echo "\nTest 5: ParseObject with other constraints on same key\n";
$query5 = new ParseQuery('TestClass');
$query5->equalTo('users', $user);
$query5->exists('users');
$opts5 = $query5->_getOptions();
$encoded5 = json_encode($opts5['where'] ?? []);
echo "  where = $encoded5\n";
check('should NOT have $eq_pointer in output', strpos($encoded5, '$eq_pointer') === false);
check('users should still contain the pointer payload', strpos($encoded5, '"__type":"Pointer"') !== false);
check('users should still contain the sibling operator', strpos($encoded5, '$exists') !== false);

echo "\n=== Results: $passed passed, $failed failed ===\n";
exit($failed > 0 ? 1 : 0);
