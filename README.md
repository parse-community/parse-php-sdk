<p align="center">
    <img alt="Parse Platform" src="Assets/logo large.png" width="200">
  </a>
</p>

<h2 align="center">Parse PHP SDK</h2>

<p align="center">
    The Parse PHP SDK gives you access to the powerful Parse Server backend from your PHP app or script.
</p>

<p align="center">
    <a href="https://twitter.com/intent/follow?screen_name=parseplatform"><img alt="Follow on Twitter" src="https://img.shields.io/twitter/follow/parseplatform?style=social&label=Follow"></a>
    <a href="https://community.parseplatform.org/"><img alt="Join the conversation" src="https://img.shields.io/discourse/https/community.parseplatform.org/topics.svg"></a>
    <a href="LICENSE"><img alt="License" src="https://img.shields.io/badge/license-BSD-lightgrey.svg"></a>
    <a href="https://travis-ci.org/parse-community/parse-php-sdk"><img alt="Build status" src="https://travis-ci.org/parse-community/parse-php-sdk.svg?branch=master"></a>
    <a href="https://packagist.org/packages/parse/php-sdk"><img alt="All time downloads" src="https://poser.pugx.org/parse/php-sdk/downloads" /></a>
</p>

<p align="center">
    <a href="https://codecov.io/gh/parse-community/parse-php-sdk"><img alt="Test coverage" src="https://codecov.io/gh/parse-community/parse-php-sdk/branch/master/graph/badge.svg"></a>
    <a href="#backers"><img alt="Backers on Open Collective" src="https://opencollective.com/parse-server/backers/badge.svg" /></a>
    <a href="#sponsors"><img alt="Sponsors on Open Collective" src="https://opencollective.com/parse-server/sponsors/badge.svg" /></a>
    <a href="https://packagist.org/packages/parse/php-sdk"><img alt="Latest stable version" src="https://poser.pugx.org/parse/php-sdk/v/stable" /></a>
    <a href="https://packagist.org/packages/parse/php-sdk"><img alt="Latest unstable version" src="https://poser.pugx.org/parse/php-sdk/v/unstable" /></a>
</p>
<br>

For more information on Parse and its features, see [the website](https://parseplatform.org), [the PHP guide](https://docs.parseplatform.org/php/guide/) or [API Reference](https://parseplatform.org/parse-php-sdk/namespaces/Parse.html).

Please note that this documentation contains the latest changes that may as of yet be unreleased. To see the README for your release please use the list below.
- [1.6.0 README](https://github.com/parse-community/parse-php-sdk/blob/1.6.0/README.md)
- [1.5.1 README](https://github.com/parse-community/parse-php-sdk/blob/1.5.1/README.md)
- [1.4.0 README](https://github.com/parse-community/parse-php-sdk/blob/1.4.0/README.md)
- [1.3.0 README](https://github.com/parse-community/parse-php-sdk/blob/1.3.0/README.md)

## Table of Contents
- [Installation](#installation)
    - [Install with Composer](#install-with-composer)
    - [Install with Git](#install-with-git)
    - [Install with another method](#install-with-another-method)
- [Setup](#setup)
    - [Initializing](#initializing)
    - [Server URL](#server-url)
    - [Server Health Check](#server-health-check)
    - [Http Clients](#http-clients)
    - [Alternate CA files](#alternate-ca-file)
- [Getting Started](#getting-started)
    - [Use Declarations](#use-declarations)
    - [Parse Objects](#parse-objects)
    - [Users](#users)
        - [Session Id and Session Fixation](#session-id-and-session-fixation)
        - [Verification Emails](#verification-emails)
    - [ACLs/Security](#acls)
    - [Queries](#queries)
        - [Aggregate](#aggregate)
        - [Distinct](#distinct)
        - [Relative Time](#relative-time)
    - [Cloud Functions](#cloud-functions)
    - [Cloud Jobs](#cloud-jobs)
    - [Config](#config)
    - [Analytics](#analytics)
    - [Files](#files)
    - [Push Notifications](#push)
        - [Push to Channels](#push-to-channels)
        - [Push with Query](#push-with-query)
        - [Push with Audience](#push-with-audience)
        - [Push Status](#push-status)
    - [Server Info](#server-info)
        - [Version](#version)
        - [Features](#features)
    - [Schema](#schema)
        - [Index](#index)
        - [Purge](#purge)
    - [Logs](#logs)
- [Contributing / Testing](#contributing--testing)

## Installation
There are various ways to install and use this sdk. We'll elaborate on a couple here.
Note that the Parse PHP SDK requires PHP 5.4 or newer. It can also run on HHVM (recommended 3.0 or newer).

### Install with Composer

[Get Composer], the PHP package manager. Then create a composer.json file in
 your projects root folder, containing:

```json
{
    "require": {
        "parse/php-sdk" : "1.5.*"
    }
}
```

Run "composer install" to download the SDK and set up the autoloader,
and then require it from your PHP script:

```php
require 'vendor/autoload.php';
```

### Install with Git

You can clone down this sdk using your favorite github client, or via the terminal.
```bash
git clone https://github.com/parse-community/parse-php-sdk.git
```

You can then include the ```autoload.php``` file in your code to automatically load the Parse SDK classes.

```php
require 'autoload.php';
```

### Install with another method

If you downloaded this sdk using any other means you can treat it like you used the git method above.
Once it's installed you need only require the `autoload.php` to have access to the sdk.

## Setup

Once you have access to the sdk you'll need to set it up in order to begin working with parse-server.

### Initializing

After including the required files from the SDK, you need to initialize the ParseClient using your Parse API keys:

```php
ParseClient::initialize( $app_id, $rest_key, $master_key );
```

If your server does not use or require a REST key you may initialize the ParseClient as follows, safely omitting the REST key:

```php
ParseClient::initialize( $app_id, null, $master_key );
```

### Server URL

Directly after initializing the sdk you should set the server url.

```php
// Users of Parse Server will need to point ParseClient at their remote URL and Mount Point:
ParseClient::setServerURL('https://my-parse-server.com:port','parse');
```

Notice Parse server's default port is `1337` and the second parameter `parse` is the route prefix of your parse server.

For example if your parse server's url is `http://example.com:1337/parse` then you can set the server url using the following snippet

```php
ParseClient::setServerURL('https://example.com:1337','parse');
```

### Server Health Check

To verify that the server url and mount path you've provided are correct you can run a health check on your server.
```php
$health = ParseClient::getServerHealth();
if($health['status'] === 200) {
    // everything looks good!
}
```
If you wanted to analyze it further the health response may look something like this.
```json
{
    "status"    : 200,
    "response"  : {
        "status" : "ok"
    }
}
```
The 'status' being the http response code, and the 'response' containing what the server replies with.
Any additional details in the reply can be found under 'response', and you can use them to check and determine the availability of parse-server before you make requests.

Note that it is _not_ guaranteed that 'response' will be a parsable json array. If the response cannot be decoded it will be returned as a string instead.

A couple examples of bad health responses could include an incorrect mount path, port or domain.
```json
// ParseClient::setServerURL('http://localhost:1337', 'not-good');
{
    "status": 404,
    "response": "<!DOCTYPE html>...Cannot GET \/not-good\/health..."
}

// ParseClient::setServerURL('http://__uh__oh__.com', 'parse');
{
    "status": 0,
    "error": 6,
    "error_message": "Couldn't resolve host '__uh__oh__.com'"
}
```
Keep in mind `error` & `error_message` may change depending on whether you are using the **curl** (may change across versions of curl) or **stream** client.

### Http Clients

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

### Alternate CA File

It is possible that your local setup may not be able to verify with peers over SSL/TLS. This may especially be the case if you do not have control over your local installation, such as for shared hosting.

If this is the case you may need to specify a Certificate Authority bundle. You can download such a bundle from <a href="http://curl.haxx.se/ca/cacert.pem">http://curl.haxx.se/ca/cacert.pem</a> to use for this purpose. This one happens to be a Mozilla CA certificate store, you don't necessarily have to use this one but it's recommended.

Once you have your bundle you can set it as follows:
```php
// ** Use an Absolute path for your file! **
// holds one or more certificates to verify the peer with
ParseClient::setCAFile(__DIR__ . '/certs/cacert.pem');
```

## Getting Started

We highly recommend you read through the [guide](http://docs.parseplatform.org/php/guide/) first. This will walk you through the basics of working with this sdk, as well as provide insight into how to best develop your project.

If want to know more about what makes the php sdk tick you can read our [API Reference](http://parseplatform.org/parse-php-sdk/namespaces/Parse.html) and flip through the code on [github](https://github.com/parse-community/parse-php-sdk/).

Check out the [Parse PHP Guide] for the full documentation.

### Use Declarations

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
use Parse\ParsePushStatus;
use Parse\ParseServerInfo;
use Parse\ParseLogs;
use Parse\ParseAudience;
```

### Parse Objects

Parse Objects hold your data, can be saved, queried for, serialized and more!
Objects are at the core of this sdk, they allow you to persist your data from php without having to worry about any databasing code.

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

// encode an object for later use
$encoded = $object->encode();

// decode an object
$decodedObject = ParseObject::decode($encoded);
```

### Users

Users are a special kind of object.
This class allows individuals to access your applications with their unique information and allows you to identify them distinctly.
Users may also be linked with 3rd party accounts such as facebook, twitter, etc.

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
#### Session Id and Session Fixation
In an attempt to avoid [session fixation exploits](https://www.owasp.org/index.php/Session_fixation), the PHP SDK will call [`session_regenerate_id()`](http://php.net/manual/en/function.session-regenerate-id.php) when a session's permissions are elevated (since 1.5.0).  In practice this means that `session_regenerate_id()` will be called when a session goes from no user to anonymous user or from no user / anonymous user to registered user.

Changing the PHP session id should have no impact on the contents of the session and state should be maintained for a user that was anonymous and becomes registered.

#### Verification Emails

If you are using email verification in your parse server setup you can request to send verification emails by hand.
```php
ParseUser::requestVerificationEmail('email@example.com');
```
Note that this will only send if the account for the email requested has not already been verified.

### ACLs

Access Control Lists (ACLs) allow you to granularly control access to individual Parse Objects.
ACLs allow you to configure access to the general public, roles, and individual users themselves.

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

### Queries

Queries allow you to recall objects that you've saved to parse-server.
Query methods and parameters allow allow a varying degree of querying for objects, from all objects of a class to objects created within a particular date range and more.

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
#### Aggregate

Queries can be made using aggregates, allowing you to retrieve objects over a set of input values.
Keep in mind that `_id` does not exist in parse-server. Please replace with `objectId`. MasterKey is Required

For a list of available operators please refer to Mongo Aggregate Documentation.

 <a href="https://docs.mongodb.com/v3.2/reference/operator/aggregation/">Mongo 3.2 Aggregate Operators</a>

```php
// group pipeline is similar to distinct, can apply $sum, $avg, $max, $min
// accumulate sum and store in total field
$pipeline = [
    'group' => [
        'objectId' => null,
        'total' => [ '$sum' => '$score']
    ]
];
$results = $query->aggregate($pipeline);

// project pipeline is similar to keys, add or remove existing fields
// includes name key
$pipeline = [
    'project' => [
        'name' => 1
    ]
];
$results = $query->aggregate($pipeline);

// match pipeline is similar to equalTo
// filter out objects with score greater than 15
 $pipeline = [
    'match' => [
        'score' => [ '$gt' => 15 ]
    ]
];
$results = $query->aggregate($pipeline);
```

#### Distinct

Queries can be made using distinct, allowing you find unique values for a specified field.
Keep in mind that MasterKey is required.
```php
// finds score that are unique
$results = $query->distinct('score');

// can be used with equalTo
$query = new ParseQuery('TestObject');
$query->equalTo('name', 'foo');
$results = $query->distinct('score');
```

#### Relative Time

Queries can be made using relative time, allowing you to retrieve objects over a varying ranges of relative dates.
Keep in mind that all relative queries are performed using the server's time and timezone.
```php
// greater than 2 weeks ago
$query->greaterThanRelativeTime('createdAt', '2 weeks ago');

// less than 1 day in the future
$query->lessThanRelativeTime('updatedAt', 'in 1 day');

// can make queries to very specific points in time
$query->greaterThanOrEqualToRelativeTime('createdAt', '1 year 2 weeks 30 days 2 hours 5 minutes 10 seconds ago');

// can make queries based on right now
// gets everything updated up to this point in time
$query->lessThanOrEqualToRelativeTime('updatedAt', 'now');

// shorthand keywords work as well
$query->greaterThanRelativeTime('date', '1 yr 2 wks 30 d 2 hrs 5 mins 10 secs ago');
```

### Cloud Functions

Directly call server-side cloud functions and get their results.

```php
$results = ParseCloud::run("aCloudFunction", array("from" => "php"));
```

### Cloud Jobs

Like cloud functions, cloud jobs allow you to run code server-side but in an asynchronous fashion.
Instead of waiting for execution to complete you are immediately returned an id for tracking the job's progress.
You can use this id to see the current information on a job and whether it has completed.
```php
// start job
$jobStatusId = ParseCloud::startJob('MyCloudJob', array("startedBy" => "me!"));

// get job status, a ParseObject!
$jobStatus = ParseCloud::getJobStatus($jobStatusId);
$status = $jobStatus->get('status'); // failed / succeeded when done

```

### Config

**ParseConfig** allows you to access the global **Config** object for your parse server setup.
You can get, set and update simple values much like you would on an instance of **ParseObject**. Through this all your SDKs and applications can have access to global settings, options, and more.
What you choose to put in your config is purely up to you however.
```php
$config = new ParseConfig();

// check a config value of yours
$allowed = $config->get('feature_allowed');

// add a simple config value
$config->set('feature_allowed', true);

// save this global config
$config->save();
```


### Analytics

A specialized Parse Object built purposely to make analytics easy.

```php
ParseAnalytics::track("logoReaction", array(
    "saw" => "elephant",
    "said" => "cute"
));
```

### Files

Persist files to parse-server and retrieve them at your convenience. Depending on how your server is setup there are a variety of storage options including mongodb, Amazon S3 and Google Cloud Storage. You can read more about that [here](https://github.com/parse-community/parse-server/#configuring-file-adapters).

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

### Push

Push notifications can be constructed and sent using this sdk. You can send pushes to predefined channels of devices, or send to a customized set of devices using the power of `ParseQuery`.

In order to use Push you must first configure a [working push configuration](http://docs.parseplatform.org/parse-server/guide/#push-notifications) in your parse server instance.

#### Push to Channels

You can send push notifications to any channels that you've created for your users.

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
```

#### Push with Query

You can also push to devices using queries targeting the `ParseInstallation` class.

```php
// Push to Query
$query = ParseInstallation::query();
$query->equalTo("design", "rad");

ParsePush::send(array(
    "where" => $query,
    "data" => $data
), true);
```

#### Push with Audience

If you want to keep track of your sends when using queries you can use the `ParseAudience` class.
You can create and configure your Audience objects with a name and query.
When you indicate it's being used in a push the `lastUsed` and `timesUsed` values are updated for you.
```php
$iosQuery = ParseInstallation::getQuery();
$iosQuery->equalTo("deviceType", "ios");

// create & save your audience
$audience = ParseAudience::createAudience(
    'MyiOSAudience',
    $iosQuery
);
$audience->save(true);

// send a push using the query in this audience and it's id
// The 'audience_id' is what allows parse to update 'lastUsed' and 'timesUsed'
// You could use any audience_id with any query and it will still update that audience
ParsePush::send([
    'data'          => [
        'alert' => 'hello ios users!'
    ],
    'where'         => $audience->getQuery(),
    'audience_id'   => $audience->getObjectId()
], true);

// fetch changes to this audience
$audience->fetch(true);

// get last & times used for tracking
$timesUsed = $audience->getTimesUsed();
$lastUsed = $audience->getLastUsed();
```
Audiences provide you with a convenient way to group your queries and keep track of how often and when you send to them.

#### Push Status

If your server supports it you can extract and check the current status of your pushes.
This allows you to monitor the success of your pushes in real time.

```php
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

### Server Info

Any server version **2.1.4** or later supports access to detailed information about itself and it's capabilities.
You can leverage `ParseServerInfo` to check on the features and version of your server.

#### Version
Get the current version of the server you are connected to.

```php
// get the current version of the server you are connected to (2.6.5, 2.5.4, etc.)
$version = ParseServerInfo::getVersion();
```

#### Features
Check which features your server has and how they are configured.

```php
// get the current version of the server you are connected to (2.6.5, 2.5.4, etc.)
$version = ParseServerInfo::getVersion();

// get various features
$globalConfigFeatures = ParseServerInfo::getGlobalConfigFeatures();
/**
 * Returns json of the related features
 * {
 *    "create" : true,
 *    "read"   : true,
 *    "update" : true,
 *    "delete" : true
 * }
 */

 // you can always get all feature data
 $data = ParseServerInfo::getFeatures();
```

 You can get details on the following features as well:

 ```php
 ParseServerInfo::getHooksFeatures();
 ParseServerInfo::getCloudCodeFeatures();
 ParseServerInfo::getLogsFeatures();
 ParseServerInfo::getPushFeatures();
 ParseServerInfo::getSchemasFeatures();

 // additional features can be obtained manually using 'get'
 $feature = ParseServerInfo::get('new-feature');

 ```

### Schema
Direct manipulation of the classes that are on your server is possible through `ParseSchema`.
Although fields and classes can be automatically generated (the latter assuming client class creation is enabled) `ParseSchema` gives you explicit control over these classes and their fields.
```php
// create an instance to manage your class
$mySchema = new ParseSchema("MyClass");

// gets the current schema data as an associative array, for inspection
$data = $mySchema->get();

// add any # of fields, without having to create any objects
$mySchema->addString('string_field');
$mySchema->addNumber('num_field');
$mySchema->addBoolean('bool_field');
$mySchema->addDate('date_field');
$mySchema->addFile('file_field');
$mySchema->addGeoPoint('geopoint_field');
$mySchema->addPolygon('polygon_field');
$mySchema->addArray('array_field');
$mySchema->addObject('obj_field');
$mySchema->addPointer('pointer_field');

// you can even setup pointer/relation fields this way
$mySchema->addPointer('pointer_field', 'TargetClass');
$mySchema->addRelation('relation_field', 'TargetClass');

// new types can be added as they are available
$mySchema->addField('new_field', 'ANewDataType');

// save/update this schema to persist your field changes
$mySchema->save();
// or
$mySchema->update();

```
Assuming you want to remove a field you can simply call `deleteField` and `save/update` to clear it out.
```php
$mySchema->deleteField('string_field');
$mySchema->save():
// or for an existing schema...
$mySchema->update():
```
A schema can be removed via `delete`, but it must be empty first.
```php
$mySchema->delete();
```
#### Index
Indexes support efficient execution of queries from the database. MasterKey is required.
```php
// To add an index, the field must exist before you create an index
$schema->addString('field');
$index = [ 'field' => 1 ];
$schema->addIndex('index_name', $index);
$schema->save();

// Delete an index
$schema->deleteIndex('index_name');
$schema->save();

// If indexes exist, you can retrieve them
$result = $schema->get();
$indexes = $result['indexes'];
 ```

#### Purge
All objects can be purged from a schema (class) via `purge`. But be careful! This can be considered an irreversible action.
Only do this if you _really_ need to delete all objects from a class, such as when you need to delete the class (as in the code example above).
```php
// delete all objects in the schema
$mySchema->purge();
```

### Logs
`ParseLogs` allows info and error logs to be retrieved from the server as JSON.
Using the same approach as that which is utilized in the [dashboard](https://github.com/parse-community/parse-dashboard) you can view your logs with specific ranges in time, type and order.
Note that this requires the correct masterKey to be set during your initialization for access.
```php
// get last 100 info logs, sorted in descending order
$logs = ParseLogs::getInfoLogs();

// get last 100 info logs, sorted in descending order
$logs = ParseLogs::getErrorLogs();

// logs can be retrieved with further specificity
// get 10 logs from a date up to a date in ascending order
$logs = ParseLogs::getInfoLogs(10, $fromDate, $untilDate, 'asc');

// above can be done for 'getErrorLogs' as well
```


## Contributing / Testing

See [CONTRIBUTING](CONTRIBUTING.md) for information on testing and contributing to
the Parse PHP SDK. We welcome fixes and enhancements.

-----

As of April 5, 2017, Parse, LLC has transferred this code to the parse-community organization, and will no longer be contributing to or distributing this code.

[Get Composer]: https://getcomposer.org/download/
[Parse PHP Guide]: https://docs.parseplatform.org/php/guide/
[open-collective-link]: https://opencollective.com/parse-server
