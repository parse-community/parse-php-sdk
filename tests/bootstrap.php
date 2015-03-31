<?php

/*
 * Used by the PHPUnit Test Suite to load dependencies and configure the main
 * application path.
 */

namespace Parse;

require_once __DIR__.'/../vendor/autoload.php';

$baseDir = str_replace('/tests', '', __DIR__);
define('APPLICATION_PATH', $baseDir);
