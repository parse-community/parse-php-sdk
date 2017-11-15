<?php

/*
 * Used by the PHPUnit Test Suite to load dependencies and configure the main
 * application path.
 *
 * Additionally indicates that the stream client should be used when possible
 */

namespace Parse;

use Parse\Test\Helper;

require_once dirname(__DIR__).'/vendor/autoload.php';

define('APPLICATION_PATH', dirname(__DIR__));

// use the steam client
global $USE_CLIENT_STREAM;
$USE_CLIENT_STREAM = true;

// indicate which server version & client we're testing against
Helper::setUp();
$version = ParseServerInfo::getVersion();

echo "[ testing against {$version} with stream client ]\n";