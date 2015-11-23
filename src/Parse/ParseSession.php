<?php

namespace Parse;

/**
 * ParseSession - Representation of an expiring user session.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class ParseSession extends ParseObject
{
    public static $parseClassName = '_Session';

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
        if ($user) {
            return self::_isRevocable($user->getSessionToken());
        }
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
     * After a save, perform Session object specific logic.
     *
     * @return null
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
