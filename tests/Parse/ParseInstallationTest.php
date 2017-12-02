<?php

namespace Parse\Test;

use Parse\ParseClient;
use Parse\ParseInstallation;

class ParseInstallationTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Helper::setUp();
    }

    public function tearDown()
    {
        Helper::clearClass(ParseInstallation::$parseClassName);
    }

    /**
     * @group installation-tests
     */
    public function testMissingIdentifyingField()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'at least one ID field (deviceToken, installationId) must be specified in this operation'
        );

        (new ParseInstallation())->save();
    }

    /**
     * @group installation-tests
     */
    public function testMissingDeviceType()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'deviceType must be specified in this operation'
        );

        $installation = new ParseInstallation();
        $installation->set('deviceToken', '12345');
        $installation->save();
    }

    /**
     * @group installation-tests
     */
    public function testClientsCannotFindWithoutMasterKey()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'Clients aren\'t allowed to perform the find operation on the installation collection.'
        );

        $query = ParseInstallation::query();
        $query->first();
    }

    /**
     * @group installation-tests
     */
    public function testClientsCannotDestroyWithoutMasterKey()
    {
        $installation = new ParseInstallation();
        $installation->set('deviceToken', '12345');
        $installation->set('deviceType', 'android');
        $installation->save();

        $this->setExpectedException(
            '\Parse\ParseException',
            "Clients aren't allowed to perform the delete operation on the installation collection."
        );

        // try destroying, without using the master key
        $installation->destroy();
    }

    /**
     * @group installation-tests
     */
    public function testInstallation()
    {
        $installationId = '12345';
        $deviceToken    = 'device-token';
        $deviceType     = 'android';
        $channels       = [
            'one',
            'zwei',
            'tres'
        ];
        $pushType       = 'a-push-type';
        $GCMSenderId    = 'gcm-sender-id';
        $timeZone       = 'Time/Zone';
        $localeIdentifier = 'locale';
        $badge          = 32;
        $appVersion     = '1.0.0';
        $appName        = 'Foo Bar App';
        $appIdentifier  = 'foo-bar-app-id';
        $parseVersion   = substr(ParseClient::VERSION_STRING, 3); // pull the version #

        $installation = new ParseInstallation();
        $installation->set('installationId', $installationId);
        $installation->set('deviceToken', $deviceToken);
        $installation->setArray('channels', $channels);
        $installation->set('deviceType', $deviceType);
        $installation->set('pushType', $pushType);
        $installation->set('GCMSenderId', $GCMSenderId);
        $installation->set('timeZone', $timeZone);
        $installation->set('localeIdentifier', $localeIdentifier);
        $installation->set('badge', $badge);
        $installation->set('appVersion', $appVersion);
        $installation->set('appName', $appName);
        $installation->set('appIdentifier', $appIdentifier);
        $installation->set('parseVersion', $parseVersion);

        $installation->save();

        // query for this installation now
        $query = ParseInstallation::query();
        $inst = $query->first(true);

        $this->assertNotNull($inst, 'Installation not found');

        $this->assertEquals($inst->getInstallationId(), $installationId);
        $this->assertEquals($inst->getDeviceToken(), $deviceToken);
        $this->assertEquals($inst->getChannels(), $channels);
        $this->assertEquals($inst->getDeviceType(), $deviceType);
        $this->assertEquals($inst->getPushType(), $pushType);
        $this->assertEquals($inst->getGCMSenderId(), $GCMSenderId);
        $this->assertEquals($inst->getTimeZone(), $timeZone);
        $this->assertEquals($inst->getLocaleIdentifier(), $localeIdentifier);
        $this->assertEquals($inst->getBadge(), $badge);
        $this->assertEquals($inst->getAppVersion(), $appVersion);
        $this->assertEquals($inst->getAppName(), $appName);
        $this->assertEquals($inst->getAppIdentifier(), $appIdentifier);
        $this->assertEquals($inst->getParseVersion(), $parseVersion);

        // cleanup
        $installation->destroy(true);
    }
}
