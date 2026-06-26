/**
 * Standalone verification for equalTo fix
 * Tests that ParseObject values use direct pointer matching (not $eq)
 * and non-Object values still use $eq
 */

require_once __DIR__ . '/src/Parse/ParseClient.php';
require_once __DIR__ . '/src/Parse/ParseObject.php';
require_once __DIR__ . '/src/Parse/ParseQuery.php';
require_once __DIR__ . '/src/Parse/ParseUser.php';
require_once __DIR__ . '/src/Parse/ParseRole.php';
require_once __DIR__ . '/src/Parse/Internal/Encodable.php';

use Parse\ParseClient;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;
use Parse\ParseRole;

// Initialize Parse client
ParseClient::initialize('appId', 'restKey', 'https://api.example.com');

$passed = 0;
$failed = 0;

function assert_test($name, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "  ✓ $name\n";
        $passed++;
    } else {
        echo "  ✗ FAIL: $name\n";
        $failed++;
    }
}

echo "=== equalTo Fix Verification ===\n\n";

// Test 1: ParseObject value should use direct pointer match (NOT $eq)
echo "Test 1: ParseObject value uses direct pointer match\n";
$user = ParseObject::create('_User', 'user123');
$query = ParseQuery::getQuery('ParseRole');
$query->equalTo('users', $user);
$where = $query->getWhere();

assert_test(
    "where['users'] should NOT have \$eq key",
    !isset($where['users']['$eq'])
);
assert_test(
    "where['users'] should have __type = Pointer",
    isset($where['users']['__type']) && $where['users']['__type'] === 'Pointer'
);
assert_test(
    "where['users'] should have className = _User",
    isset($where['users']['className']) && $where['users']['className'] === '_User'
);
assert_test(
    "where['users'] should have objectId = user123",
    isset($where['users']['objectId']) && $where['users']['objectId'] === 'user123'
);

// Test 2: String value should still use $eq
echo "\nTest 2: String value uses \$eq\n";
$query2 = ParseQuery::getQuery('TestClass');
$query2->equalTo('foo', 'bar');
$where2 = $query2->getWhere();

assert_test(
    "where['foo'] should have \$eq key",
    isset($where2['foo']['$eq'])
);
assert_test(
    "where['foo']['\$eq'] should be 'bar'",
    isset($where2['foo']['$eq']) && $where2['foo']['$eq'] === 'bar'
);

// Test 3: Number value should still use $eq
echo "\nTest 3: Number value uses \$eq\n";
$query3 = ParseQuery::getQuery('TestClass');
$query3->equalTo('number', 17);
$where3 = $query3->getWhere();

assert_test(
    "where['number'] should have \$eq key",
    isset($where3['number']['$eq'])
);
assert_test(
    "where['number']['\$eq'] should be 17",
    isset($where3['number']['$eq']) && $where3['number']['$eq'] === 17
);

// Test 4: null value should still use $eq
echo "\nTest 4: null value uses \$eq\n";
$query4 = ParseQuery::getQuery('TestClass');
$query4->equalTo('num', null);
$where4 = $query4->getWhere();

assert_test(
    "where['num'] should have \$eq key",
    isset($where4['num']['$eq'])
);
assert_test(
    "where['num']['\$eq'] should be null",
    isset($where4['num']['$eq']) && $where4['num']['$eq'] === null
);

// Test 5: Array value should still use $eq
echo "\nTest 5: Array value uses \$eq\n";
$query5 = ParseQuery::getQuery('TestClass');
$query5->equalTo('tags', ['foo', 'bar']);
$where5 = $query5->getWhere();

assert_test(
    "where['tags'] should have \$eq key",
    isset($where5['tags']['$eq'])
);

echo "\n=== Results ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if ($failed > 0) {
    echo "\n❌ SOME TESTS FAILED\n";
    exit(1);
} else {
    echo "\n✅ ALL TESTS PASSED\n";
    exit(0);
}
