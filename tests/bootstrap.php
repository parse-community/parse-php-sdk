<?php

/*
 * Used by the PHPUnit Test Suite to load dependencies and configure the main
 * application path.
 */

namespace Parse;

use Parse\Test\Helper;

require_once dirname(__DIR__).'/vendor/autoload.php';

define('APPLICATION_PATH', dirname(__DIR__));

// indicate which server version & client we're testing against
Helper::setUp();
$version = ParseServerInfo::getVersion();

fwrite(STDERR, "[ testing against {$version} with curl client ]\n");
