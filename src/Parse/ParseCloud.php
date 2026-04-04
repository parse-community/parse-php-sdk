<?php
/**
 * Class ParseCloud | Parse/ParseCloud.php
 */

namespace Parse;

/**
 * Class ParseCloud - Facilitates calling Parse Cloud functions.
 *
 * @author Fosco Marotto <fjm@fb.com>
 * @package Parse
 */
class ParseCloud
{
    protected static $requestCallable;

    /**
     * Sets a callable to be used for making requests.
     *
     * This method allows injection of a mockable callable for testing purposes.
     *
     * @param callable $callable The callable to use for requests.
     */
    public static function setRequestCallable(callable $callable)
    {
        self::$requestCallable = $callable;
    }

    /**
     * Gets the callable used for making requests.
     *
     * If no callable has been set, it returns the default callable that calls ParseClient::_request.
     *
     * @return callable The callable used for requests.
     */
    protected static function getRequestCallable()
    {
        if (!self::$requestCallable) {
            self::$requestCallable = function($method, $path, $sessionToken = null, $data = null, $useMasterKey = false, $contentType = 'application/json', $returnHeaders = false) {
                return ParseClient::_request($method, $path, $sessionToken, $data, $useMasterKey, $contentType, $returnHeaders);
            };
        }
        return self::$requestCallable;
    }

    /**
     * Makes a call to a Cloud function.
     *
     * @param string $name         Cloud function name
     * @param array  $data         Parameters to pass
     * @param bool   $useMasterKey Whether to use the Master Key
     *
     * @return mixed
     */
    public static function run($name, $data = [], $useMasterKey = false)
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $response = call_user_func(
            self::getRequestCallable(),
            'POST',
            'functions/'.$name,
            $sessionToken,
            json_encode(ParseClient::_encode($data, false)),
            $useMasterKey
        );

        $returnVal = isset($response['result']) ? $response['result'] : [];
        return ParseClient::_decode($returnVal);
    }

    /**
     * Gets data for the current set of cloud jobs
     *
     * @return array
     */
    public static function getJobsData()
    {
        $response = ParseClient::_request(
            'GET',
            'cloud_code/jobs/data',
            null,
            null,
            true
        );

        return ParseClient::_decode($response);
    }

    /**
     * Starts a given cloud job, which will process asynchronously
     *
     * @param string $jobName   Name of job to run
     * @param array $data       Parameters to pass
     * @return string           Id for tracking job status
     */
    public static function startJob($jobName, $data = [])
    {
        $response = ParseClient::_request(
            'POST',
            'jobs/'.$jobName,
            null,
            json_encode(ParseClient::_encode($data, false)),
            true,
            'application/json',
            true
        );

        return ParseClient::_decode($response)['_headers']['X-Parse-Job-Status-Id'];
    }

    /**
     * Gets job status by id
     *
     * @param string $jobStatusId   Id of the job status to return
     * @return array|ParseObject
     */
    public static function getJobStatus($jobStatusId)
    {
        $query = new ParseQuery('_JobStatus');
        return $query->get($jobStatusId, true);
    }
}
