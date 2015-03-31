<?php

/*
 * You only need this file if you are not using composer.
 */

if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    throw new Exception('The Parse SDK requires PHP version 5.4 or higher.');
}

/*
 * Register the autoloader for the Parse SDK.
 *
 * Based off the official PSR-4 autoloader example found here:
 * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 */

spl_autoload_register(function ($class) {
    // Parse class prefix
    $prefix = 'Parse\\';

    // base directory for the namespace prefix
    $base_dir = defined('PARSE_SDK_DIR') ? PARSE_SDK_DIR : __DIR__.'/src/Parse/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir.str_replace('\\', '/', $relative_class).'.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
