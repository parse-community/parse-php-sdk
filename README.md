Parse PHP SDK
-------------

The Parse PHP SDK gives you access to the powerful Parse cloud platform
from your PHP app or script.

Installation
------------

[Get Composer], the PHP package manager.  Then create a composer.json file in
 your projects root folder, containing:

     {
       "require": {
         "parse/php-sdk" : "1.0.*"
       }
     }

Run "composer install" to download the SDK and set up the autoloader,
and then require it from your PHP script:

    require 'vendor/autoload.php';

Usage
-----

Check out the [Parse PHP Guide] for the full documentation.

Add the "use" declarations where you'll be using the classes.  For all of the
sample code in this file:

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


Objects:

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

Users:

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

Security:

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

Queries:

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

Cloud Functions:

    $results = ParseCloud::run("aCloudFunction", array("from" => "php"));

Analytics:

    PFAnalytics::trackEvent("logoReaction", array(
      "saw" => "elephant",
      "said" => "cute"
    ));

Files:

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

Push:

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

Contributing / Testing
----------------------

See the CONTRIBUTORS.md file for information on testing and contributing to
the Parse PHP SDK.  We welcome fixes and enhancements.

[Get Composer]: https://getcomposer.org/download/
[Parse PHP Guide]: https://www.parse.com/docs/php_guide