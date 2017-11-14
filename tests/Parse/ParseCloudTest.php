<?php

namespace Parse\Test;

use Parse\ParseCloud;
use Parse\ParseGeoPoint;
use Parse\ParseObject;
use Parse\ParseUser;

class ParseCloudTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function tearDown()
    {
        $user = ParseUser::getCurrentUser();
        if (isset($user)) {
            ParseUser::logOut();
            $user->destroy(true);
        }
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
        $this->setExpectedException(
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
    public function testFunctionsWithObjectParamsFails()
    {
        // login as user
        $obj = ParseObject::create('SomeClass');
        $obj->set('name', 'Zanzibar');
        $obj->save();
        $params = ['key1' => $obj];
        $this->setExpectedException('\Exception', 'ParseObjects not allowed');
        ParseCloud::run('foo', $params);
    }

    /**
     * @group cloud-code
     */
    public function testFunctionsWithGeoPointParamsDoNotThrow()
    {
        $params = ['key1' => new ParseGeoPoint(50, 50)];
        $this->setExpectedException(
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
        $this->setExpectedException(
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
        $this->setExpectedException('Parse\ParseException', 'Invalid job.');
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
        $this->setExpectedException('Parse\ParseException', 'Object not found.');
        $jobStatus = ParseCloud::getJobStatus('not-a-real-job-status');
        $this->assertNull($jobStatus);
    }
}
