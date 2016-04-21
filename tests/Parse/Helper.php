<?php

namespace Parse\Test;

use Parse\ParseClient;
use Parse\ParseObject;
use Parse\ParseQuery;

class Helper
{
    public static function setUp()
    {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        date_default_timezone_set('UTC');

        ParseClient::initialize(
            'app-id-here',
            'rest-api-key-here',
            'master-key-here',
            true,
            'account-key-here'
        );
        ParseClient::setServerURL('http://localhost:1337/parse');
    }

    public static function tearDown()
    {
    }

    public static function clearClass($class)
    {
        $query = new ParseQuery($class);
        $query->each(
            function (ParseObject $obj) {
                $obj->destroy(true);
            },
            true
        );
    }
}
