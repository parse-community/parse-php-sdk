<?php
/**
 * Class ParseServerInfoTest | Parse/Test/ParseServerInfoTest.php
 */

namespace Parse\Test;

use Parse\ParseClient;
use Parse\ParseServerInfo;

use PHPUnit\Framework\TestCase;

class ParseServerInfoTest extends TestCase
{
    public function setup() : void
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
        $this->expectException(
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
        $this->expectException(
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
        $this->assertTrue(isset($globalConfigFeatures['create']));
        $this->assertTrue(isset($globalConfigFeatures['read']));
        $this->assertTrue(isset($globalConfigFeatures['update']));
        $this->assertTrue(isset($globalConfigFeatures['delete']));
    }

    public function testHooksFeatures()
    {
        $hooksFeatures = ParseServerInfo::getHooksFeatures();
        $this->assertTrue(isset($hooksFeatures['create']));
        $this->assertTrue(isset($hooksFeatures['read']));
        $this->assertTrue(isset($hooksFeatures['update']));
        $this->assertTrue(isset($hooksFeatures['delete']));
    }

    public function testCloudCodeFeatures()
    {
        $cloudCodeFeatures = ParseServerInfo::getCloudCodeFeatures();
        $this->assertTrue($cloudCodeFeatures['jobs']);
    }

    public function testLogsFeatures()
    {
        $logsFeatures = ParseServerInfo::getLogsFeatures();
        $this->assertTrue(isset($logsFeatures['level']));
        $this->assertTrue(isset($logsFeatures['size']));
        $this->assertTrue(isset($logsFeatures['order']));
        $this->assertTrue(isset($logsFeatures['until']));
        $this->assertTrue(isset($logsFeatures['from']));
    }

    public function testPushFeatures()
    {
        $pushFeatures = ParseServerInfo::getPushFeatures();

        // these may change depending on the server being tested against
        $this->assertTrue(isset($pushFeatures['immediatePush']));
        $this->assertTrue(isset($pushFeatures['scheduledPush']));
        $this->assertTrue(isset($pushFeatures['storedPushData']));

        $this->assertTrue(isset($pushFeatures['pushAudiences']));
        $this->assertTrue(isset($pushFeatures['localization']));
    }

    public function testSchemasFeatures()
    {
        $schemasFeatures = ParseServerInfo::getSchemasFeatures();
        $this->assertTrue(isset($schemasFeatures['addField']));
        $this->assertTrue(isset($schemasFeatures['removeField']));
        $this->assertTrue(isset($schemasFeatures['addClass']));
        $this->assertTrue(isset($schemasFeatures['removeClass']));
        $this->assertTrue(isset($schemasFeatures['clearAllDataFromClass']));
        $this->assertTrue(isset($schemasFeatures['exportClass']));
        $this->assertTrue(isset($schemasFeatures['editClassLevelPermissions']));
        $this->assertTrue(isset($schemasFeatures['editPointerPermissions']));
    }
}
