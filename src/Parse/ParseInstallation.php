<?php
/**
 * Class ParseHooks | Parse/ParseHooks.php
 */

namespace Parse;

/**
 * Class ParseInstallation - Representation of an Installation stored on Parse.
 *
 * @author Fosco Marotto <fjm@fb.com>
 * @package Parse
 */
class ParseInstallation extends ParseObject
{
    /**
     * Parse Class name
     *
     * @var string
     */
    public static $parseClassName = '_Installation';

    /**
     * Gets the installation id for this installation
     *
     * @return string
     */
    public function getInstallationId()
    {
        return $this->get('installationId');
    }

    /**
     * Gets the device token for this installation
     *
     * @return string
     */
    public function getDeviceToken()
    {
        return $this->get('deviceToken');
    }

    /**
     * Get channels for this installation
     *
     * @return array
     */
    public function getChannels()
    {
        return $this->get('channels');
    }

    /**
     * Gets the device type of this installation
     *
     * @return string
     */
    public function getDeviceType()
    {
        return $this->get('deviceType');
    }

    /**
     * Gets the push type of this installation
     *
     * @return string
     */
    public function getPushType()
    {
        return $this->get('pushType');
    }

    /**
     * Gets the GCM sender id of this installation
     *
     * @return string
     */
    public function getGCMSenderId()
    {
        return $this->get('GCMSenderId');
    }

    /**
     * Gets the time zone of this installation
     *
     * @return string
     */
    public function getTimeZone()
    {
        return $this->get('timeZone');
    }

    /**
     * Gets the locale id for this installation
     *
     * @return string
     */
    public function getLocaleIdentifier()
    {
        return $this->get('localeIdentifier');
    }

    /**
     * Gets the badge number of this installation
     *
     * @return int
     */
    public function getBadge()
    {
        return $this->get('badge');
    }

    /**
     * Gets the app version of this installation
     *
     * @return string
     */
    public function getAppVersion()
    {
        return $this->get('appVersion');
    }

    /**
     * Get the app name for this installation
     *
     * @return string
     */
    public function getAppName()
    {
        return $this->get('appName');
    }

    /**
     * Gets the app identifier for this installation
     *
     * @return string
     */
    public function getAppIdentifier()
    {
        return $this->get('appIdentifier');
    }

    /**
     * Gets the parse version for this installation
     *
     * @return string
     */
    public function getParseVersion()
    {
        return $this->get('parseVersion');
    }
}
