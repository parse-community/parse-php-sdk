Parse PHP SDK
-------------

[![codecov](https://codecov.io/gh/parse-community/parse-php-sdk/branch/master/graph/badge.svg)](https://codecov.io/gh/parse-community/parse-php-sdk)
[![Build Status](https://travis-ci.org/parse-community/parse-php-sdk.svg?branch=master)](https://travis-ci.org/parse-community/parse-php-sdk)

The Parse PHP SDK gives you access to the powerful Parse cloud platform
from your PHP app or script.  Updated to work with the self-hosted Parse Server: https://github.com/parse-community/parse-server

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

After including the required files from the SDK, you need to initialize the ParseClient using your Parse API keys:

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

Getting Started
---------------

We highly recommend you read through the [guide](http://docs.parseplatform.org/php/guide/) first. This will walk you through the basics of working with this sdk, as well as provide insight into how to best develop your project.

If want to know more about what makes the php sdk tick you can read our [API Reference](http://parseplatform.org/parse-php-sdk/namespaces/Parse.html) and flip through the code on [github](https://github.com/parse-community/parse-php-sdk/).

Http Clients
------------

This SDK has the ability to change the underlying http client at your convenience.
The default is to use the curl http client if none is set, there is also a stream http client that can be used as well.

Setting the http client can be done as follows:
```php
// set curl http client (default if none set)
ParseClient::setHttpClient(new ParseCurlHttpClient());

// set stream http client
// ** requires 'allow_url_fopen' to be enabled in php.ini **
ParseClient::setHttpClient(new ParseStreamHttpClient());
```

If you have a need for an additional http client you can request one by opening an issue or by submitting a PR.

If you wish to build one yourself make sure your http client implements ```ParseHttpable``` for it be compatible with the SDK. Once you have a working http client that enhances the SDK feel free to submit it in a PR so we can look into adding it in.


Alternate Certificate Authority File
------------------------------------

It is possible that your local setup may not be able to verify with peers over SSL/TLS. This may especially be the case if you do not have control over your local installation, such as for shared hosting.

If this is the case you may need to specify a Certificate Authority bundle. You can download such a bundle from <a href="http://curl.haxx.se/ca/cacert.pem">http://curl.haxx.se/ca/cacert.pem</a> to use for this purpose. This one happens to be a Mozilla CA certificate store, you don't necessarily have to use this one but it's recommended.

Once you have your bundle you can set it as follows:
```php
// ** Use an Absolute path for your file! **
// holds one or more certificates to verify the peer with
ParseClient::setCAFile(__DIR__ . '/certs/cacert.pem');
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

In order to use Push you must first configure a [working push configuration](http://docs.parseplatform.org/parse-server/guide/#push-notifications) in your parse server instance.

```php
$data = array("alert" => "Hi!");

// Parse Server has a few requirements:
// - The master key is required for sending pushes, pass true as the second parameter
// - You must set your recipients by using 'channels' or 'where', but you must not pass both


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


// Get Push Status
$response = ParsePush::send(array(
    "channels" => ["StatusFans"],
    "data" => $data
), true);

if(ParsePush::hasStatus($response)) {

    // Retrieve PushStatus object
    $pushStatus = ParsePush::getStatus($response);

    // check push status
    if($pushStatus->isPending()) {
        // handle a pending push request

    } else if($pushStatus->isRunning()) {
        // handle a running push request

    } else if($pushStatus->hasSucceeded()) {
        // handle a successful push request

    } else if($pushStatus->hasFailed()) {
        // handle a failed request

    }

    // ...or get the push status string to check yourself
    $status = $pushStatus->getPushStatus();

    // get # pushes sent
    $sent = $pushStatus->getPushesSent();

    // get # pushes failed
    $failed = $pushStatus->getPushesFailed();

}
```

Contributing / Testing
----------------------

See the CONTRIBUTORS.md file for information on testing and contributing to
the Parse PHP SDK. We welcome fixes and enhancements.

[Get Composer]: https://getcomposer.org/download/
[Parse PHP Guide]: http://docs.parseplatform.org/php/guide/

-----

As of April 5, 2017, Parse, LLC has transferred this code to the parse-community organization, and will no longer be contributing to or distributing this code.
