<?php

namespace Parse\Test;

use Parse\ParseClient;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseSchema;

class Helper
{
    public static function setUp()
    {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        date_default_timezone_set('UTC');

        ParseClient::initialize(
            '2i1dJ1YGS93dz6YV5IVLdk3KyweJklb3YZIpFxbP',
            'MGtWmpVjDsTkXvYfXTqeH1LIaSPhi1FK3jHy8h2Y',
            'B4j3dzMI0uuulwTwjFfTNxqf5n0FBkT8VjwKdx2T'
        );
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
