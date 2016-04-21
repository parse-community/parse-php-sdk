<?php

namespace Parse;

class ParseApp
{
    public static $APP_NAME = 'appName';
    public static $CLIENT_CLASS_CREATION_ENABLED = 'clientClassCreationEnabled';
    public static $CLIENT_PUSH_ENABLED = 'clientPushEnabled';
    public static $REQUIRE_REVOCABLE_SESSION = 'requireRevocableSessions';
    public static $REVOKE_SESSION_ON_PASSWORD_CHANGE = 'revokeSessionOnPasswordChange';

    /**
     * To fetch the keys and settings for all of the apps that you are a collaborator on.
     *
     * @throws ParseException
     *
     * @return array Containing the keys and settings for your apps.
     */
    public static function fetchApps()
    {
        $result = ParseClient::_request(
            'GET',
            'apps',
            null,
            null,
            false,
            true
        );

        return $result['results'];
    }

    /**
     * To fetch the keys and settings of a single app.
     *
     * @param string $application_id
     *
     * @throws ParseException
     *
     * @return array Containing the keys and settings for your app.
     */
    public static function fetchApp($application_id)
    {
        $result = ParseClient::_request(
            'GET',
            'apps/'.$application_id,
            null,
            null,
            false,
            true
        );

        return $result;
    }

    /**
     * Create a new app, that is owned by your account. The only required field for creating an app is the app name.
     *
     * @param array $data
     *
     * @throws ParseException
     *
     * @return array
     */
    public static function createApp(array $data)
    {
        $result = ParseClient::_request(
            'POST',
            'apps',
            null,
            json_encode($data),
            false,
            true
        );

        return $result;
    }

    /**
     * You can change your app's name, as well as change your app's settings.
     *
     * @param string $application_id
     * @param array  $data
     *
     * @throws ParseException
     *
     * @return array
     */
    public static function updateApp($application_id, array $data)
    {
        $result = ParseClient::_request(
            'PUT',
            'apps/'.$application_id,
            null,
            json_encode($data),
            false,
            true
        );

        return $result;
    }
}
