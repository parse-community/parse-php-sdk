Parse PHP SDK
-------------

The Parse PHP SDK gives you access to the powerful Parse cloud platform
from your PHP app or script.

Installation
------------

[Get Composer], the PHP package manager. Then create a composer.json file in
 your projects root folder, containing:

```json
{
    "require": {
        "parse/php-sdk" : "1.1.*"
    }
}
```

Run "composer install" to download the SDK and set up the autoloader,
and then require it from your PHP script:

```php
require 'vendor/autoload.php';
```

Note: The Parse PHP SDK requires PHP 5.4 or newer.

Alternative Method
------------------

If you don't want to use Composer, you can include the ```autoload.php```
file in your code to automatically load the Parse SDK classes.

```php
require 'autoload.php';
```

Initialization
---------------

After including the required files from the SDK, you need to initalize the ParseClient using your Parse API keys:

```php
ParseClient::initialize( $app_id, $rest_key, $master_key );
```

Usage
-----

Check out the [Parse PHP Guide] for the full documentation.

Add the "use" declarations where you'll be using the classes. For all of the
sample code in this file:

```php
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseACL;
use Parse\ParsePush;
use Parse\ParseUser;
use Parse\ParseInstallation;
use Parse\ParseException;
use Parse\ParseAnalytics;
use Parse\ParseFile;
use Parse\ParseCloud;
use Parse\ParseClient;
```

Objects:

```php
$object = ParseObject::create("TestObject");
$objectId = $object->getObjectId();
$php = $object->get("elephant");

// Set values:
$object->set("elephant", "php");
$object->set("today", new DateTime());
$object->setArray("mylist", [1, 2, 3]);
$object->setAssociativeArray(
    "languageTypes", array("php" => "awesome", "ruby" => "wtf")
);

// Save:
$object->save();
```

Users:

```php
// Signup
$user = new ParseUser();
$user->setUsername("foo");
$user->setPassword("Q2w#4!o)df");
try {
    $user->signUp();
} catch (ParseException $ex) {
    // error in $ex->getMessage();
}

// Login
try {
    $user = ParseUser::logIn("foo", "Q2w#4!o)df");
} catch(ParseException $ex) {
    // error in $ex->getMessage();
}

// Current user
$user = ParseUser::getCurrentUser();
```

Security:

```php
// Access only by the ParseUser in $user
$userACL = ParseACL::createACLWithUser($user);

// Access only by master key
$restrictedACL = new ParseACL();

// Set individual access rights
$acl = new ParseACL();
$acl->setPublicReadAccess(true);
$acl->setPublicWriteAccess(false);
$acl->setUserWriteAccess($user, true);
$acl->setRoleWriteAccessWithName("PHPFans", true);
```

Queries:

```php
$query = new ParseQuery("TestObject");

// Get a specific object:
$object = $query->get("anObjectId");

$query->limit(10); // default 100, max 1000

// All results:
$results = $query->find();

// Just the first result:
$first = $query->first();

// Process ALL (without limit) results with "each".
// Will throw if sort, skip, or limit is used.
$query->each(function($obj) {
    echo $obj->getObjectId();
});
```

Cloud Functions:

```php
$results = ParseCloud::run("aCloudFunction", array("from" => "php"));
```

Analytics:

```php
ParseAnalytics::track("logoReaction", array(
    "saw" => "elephant",
    "said" => "cute"
));
```

Files:

```php
// Get from a Parse Object:
$file = $aParseObject->get("aFileColumn");
$name = $file->getName();
$url = $file->getURL();
// Download the contents:
$contents = $file->getData();

// Upload from a local file:
$file = ParseFile::createFromFile(
    "/tmp/foo.bar", "Parse.txt", "text/plain"
);

// Upload from variable contents (string, binary)
$file = ParseFile::createFromData($contents, "Parse.txt", "text/plain");
```

Push:

```php
$data = array("alert" => "Hi!");

// Push to Channels
ParsePush::send(array(
    "channels" => ["PHPFans"],
    "data" => $data
));

// Push to Query
$query = ParseInstallation::query();
$query->equalTo("design", "rad");
ParsePush::send(array(
    "where" => $query,
    "data" => $data
));
```

Contributing / Testing
----------------------

See the CONTRIBUTORS.md file for information on testing and contributing to
the Parse PHP SDK. We welcome fixes and enhancements.

[Get Composer]: https://getcomposer.org/download/
[Parse PHP Guide]: https://www.parse.com/docs/php_guide
