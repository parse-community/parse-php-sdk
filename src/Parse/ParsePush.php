<?php

namespace Parse;

/**
 * ParsePush - Handles sending push notifications with Parse.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class ParsePush
{
    /**
     * Sends a push notification.
     *
     * @param array   $data         The data of the push notification.    Valid fields
     *                              are:
     *                              channels - An Array of channels to push to.
     *                              push_time - A Date object for when to send the push.
     *                              expiration_time -    A Date object for when to expire
     *                              the push.
     *                              expiration_interval - The seconds from now to expire the push.
     *                              where - A ParseQuery over ParseInstallation that is used to match
     *                              a set of installations to push to.
     *                              data - The data to send as part of the push
     * @param boolean $useMasterKey Whether to use the Master Key for the request
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
            throw new \Exception(
                'Both expiration_time and expiration_interval can\'t be set.'
            );
        }
        if (isset($data['where'])) {
            if ($data['where'] instanceof ParseQuery) {
                $data['where'] = $data['where']->_getOptions()['where'];
            } else {
                throw new \Exception(
                    'Where parameter for Parse Push must be of type ParseQuery'
                );
            }
        }
        if (isset($data['push_time'])) {
            //Local push date format is different from iso format generally used in Parse
            //Schedule does not work if date format not correct
            $data['push_time'] = ParseClient::getLocalPushDateFormat($data['push_time']);
        }
        if (isset($data['expiration_time'])) {
            $data['expiration_time'] = ParseClient::_encode(
                $data['expiration_time'], false
            )['iso'];
        }

        return ParseClient::_request(
            'POST',
            '/1/push',
            null,
            json_encode($data),
            $useMasterKey
        );
    }
}
