<?php
/**
 * Created by PhpStorm.
 * User: Bfriedman
 * Date: 10/30/17
 * Time: 16:35
 */

namespace Parse\Test;

use Parse\ParseClient;
use Parse\ParseException;
use Parse\ParseServerInfo;

class ParseServerInfoTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Helper::setHttpClient();
    }

    public function testDirectGet()
    {
        $logs = ParseServerInfo::get('logs');
        $this->assertNotNull($logs);
    }

    public function testGetFeatures()
    {
        $features = ParseServerInfo::getFeatures();
        $this->assertNotEmpty($features);
    }

    /**
     * @group test-get-version
     */
    public function testGetVersion()
    {
        ParseServerInfo::_setServerVersion(null);
        $version = ParseServerInfo::getVersion();
        $this->assertNotNull($version);
    }

    public function testSetVersion()
    {
        /**
         * Tests setting the version.
         * /health may return the version in the future as well.
         * Rather than fetch that information again we can always have the option
         * to set it from wherever we happen to get it.
         */
        $version = '1.2.3';
        ParseServerInfo::_setServerVersion($version);
        $this->assertEquals($version, ParseServerInfo::getVersion());
    }

    /**
     * @group server-info-missing-features
     */
    public function testMissingFeatures()
    {
        $this->setExpectedException(
            'Parse\ParseException',
            'Missing features in server info.'
        );

        $httpClient = ParseClient::getHttpClient();

        // create a mock of the current http client
        $stubClient = $this->getMockBuilder(get_class($httpClient))
            ->getMock();

        // stub the response type to return
        // something we will try to work with
        $stubClient
            ->method('getResponseContentType')
            ->willReturn('application/octet-stream');

        $stubClient
            ->method('send')
            ->willReturn(json_encode([
                'empty' => true
            ]));

        // replace the client with our stub
        ParseClient::setHttpClient($stubClient);

        ParseServerInfo::_setServerVersion(null);
        ParseServerInfo::getFeatures();
    }

    /**
     * @group server-info-missing-version
     */
    public function testMissingVersion()
    {
        $this->setExpectedException(
            'Parse\ParseException',
            'Missing version in server info.'
        );

        $httpClient = ParseClient::getHttpClient();

        // create a mock of the current http client
        $stubClient = $this->getMockBuilder(get_class($httpClient))
            ->getMock();

        // stub the response type to return
        // something we will try to work with
        $stubClient
            ->method('getResponseContentType')
            ->willReturn('application/octet-stream');

        $stubClient
            ->method('send')
            ->willReturn(json_encode([
                'features' => []
            ]));

        // replace the client with our stub
        ParseClient::setHttpClient($stubClient);

        ParseServerInfo::_setServerVersion(null);
        ParseServerInfo::getFeatures();
    }

    public function testGlobalConfigFeatures()
    {
        $globalConfigFeatures = ParseServerInfo::getGlobalConfigFeatures();
        $this->assertTrue($globalConfigFeatures['create']);
        $this->assertTrue($globalConfigFeatures['read']);
        $this->assertTrue($globalConfigFeatures['update']);
        $this->assertTrue($globalConfigFeatures['delete']);
    }

    public function testHooksFeatures()
    {
        $hooksFeatures = ParseServerInfo::getHooksFeatures();
        $this->assertTrue($hooksFeatures['create']);
        $this->assertTrue($hooksFeatures['read']);
        $this->assertTrue($hooksFeatures['update']);
        $this->assertTrue($hooksFeatures['delete']);
    }

    public function testCloudCodeFeatures()
    {
        $cloudCodeFeatures = ParseServerInfo::getCloudCodeFeatures();
        $this->assertTrue($cloudCodeFeatures['jobs']);
    }

    public function testLogsFeatures()
    {
        $logsFeatures = ParseServerInfo::getLogsFeatures();
        $this->assertTrue($logsFeatures['level']);
        $this->assertTrue($logsFeatures['size']);
        $this->assertTrue($logsFeatures['order']);
        $this->assertTrue($logsFeatures['until']);
        $this->assertTrue($logsFeatures['from']);
    }

    public function testPushFeatures()
    {
        $pushFeatures = ParseServerInfo::getPushFeatures();

        // these may change depending on the server being tested against
        $this->assertTrue(isset($pushFeatures['immediatePush']));
        $this->assertTrue(isset($pushFeatures['scheduledPush']));
        $this->assertTrue(isset($pushFeatures['storedPushData']));

        $this->assertTrue($pushFeatures['pushAudiences']);
        $this->assertTrue($pushFeatures['localization']);
    }

    public function testSchemasFeatures()
    {
        $schemasFeatures = ParseServerInfo::getSchemasFeatures();
        $this->assertTrue($schemasFeatures['addField']);
        $this->assertTrue($schemasFeatures['removeField']);
        $this->assertTrue($schemasFeatures['addClass']);
        $this->assertTrue($schemasFeatures['removeClass']);
        $this->assertTrue($schemasFeatures['clearAllDataFromClass']);
        $this->assertFalse($schemasFeatures['exportClass']);
        $this->assertTrue($schemasFeatures['editClassLevelPermissions']);
        $this->assertTrue($schemasFeatures['editPointerPermissions']);
    }
}
