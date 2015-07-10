<?php

namespace Parse;

/**
 * ParseUser - Representation of a user object stored on Parse.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class ParseUser extends ParseObject
{
    public static $parseClassName = "_User";

    /**
     * The currently logged-in user.
     *
     * @var ParseUser
     */
    protected static $currentUser = null;

    /**
     * The sessionToken for an authenticated user.
     *
     * @var string
     */
    protected $_sessionToken = null;

    /**
     * Returns the username.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->get("username");
    }

    /**
     * Sets the username for the ParseUser.
     *
     * @param string $username The username
     *
     * @return null
     */
    public function setUsername($username)
    {
        return $this->set("username", $username);
    }

    /**
     * Sets the password for the ParseUser.
     *
     * @param string $password The password
     *
     * @return null
     */
    public function setPassword($password)
    {
        return $this->set("password", $password);
    }

    /**
     * Returns the email address, if set, for the ParseUser.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->get("email");
    }

    /**
     * Sets the email address for the ParseUser.
     *
     * @param string $email The email address
     *
     * @return null
     */
    public function setEmail($email)
    {
        return $this->set("email", $email);
    }

    /**
     * Checks whether this user has been authenticated.
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        return $this->_sessionToken !== null;
    }

    /**
     * Signs up the current user, or throw if invalid.
     * This will create a new ParseUser on the server, and also persist the
     * session so that you can access the user using ParseUser::getCurrentUser();.
     */
    public function signUp()
    {
        if (!$this->get('username')) {
            throw new ParseException("Cannot sign up user with an empty name");
        }
        if (!$this->get('password')) {
            throw new ParseException(
                "Cannot sign up user with an empty password."
            );
        }
        if ($this->getObjectId()) {
            throw new ParseException(
                "Cannot sign up an already existing user."
            );
        }
        parent::save();
        $this->handleSaveResult(true);
    }

    /**
     * Logs in and returns a valid ParseUser, or throws if invalid.
     *
     * @param string $username
     * @param string $password
     *
     * @throws ParseException
     *
     * @return ParseUser
     */
    public static function logIn($username, $password)
    {
        if (!$username) {
            throw new ParseException("Cannot log in user with an empty name");
        }
        if (!$password) {
            throw new ParseException(
                "Cannot log in user with an empty password."
            );
        }
        $data = ["username" => $username, "password" => $password];
        $result = ParseClient::_request("GET", "/1/login", "", $data);
        $user = new static();
        $user->_mergeAfterFetch($result);
        $user->handleSaveResult(true);
        ParseClient::getStorage()->set("user", $user);

        return $user;
    }

    /**
     * Logs in with Facebook details, or throws if invalid.
     *
     * @param string $id the Facebook user identifier
     * @param string $access_token the access token for this session
     * @param \DateTime $expiration_date defaults to 60 days
     *
     * @throws ParseException
     *
     * @return ParseUser
     */
    public static function logInWithFacebook($id, $access_token, $expiration_date = null)
    {
        if (!$id) {
            throw new ParseException("Cannot log in Facebook user without an id.");
        }
        if (!$access_token) {
            throw new ParseException(
                "Cannot log in Facebook user without an access token."
            );
        }
        if (!$expiration_date) {
            $expiration_date = new \DateTime();
            $expiration_date->setTimestamp(time() + 86400 * 60);
        }
        $data = ["authData" => [
            "facebook" => [
                "id" => $id, "access_token" => $access_token,
                "expiration_date" => ParseClient::getProperDateFormat($expiration_date)
            ]
        ]];
        $result = ParseClient::_request("POST", "/1/users", "", json_encode($data));
        $user = ParseObject::create('_User');
        $user->_mergeAfterFetch($result);
        $user->handleSaveResult(true);
        ParseClient::getStorage()->set("user", $user);
        return $user;
    }

    /**
     * Login as an anonymous User with REST API.
     * @link https://www.parse.com/docs/rest/guide#users-anonymous-user-code-authdata-code-
     * @docs https://www.parse.com/docs/php/guide#users
     * @return ParseUser
     * @throws ParseException
     */
    public static function loginWithAnonymous()
    {
        /**
         * We use UUID version 4 as the id value
         * @link https://en.wikipedia.org/wiki/Universally_unique_identifier
         */
        $uuid_parts = str_split(md5(mt_rand()), 4);
        $data = ['authData' => [
            "anonymous" => [
                "id" => "{$uuid_parts[0]}{$uuid_parts[1]}-{$uuid_parts[2]}-{$uuid_parts[3]}-{$uuid_parts[4]}-{$uuid_parts[5]}{$uuid_parts[6]}{$uuid_parts[7]}",
            ]
        ]];

        $result = ParseClient::_request("POST", "/1/users", "", json_encode($data));
        $user = new ParseUser();
        $user->_mergeAfterFetch($result);
        $user->handleSaveResult(true);
        ParseClient::getStorage()->set("user", $user);
        return $user;
    }
    /**
     * Link the user with Facebook details.
     *
     * @param string $id the Facebook user identifier
     * @param string $access_token the access token for this session
     * @param \DateTime $expiration_date defaults to 60 days
     * @param boolean $useMasterKey whether to override security
     *
     * @throws ParseException
     *
     * @return ParseUser
     */
    public function linkWithFacebook($id, $access_token, $expiration_date = null, $useMasterKey = false){
        if (!$this->getObjectId()) {
            throw new ParseException("Cannot link an unsaved user, use ParseUser::logInWithFacebook");
        }
        if (!$id) {
            throw new ParseException("Cannot link Facebook user without an id.");
        }
        if (!$access_token) {
            throw new ParseException(
                "Cannot link Facebook user without an access token."
            );
        }
        if (!$expiration_date) {
            $expiration_date = new \DateTime();
            $expiration_date->setTimestamp(time() + 86400 * 60);
        }
        $data = ["authData" => [
            "facebook" => [
                "id" => $id, "access_token" => $access_token,
                "expiration_date" => ParseClient::getProperDateFormat($expiration_date)
            ]
        ]];
        $result = ParseClient::_request(
            "PUT", "/1/users/" . $this->getObjectId(),
            $this->getSessionToken(), json_encode($data), $useMasterKey
        );
        $user = new ParseUser();
        $user->_mergeAfterFetch($result);
        $user->handleSaveResult(true);
        return $user;
    }

    /**
     * Logs in a user with a session token.    Calls the /users/me route and if
     *     valid, creates and returns the current user.
     *
     * @param string $sessionToken
     *
     * @return ParseUser
     */
    public static function become($sessionToken)
    {
        $result = ParseClient::_request('GET', '/1/users/me', $sessionToken);
        $user = new static();
        $user->_mergeAfterFetch($result);
        $user->handleSaveResult(true);
        ParseClient::getStorage()->set("user", $user);

        return $user;
    }

    /**
     * Log out the current user.    This will clear the storage and future calls
     *     to current will return null.
     * This will make a network request to /1/logout to invalidate the session.
     *
     * @return null
     */
    public static function logOut()
    {
        $user = static::getCurrentUser();
        if ($user) {
            try {
                ParseClient::_request('POST', '/1/logout', $user->getSessionToken());
            } catch (ParseException $ex) {
                // If this fails, we're going to ignore it.
            }
            static::$currentUser = null;
        }
        ParseClient::getStorage()->remove('user');
    }

    /**
     * After a save, perform User object specific logic.
     *
     * @param boolean $makeCurrent Whether to set the current user.
     *
     * @return null
     */
    protected function handleSaveResult($makeCurrent = false)
    {
        if (isset($this->serverData['password'])) {
            unset($this->serverData['password']);
        }
        if (isset($this->serverData['sessionToken'])) {
            $this->_sessionToken = $this->serverData['sessionToken'];
            unset($this->serverData['sessionToken']);
        }
        if ($makeCurrent) {
            static::$currentUser = $this;
            static::saveCurrentUser();
        }
        $this->rebuildEstimatedData();
    }

    /**
     * Retrieves the currently logged in ParseUser with a valid session,
     * either from memory or the storage provider, if necessary.
     *
     * @return ParseUser|null
     */
    public static function getCurrentUser()
    {
        if (static::$currentUser instanceof ParseUser) {
            return static::$currentUser;
        }
        $storage = ParseClient::getStorage();
        $userData = $storage->get("user");
        if ($userData instanceof ParseUser) {
            static::$currentUser = $userData;

            return $userData;
        }
        if (isset($userData["id"]) && isset($userData["_sessionToken"])) {
            $user = static::create("_User", $userData["id"]);
            unset($userData["id"]);
            $user->_sessionToken = $userData["_sessionToken"];
            unset($userData["_sessionToken"]);
            foreach ($userData as $key => $value) {
                $user->set($key, $value);
            }
            $user->_opSetQueue = [];
            static::$currentUser = $user;

            return $user;
        }

        return;
    }

    /**
     * Persists the current user to the storage provider.
     *
     * @return null
     */
    protected static function saveCurrentUser()
    {
        $storage = ParseClient::getStorage();
        $storage->set('user', static::getCurrentUser());
    }

    /**
     * Returns the session token, if available.
     *
     * @return string|null
     */
    public function getSessionToken()
    {
        return $this->_sessionToken;
    }

    /**
     * Returns true if this user is the current user.
     *
     * @return boolean
     */
    public function isCurrent()
    {
        if (static::getCurrentUser() && $this->getObjectId()) {
            if ($this->getObjectId() == static::getCurrentUser()->getObjectId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save the current user object, unless it is not signed up.
     *
     * @throws ParseException
     *
     * @return null
     */
    public function save($useMasterKey = false)
    {
        if ($this->getObjectId()) {
            parent::save($useMasterKey);
        } else {
            throw new ParseException(
                "You must call signUp to create a new User."
            );
        }
    }

    /**
     * Requests a password reset email to be sent to the specified email
     * address associated with the user account.    This email allows the user
     * to securely reset their password on the Parse site.
     *
     * @param string $email
     *
     * @return null
     */
    public static function requestPasswordReset($email)
    {
        $json = json_encode(['email' => $email]);
        ParseClient::_request('POST', '/1/requestPasswordReset', null, $json);
    }

    public static function _clearCurrentUserVariable()
    {
        static::$currentUser = null;
    }
}
