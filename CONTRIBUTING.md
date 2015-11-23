Contributing
------------

For us to accept contributions you will have to first have signed the
[Contributor License Agreement].

When committing, keep all lines to less than 80 characters, and try to
follow the existing style. Before creating a pull request, squash your commits
into a single commit. Please provide ample explanation in the commit message.

Installation
------------

Testing the Parse PHP SDK involves some set-up. You'll need to create a Parse
App just for testing, and deploy some cloud code to it.

* [Get Composer], the PHP package manager.
* Run "composer install" to download dependencies.
* Create a new app here: [Create Parse App]
* Use the Parse CLI to create a Cloud Code folder for the new app.
* Copy tests/cloudcode/cloud/main.js into the newly created cloud/ folder.
* Run "parse deploy" in your cloud folder.
* Paste your App ID, REST API Key, and Master Key in tests/Parse/Helper.php

You should now be able to execute, from project root folder:

    ./vendor/bin/phpunit --stderr .

At present the full suite of tests takes around 20 minutes.

[Get Composer]: https://getcomposer.org/download/
[Contributor License Agreement]: https://developers.facebook.com/opensource/cla
[Create Parse App]: https://parse.com/apps/new
