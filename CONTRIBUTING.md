Contributing
------------

For us to accept contributions you will have to first have signed the
[Contributor License Agreement].

When committing, keep all lines to less than 80 characters, and try to
follow the existing style. Before creating a pull request, squash your commits
into a single commit. Please provide ample explanation in the commit message.

Installation
------------

Testing the Parse PHP SDK requires having a working Parse Server instance to run against.
Additionally you'll need to add some cloud code to it.

To get started:

* [Get Composer], the PHP package manager.
* Run "composer install" to download dependencies.

From here you have to setup an instance of parse server.

To get started quickly you can clone and run this [parse server test] project.
It's setup with the appropriate configuration to run the php sdk test suite.
Additionally it handles setting up mongodb for the server.

Alternately you can configure a compatible test server as follows:

* [Setup a local Parse Server instance]
* Add main.js in tests/cloudcode/cloud/ to your Parse Server configuration as a cloud code file
* Ensure your App ID, REST API Key, and Master Key match those contained in tests/Parse/Helper.php
* Add a mock push configuration, for example:
```json
{
    "android":
    {
        "senderId": "blank-sender-id",
        "apiKey": "not-a-real-api-key"
    }
}
```
* Add a mock email adapter configuration, for example:
```json
{
    "module": "parse-server-simple-mailgun-adapter",
    "options": {
        "apiKey": "not-a-real-api-key",
        "domain": "example.com",
        "fromAddress": "example@example.com"
    }
}
```
* Ensure the public url is correct. For a locally hosted instance this is probably ```http://localhost:1337/parse```


You should now be able to execute the tests, from project root folder:

    ./vendor/bin/phpunit

The test suite is setup for code coverage if you have [XDebug] installed and setup.
Coverage is outputted as text and as html in the phpunit-test-results/ directory within the project root.

If you do not have XDebug tests will still run, just without coverage.

Please make sure that any new functionality (or issues) you are working on are covered by tests when possible.
If you have XDebug setup and can view code coverage please ensure that you do your best to completely cover any new code you are adding.

[Get Composer]: https://getcomposer.org/download/
[Contributor License Agreement]: https://developers.facebook.com/opensource/cla
[Create Parse App]: https://parse.com/apps/new
[XDebug]: https://xdebug.org/
[parse server test]: https://github.com/montymxb/parse-server-test
[Setup a local Parse Server instance]: https://github.com/parse-community/parse-server#user-content-locally
