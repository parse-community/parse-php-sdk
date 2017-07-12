<?php
/**
 * Class ParsePush | Parse/ParsePush.php
 */

namespace Parse;

use Exception;

/**
 * Class ParsePush - Handles sending push notifications with Parse.
 *
 * @author Fosco Marotto <fjm@fb.com>
 * @package Parse
 */
class ParsePush
{
    /**
     * Sends a push notification.
     *
     * @param array $data         The data of the push notification.    Valid fields
     *                            are:
     *                            channels - An Array of channels to push to.
     *                            push_time - A Date object for when to send the push.
     *                            expiration_time -    A Date object for when to expire
     *                            the push.
     *                            expiration_interval - The seconds from now to expire the push.
     *                            where - A ParseQuery over ParseInstallation that is used to match
     *                            a set of installations to push to.
     *                            data - The data to send as part of the push
     * @param bool  $useMasterKey Whether to use the Master Key for the request
     *
     * @throws \Exception, ParseException
     *
     * @return mixed
     */
    public static function send($data, $useMasterKey = false)
    {
        if (isset($data['expiration_time'])
            && isset($data['expiration_interval'])
        ) {
            throw new Exception(
                'Both expiration_time and expiration_interval can\'t be set.',
                138
            );
        }
        if (isset($data['where'])) {
            if ($data['where'] instanceof ParseQuery) {
                $where_options = $data['where']->_getOptions();

                if (!isset($where_options['where'])) {
                    $data['where'] = '{}';
                } else {
                    $data['where'] = $data['where']->_getOptions()['where'];
                }
            } else {
                throw new Exception(
                    'Where parameter for Parse Push must be of type ParseQuery',
                    111
                );
            }
        }
        if (isset($data['push_time'])) {
            //Local push date format is different from iso format generally used in Parse
            //Schedule does not work if date format not correct
            $data['push_time'] = ParseClient::getPushDateFormat($data['push_time'], isset($data['local_time']));
        }
        if (isset($data['expiration_time'])) {
            $data['expiration_time'] = ParseClient::_encode(
                $data['expiration_time'],
                false
            )['iso'];
        }

        return ParseClient::_request(
            'POST',
            'push',
            null,
            json_encode(ParseClient::_encode($data, true)),
            $useMasterKey,
            false,
            'application/json',
            true
        );
    }

    /**
     * Returns whether or not the given response has a push status
     * Checks to see if X-Push-Status-Id is present in $response
     *
     * @param array $response    Response from ParsePush::send
     * @return bool
     */
    public static function hasStatus($response)
    {
        return(
            isset($response['_headers']) &&
            isset($response['_headers']['X-Parse-Push-Status-Id'])
        );
    }

    /**
     * Returns the PushStatus for a response from ParsePush::send
     *
     * @param array $response   Response from ParsePush::send
     * @return null|ParsePushStatus
     */
    public static function getStatus($response)
    {
        if (!isset($response['_headers'])) {
            // missing headers
            return null;
        }

        $headers = $response['_headers'];

        if (!isset($headers['X-Parse-Push-Status-Id'])) {
            // missing push status id
            return null;
        }

        // get our push status id
        $pushStatusId = $response['_headers']['X-Parse-Push-Status-Id'];

        // return our push status if it exists
        return ParsePushStatus::getFromId($pushStatusId);
    }
}
