<?php
/**
 * Class ParseSession | Parse/ParseSession.php
 */

namespace Parse;

/**
 * Class ParseSession - Representation of an expiring user session.
 *
 * @author Fosco Marotto <fjm@fb.com>
 * @package Parse
 */
class ParseSession extends ParseObject
{
    /**
     * Parse Class name
     *
     * @var string
     */
    public static $parseClassName = '_Session';

    /**
     * Session token string
     *
     * @var null|string
     */
    private $_sessionToken = null;

    /**
     * Returns the session token string.
     *
     * @return string
     */
    public function getSessionToken()
    {
        return $this->_sessionToken;
    }

    /**
     * Retrieves the Session object for the currently logged in user.
     *
     * @param bool $useMasterKey If the Master Key should be used to override security.
     *
     * @return ParseSession
     */
    public static function getCurrentSession($useMasterKey = false)
    {
        $token = ParseUser::getCurrentUser()->getSessionToken();
        $response = ParseClient::_request(
            'GET',
            'sessions/me',
            $token,
            null,
            $useMasterKey
        );
        $session = new self();
        $session->_mergeAfterFetch($response);
        $session->handleSaveResult();

        return $session;
    }

    /**
     * Determines whether the current session token is revocable.
     * This method is useful for migrating an existing app to use
     * revocable sessions.
     *
     * @return bool
     */
    public static function isCurrentSessionRevocable()
    {
        $user = ParseUser::getCurrentUser();
        return $user ? self::_isRevocable($user->getSessionToken()) : false;
    }

    /**
     * Determines whether a session token is revocable.
     *
     * @param string $token The session token to check
     *
     * @return bool
     */
    public static function _isRevocable($token)
    {
        return strpos($token, 'r:') === 0;
    }

    /**
     * Upgrades the current session to a revocable one
     *
     * @throws ParseException
     */
    public static function upgradeToRevocableSession()
    {
        $user = ParseUser::getCurrentUser();
        if ($user) {
            $token = $user->getSessionToken();
            $response = ParseClient::_request(
                'POST',
                'upgradeToRevocableSession',
                $token,
                null,
                false
            );
            $session = new self();
            $session->_mergeAfterFetch($response);
            $session->handleSaveResult();
            ParseUser::become($session->getSessionToken());
        } else {
            throw new ParseException('No session to upgrade.');
        }
    }

    /**
     * After a save, perform Session object specific logic.
     */
    private function handleSaveResult()
    {
        if (isset($this->serverData['sessionToken'])) {
            $this->_sessionToken = $this->serverData['sessionToken'];
            unset($this->serverData['sessionToken']);
        }
        $this->rebuildEstimatedData();
    }
}
