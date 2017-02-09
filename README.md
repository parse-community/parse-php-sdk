Parse PHP SDK
-------------

The Parse PHP SDK gives you access to the powerful Parse cloud platform
from your PHP app or script.  Updated to work with the self-hosted Parse Server: https://github.com/parseplatform/parse-server

Installation
------------

[Get Composer], the PHP package manager. Then create a composer.json file in
 your projects root folder, containing:

```json
{
    "require": {
        "parse/php-sdk" : "1.2.*"
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
// Users of Parse Server will need to point ParseClient at their remote URL and Mount Point:
ParseClient::setServerURL('https://my-parse-server.com:port','parse');
```

If your server does not use or require a REST key you may initialize the ParseClient as follows, safely omitting the REST key:

```php
ParseClient::initialize( $app_id, null, $master_key );
// Users of Parse Server will need to point ParseClient at their remote URL and Mount Point:
ParseClient::setServerURL('https://my-parse-server.com:port','parse');
```

Notice
Parse server's default port is `1337` and the second parameter `parse` is the route prefix of your parse server.

For example if your parse server's url is `http://example.com:1337/parse` then you can set the server url using the following snippet

`ParseClient::setServerURL('https://example.com:1337','parse');`

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

// Save normally:
$object->save();

// Or pass true to use the master key to override ACLs when saving:
$object->save(true);
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

// All results, normally:
$results = $query->find();

// Or pass true to use the master key to override ACLs when querying:
$results = $query->find(true);

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

// Parse Server requires the master key for sending push.  Pass true as the second parameter.
ParsePush::send($data, true);

// Push to Channels
ParsePush::send(array(
    "channels" => ["PHPFans"],
    "data" => $data
), true);

// Push to Query
$query = ParseInstallation::query();
$query->equalTo("design", "rad");

ParsePush::send(array(
    "where" => $query,
    "data" => $data
), true);

// Get Push Status Id
$reponse = ParsePush::send($data, true);
if(isset($response['_headers']['X-Push-Status-Id'])) {
    // Retrieve info on _PushStatus using the id
}
```

Contributing / Testing
----------------------

See the CONTRIBUTORS.md file for information on testing and contributing to
the Parse PHP SDK. We welcome fixes and enhancements.

[Get Composer]: https://getcomposer.org/download/
[Parse PHP Guide]: https://www.parse.com/docs/php/guide
