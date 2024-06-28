<?php

namespace Parse\Test;

use Parse\ParseCloud;
use Parse\ParseGeoPoint;
use Parse\ParseObject;
use Parse\ParseUser;

use PHPUnit\Framework\TestCase;

class ParseCloudTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    public function tearDown() : void
    {
        $user = ParseUser::getCurrentUser();
        if (isset($user)) {
            ParseUser::logOut();
            $user->destroy(true);
        }

        // Reset the callable after each test
        ParseCloud::setRequestCallable(function($method, $path, $sessionToken = null, $data = null, $useMasterKey = false, $contentType = 'application/json', $returnHeaders = false) {
            return \Parse\ParseClient::_request($method, $path, $sessionToken, $data, $useMasterKey, $contentType, $returnHeaders);
        });

        parent::tearDown();
    }

    /**
     * @group cloud-code
     */
    public function testFunctionCall()
    {
        $response = ParseCloud::run('bar', [
            'key1'  => 'value2',
            'key2'  => 'value1'
        ]);

        $this->assertEquals('Foo', $response);
    }

    public function testFunctionCallWithUser()
    {
        $user = new ParseUser();
        $user->setUsername("someuser");
        $user->setPassword("somepassword");
        $user->signUp();

        $response = ParseCloud::run('bar', [
            'key1'  => 'value2',
            'key2'  => 'value1'
        ]);

        $this->assertEquals('Foo', $response);

        ParseUser::logOut();
        $user->destroy(true);
    }

    /**
     * @group cloud-code
     */
    public function testFunctionCallException()
    {
        $this->expectException(
            '\Parse\ParseException',
            'bad stuff happened'
        );

        ParseCloud::run('bar', [
            'key1'  => 'value1',
            'key2'  => 'value2'
        ]);
    }

    /**
     * @group cloud-code
     */
    public function testFunctionCallWithNullParams()
    {
        $this->expectException(
            'Parse\ParseException',
            'bad stuff happened'
        );
        $response = ParseCloud::run('bar', null);
    }

    /**
     * @group cloud-code
     */
    public function testFunctionCallWithEmptyParams() {
        $this->expectException(
            'Parse\ParseException',
            'bad stuff happened'
        );
        $response = ParseCloud::run('bar', []);
    }

    /**
     * @group cloud-code
     */
    public function testFunctionCallWithoutResultKey()
    {
        // Mock the _request method to return a response without the 'result' key
        $mockResponse = [];
        $mockCallable = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();
        $mockCallable->expects($this->once())
            ->method('__invoke')
            ->willReturn($mockResponse);

        // Set the mock callable in ParseCloud
        ParseCloud::setRequestCallable($mockCallable);

        $response = ParseCloud::run('bar', [
            'key1' => 'value2',
            'key2' => 'value1'
        ]);

        // Since 'result' key is missing, the default value is returned
        $this->assertEquals([], $response);
    }

    /**
     * @group cloud-code
     */
    public function testFunctionsWithObjectParamsFails()
    {
        // login as user
        $obj = ParseObject::create('SomeClass');
        $obj->set('name', 'Zanzibar');
        $obj->save();
        $params = ['key1' => $obj];
        $this->expectException('\Exception', 'ParseObjects not allowed');
        ParseCloud::run('foo', $params);
    }

    /**
     * @group cloud-code
     */
    public function testFunctionsWithGeoPointParamsDoNotThrow()
    {
        $params = ['key1' => new ParseGeoPoint(50, 50)];
        $this->expectException(
            'Parse\ParseException',
            'Invalid function: "unknown_function"'
        );
        ParseCloud::run('unknown_function', $params);
    }

    /**
     * @group cloud-code
     */
    public function testUnknownFunctionFailure()
    {
        $params = ['key1' => 'value1'];
        $this->expectException(
            'Parse\ParseException',
            'Invalid function: "unknown_function"'
        );
        ParseCloud::run('unknown_function', $params);
    }

    /**
     * @group cloud-code-jobs
     */
    public function testGetJobsData()
    {
        $jobsData = ParseCloud::getJobsData();
        $this->assertNotNull($jobsData['jobs']);
        $this->assertNotNull($jobsData['in_use']);
        $this->assertEquals(0, count($jobsData['in_use']));
        $this->assertEquals(3, count($jobsData['jobs']));
    }

    /**
     * @group cloud-code-jobs
     */
    public function testRunJob()
    {
        $jobStatusId = ParseCloud::startJob('CloudJob1', [
            'startedBy' => 'Monty Python'
        ]);
        $this->assertNotNull($jobStatusId);

        $jobStatus = ParseCloud::getJobStatus($jobStatusId);
        $this->assertNotNull($jobStatus);
        $this->assertEquals('succeeded', $jobStatus->get('status'));
        $this->assertEquals('Monty Python', $jobStatus->get('params')['startedBy']);
    }

    /**
     * @group cloud-code-jobs
     */
    public function testLongJob()
    {
        $jobStatusId = ParseCloud::startJob('CloudJob2');
        $jobStatus = ParseCloud::getJobStatus($jobStatusId);
        $this->assertNotNull($jobStatus);
        $this->assertEquals('running', $jobStatus->get('status'));
    }

    /**
     * @group cloud-code-jobs
     */
    public function testBadJob()
    {
        $this->expectException('Parse\ParseException', 'Invalid job.');
        ParseCloud::startJob('bad_job');
    }

    /**
     * @group cloud-code-jobs
     */
    public function testFailingJob()
    {
        $jobStatusId = ParseCloud::startJob('CloudJobFailing');
        $this->assertNotNull($jobStatusId);

        $jobStatus = ParseCloud::getJobStatus($jobStatusId);
        $this->assertNotNull($jobStatus);
        $this->assertEquals('failed', $jobStatus->get('status'));
        $this->assertEquals('cloud job failed', $jobStatus->get('message'));
    }

    /**
     * @group cloud-code-jobs
     */
    public function testGettingNotARealJobStatus()
    {
        $this->expectException('Parse\ParseException', 'Object not found.');
        $jobStatus = ParseCloud::getJobStatus('not-a-real-job-status');
        $this->assertNull($jobStatus);
    }
}
