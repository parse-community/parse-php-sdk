<?php
/**
 * Created by PhpStorm.
 * User: Bfriedman
 * Date: 10/30/17
 * Time: 15:56
 */

namespace Parse;

/**
 * Class ParseFeatures - Representation of server-side features
 *
 * @author Ben Friedman <ben@axolsoft.com>
 * @package Parse
 */
class ParseServerInfo
{
    /**
     * Reported server features and configs
     *
     * @var array
     */
    private static $serverFeatures;

    /**
     * Reported server version
     *
     * @var string
     */
    private static $serverVersion;

    /**
     * Requests and sets server features and version
     *
     * @throws ParseException
     */
    private static function setServerInfo()
    {
        $info = ParseClient::_request(
            'GET',
            'serverInfo/',
            null,
            null,
            true
        );

        // validate we have features & version

        if(!isset($info['features'])) {
            throw new ParseException('Missing features in server info.');
        }

        if(!isset($info['parseServerVersion'])) {
            throw new ParseException('Missing version in server info');
        }

        self::$serverFeatures = $info['features'];
        self::$serverVersion  = $info['parseServerVersion'];
    }

    /**
     * Get a specific feature set from the server
     *
     * @param string $key   Feature set to get
     * @return mixed
     */
    public static function get($key)
    {
        if(!isset(self::$serverFeatures)) {
            self::setServerInfo();
        }
        return self::$serverFeatures[$key];
    }

    /**
     * Gets features for the current server
     *
     * @return array
     */
    public static function getFeatures()
    {
        if(!isset(self::$serverFeatures)) {
            self::setServerInfo();
        }
        return self::$serverFeatures;
    }

    /**
     * Gets the reported version of the current server
     *
     * @return string
     */
    public static function getVersion()
    {
        if(!isset(self::$serverVersion)) {
            self::setServerInfo();
        }
        return self::$serverVersion;
    }

    /**
     * Gets features available for globalConfig
     *
     * @return array
     */
    public static function getGlobalConfigFeatures()
    {
        return self::get('globalConfig');
    }

    /**
     * Gets features available for hooks
     *
     * @return array
     */
    public static function getHooksFeatures()
    {
        return self::get('hooks');
    }

    /**
     * Gets features available for cloudCode
     *
     * @return array
     */
    public static function getCloudCodeFeatures()
    {
        return self::get('cloudCode');
    }

    /**
     * Gets features available for logs
     *
     * @return array
     */
    public static function getLogsFeatures()
    {
        return self::get('logs');
    }

    /**
     * Gets features available for push
     *
     * @return array
     */
    public static function getPushFeatures()
    {
        return self::get('push');
    }

    /**
     * Gets features available for schemas
     *
     * @return array
     */
    public static function getSchemasFeatures()
    {
        return self::get('schemas');
    }
}