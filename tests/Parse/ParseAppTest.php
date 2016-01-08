<?php

namespace Parse\Test;

use Parse\ParseApp;
use PHPUnit_Framework_TestCase;

class ParseAppTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function testFetchingApps()
    {
        self::_createApp(self::_getNewName());
        self::_createApp(self::_getNewName());

        $apps = ParseApp::fetchApps();
        $this->assertGreaterThanOrEqual(2, $apps);
    }

    public function testFetchSingleApp()
    {
        $app_created = self::_createApp(self::_getNewName());

        $app = ParseApp::fetchApp($app_created['applicationId']);

        $this->assertCount(13, $app);
    }

    public function testFetchNotFound()
    {
        $invalid_application_id = '1YkU7V110nEDUqU7ctCEbLr6xcgQgdEkePuBaw6P';

        $this->setExpectedException('Parse\ParseException', 'requested resource was not found');
        ParseApp::fetchApp($invalid_application_id);
    }

    public function testCreateApp()
    {
        $app_name = self::_getNewName();

        $app = ParseApp::createApp([
            'appName' => $app_name,
        ]);

        $this->assertEquals($app_name, $app['appName']);
        $this->assertEquals(true, $app['clientClassCreationEnabled']);
        $this->assertEquals(false, $app['clientPushEnabled']);
        $this->assertEquals(true, $app['requireRevocableSessions']);
        $this->assertEquals(true, $app['revokeSessionOnPasswordChange']);
    }

    public function testNameAlreadyInAccount()
    {
        $app_name = self::_getNewName();

        ParseApp::createApp([
            'appName' => $app_name,
        ]);

        $this->setExpectedException('Parse\ParseException', 'App name must not already be used in your account');
        ParseApp::createApp([
            'appName' => $app_name,
        ]);
    }

    public function testUpdateApp()
    {
        $app_name = self::_getNewName();
        $updated_name = self::_getNewName();
        $this->assertNotEquals($app_name, $updated_name);

        $app = ParseApp::createApp([
            'appName' => $app_name,
        ]);

        $updated_app = ParseApp::updateApp($app['applicationId'], [
            'appName'                       => $updated_name,
            'clientClassCreationEnabled'    => false,
            'clientPushEnabled'             => true,
            'requireRevocableSessions'      => false,
            'revokeSessionOnPasswordChange' => false,
        ]);

        $this->assertEquals($updated_name, $updated_app['appName']);
        $this->assertNotTrue($updated_name['clientClassCreationEnabled']);
        $this->assertNotFalse($updated_name['clientPushEnabled']);
        $this->assertNotTrue($updated_name['requireRevocableSessions']);
        $this->assertNotTrue($updated_name['revokeSessionOnPasswordChange']);
    }

    private static function _createApp($name)
    {
        return ParseApp::createApp([
            'appName' => $name,
        ]);
    }

    private static function _getNewName()
    {
        return md5(uniqid(rand(), true));
    }
}
