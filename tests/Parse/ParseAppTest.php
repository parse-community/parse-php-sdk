<?php

namespace Parse\Test;

use Parse\ParseApp;
use PHPUnit\Framework\TestCase;

class ParseAppTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    public function testFetchingApps()
    {
        $this->expectException(
            'Parse\ParseException',
            'unauthorized'
        );

        self::_createApp(self::_getNewName());
        self::_createApp(self::_getNewName());

        $apps = ParseApp::fetchApps();
        $this->assertGreaterThanOrEqual(2, $apps);
    }

    public function testFetchSingleApp()
    {
        $this->expectException(
            'Parse\ParseException',
            'unauthorized'
        );

        $app_created = self::_createApp(self::_getNewName());

        $app = ParseApp::fetchApp($app_created['applicationId']);

        $this->assertCount(13, $app);
    }

    public function testFetchNotFound()
    {
        $invalid_application_id = '1YkU7V110nEDUqU7ctCEbLr6xcgQgdEkePuBaw6P';

        $this->expectException('Parse\ParseException', 'unauthorized');
        ParseApp::fetchApp($invalid_application_id);
    }

    public function testCreateApp()
    {
        $this->expectException(
            'Parse\ParseException',
            'unauthorized'
        );

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
        $this->expectException(
            'Parse\ParseException',
            'unauthorized'
        );

        $app_name = self::_getNewName();

        ParseApp::createApp([
            'appName' => $app_name,
        ]);

        $this->expectException('Parse\ParseException', 'App name must not already be used in your account');
        ParseApp::createApp([
            'appName' => $app_name,
        ]);
    }

    public function testUpdateApp()
    {
        $this->expectException(
            'Parse\ParseException',
            'unauthorized'
        );

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
        $this->assertNotTrue($updated_app['clientClassCreationEnabled']);
        $this->assertNotFalse($updated_app['clientPushEnabled']);
        $this->assertNotTrue($updated_app['requireRevocableSessions']);
        $this->assertNotTrue($updated_app['revokeSessionOnPasswordChange']);
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
