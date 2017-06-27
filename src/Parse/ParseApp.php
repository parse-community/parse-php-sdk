<?php
/**
 * Class ParseApp | Parse/ParseApp.php
 */

namespace Parse;

/**
 * Class ParseApp - Used to manage individual app instances on parse.com.
 * Note that with the open source parse-server this is not used as each parse-server is a singular app instance.
 *
 * @deprecated Not available on the open source parse-server.
 * @package Parse
 */
class ParseApp
{
    /**
     * App name key
     *
     * @var string
     */
    public static $APP_NAME = 'appName';

    /**
     * Class creation key
     *
     * @var string
     */
    public static $CLIENT_CLASS_CREATION_ENABLED = 'clientClassCreationEnabled';

    /**
     * Client push enabled key
     *
     * @var string
     */
    public static $CLIENT_PUSH_ENABLED = 'clientPushEnabled';

    /**
     * Require revocable session key
     *
     * @var string
     */
    public static $REQUIRE_REVOCABLE_SESSION = 'requireRevocableSessions';

    /**
     * Revoke session on password change key
     *
     * @var string
     */
    public static $REVOKE_SESSION_ON_PASSWORD_CHANGE = 'revokeSessionOnPasswordChange';

    /**
     * To fetch the keys and settings for all of the apps that you are a collaborator on.
     *
     * @throws ParseException
     * @deprecated Not available on the open source parse-server.
     * @return array Containing the keys and settings for your apps.
     */
    public static function fetchApps()
    {
        $result = ParseClient::_request(
            'GET',
            'apps',
            null,
            null,
            true,
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
     * @deprecated Not available on the open source parse-server.
     * @return array Containing the keys and settings for your app.
     */
    public static function fetchApp($application_id)
    {
        $result = ParseClient::_request(
            'GET',
            'apps/'.$application_id,
            null,
            null,
            true,
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
     * @deprecated Not available on the open source parse-server.
     * @return array
     */
    public static function createApp(array $data)
    {
        $result = ParseClient::_request(
            'POST',
            'apps',
            null,
            json_encode($data),
            true,
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
     * @deprecated Not available on the open source parse-server.
     * @return array
     */
    public static function updateApp($application_id, array $data)
    {
        $result = ParseClient::_request(
            'PUT',
            'apps/'.$application_id,
            null,
            json_encode($data),
            true,
            true
        );

        return $result;
    }
}
