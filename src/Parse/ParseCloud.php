<?php

namespace Parse;

/**
 * ParseCloud - Facilitates calling Parse Cloud functions.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class ParseCloud
{
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
        $response = ParseClient::_request(
            'POST',
            'functions/'.$name,
            $sessionToken,
            json_encode(ParseClient::_encode($data, false)),
            $useMasterKey
        );

        return ParseClient::_decode($response['result']);
    }
}
