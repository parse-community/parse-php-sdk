<?php

namespace Parse;

use Parse\Internal\Encodable;

/**
 * ParseACL - is used to control which users can access or modify a particular
 * object. Each ParseObject can have its own ParseACL. You can grant read and
 * write permissions separately to specific users, to groups of users that
 * belong to roles, or you can grant permissions to "the public" so that, for
 * example, any user could read a particular object but only a particular set
 * of users could write to that object.
 *
 * @author Mohamed Madbouli <mohamedmadbouli@fb.com>
 */
class ParseACL implements Encodable
{
    const PUBLIC_KEY = '*';

    /**
     * @var array
     */
    private $permissionsById = [];

    /**
     * @var bool
     */
    private $shared = false;

    /**
     * @var ParseUser
     */
    private static $lastCurrentUser = null;

    /**
     * @var ParseACL
     */
    private static $defaultACLWithCurrentUser = null;

    /**
     * @var ParseACL
     */
    private static $defaultACL = null;

    /**
     * @var bool
     */
    private static $defaultACLUsesCurrentUser = false;

    /**
     * Create new ParseACL with read and write access for the given user.
     *
     * @param ParseUser $user
     *
     * @return ParseACL
     */
    public static function createACLWithUser($user)
    {
        $acl = new ParseACL();
        $acl->setUserReadAccess($user, true);
        $acl->setUserWriteAccess($user, true);

        return $acl;
    }

    /**
     * Create new ParseACL from existing permissions.
     *
     * @param array $data represents permissions.
     *
     * @throws \Exception
     *
     * @return ParseACL
     */
    public static function _createACLFromJSON($data)
    {
        $acl = new ParseACL();
        foreach ($data as $id => $permissions) {
            if (!is_string($id)) {
                throw new \Exception('Tried to create an ACL with an invalid userId.');
            }
            foreach ($permissions as $accessType => $value) {
                if ($accessType !== 'read' && $accessType !== 'write') {
                    throw new \Exception(
                        'Tried to create an ACL with an invalid permission type.'
                    );
                }
                if (!is_bool($value)) {
                    throw new \Exception(
                        'Tried to create an ACL with an invalid permission value.'
                    );
                }
                $acl->setAccess($accessType, $id, $value);
            }
        }

        return $acl;
    }

    /**
     * Return if ParseACL shared or not.
     *
     * @return bool
     */
    public function _isShared()
    {
        return $this->shared;
    }

    /**
     * Set shared for ParseACL.
     *
     * @param bool $shared
     */
    public function _setShared($shared)
    {
        $this->shared = $shared;
    }

    public function _encode()
    {
        if (empty($this->permissionsById)) {
            return new \stdClass();
        }

        return $this->permissionsById;
    }

    /**
     * Set access permission with access name, user id and if
     * the user has permission for accessing or not.
     *
     * @param string $accessType Access name.
     * @param string $userId     User id.
     * @param bool   $allowed    If user allowed to access or not.
     *
     * @throws ParseException
     */
    private function setAccess($accessType, $userId, $allowed)
    {
        if ($userId instanceof ParseUser) {
            $userId = $userId->getObjectId();
        }
        if ($userId instanceof ParseRole) {
            $userId = "role:".$userId->getName();
        }
        if (!is_string($userId)) {
            throw new ParseException(
                "Invalid target for access control."
            );
        }
        if (!isset($this->permissionsById[$userId])) {
            if (!$allowed) {
                return;
            }
            $this->permissionsById[$userId] = [];
        }
        if ($allowed) {
            $this->permissionsById[$userId][$accessType] = true;
        } else {
            unset($this->permissionsById[$userId][$accessType]);
            if (empty($this->permissionsById[$userId])) {
                unset($this->permissionsById[$userId]);
            }
        }
    }

    /**
     * Get if the given userId has a permission for the given access type or not.
     *
     * @param string $accessType Access name.
     * @param string $userId     User id.
     *
     * @return bool
     */
    private function getAccess($accessType, $userId)
    {
        if (!isset($this->permissionsById[$userId])) {
            return false;
        }
        if (!isset($this->permissionsById[$userId][$accessType])) {
            return false;
        }

        return $this->permissionsById[$userId][$accessType];
    }

    /**
     * Set whether the given user id is allowed to read this object.
     *
     * @param string $userId  User id.
     * @param bool   $allowed If user allowed to read or not.
     *
     * @throws \Exception
     */
    public function setReadAccess($userId, $allowed)
    {
        if (!$userId) {
            throw new \Exception("cannot setReadAccess for null userId");
        }
        $this->setAccess('read', $userId, $allowed);
    }

    /**
     * Get whether the given user id is *explicitly* allowed to read this
     * object. Even if this returns false, the user may still be able to
     * access it if getPublicReadAccess returns true or a role that the
     * user belongs to has read access.
     *
     * @param string $userId User id.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function getReadAccess($userId)
    {
        if (!$userId) {
            throw new \Exception("cannot getReadAccess for null userId");
        }

        return $this->getAccess('read', $userId);
    }

    /**
     * Set whether the given user id is allowed to write this object.
     *
     * @param string $userId  User id.
     * @param bool   $allowed If user allowed to write or not.
     *
     * @throws \Exception
     */
    public function setWriteAccess($userId, $allowed)
    {
        if (!$userId) {
            throw new \Exception("cannot setWriteAccess for null userId");
        }
        $this->setAccess('write', $userId, $allowed);
    }

    /**
     * Get whether the given user id is *explicitly* allowed to write this
     * object. Even if this returns false, the user may still be able to
     * access it if getPublicWriteAccess returns true or a role that the
     * user belongs to has write access.
     *
     * @param string $userId User id.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function getWriteAccess($userId)
    {
        if (!$userId) {
            throw new \Exception("cannot getWriteAccess for null userId");
        }

        return $this->getAccess('write', $userId);
    }

    /**
     * Set whether the public is allowed to read this object.
     *
     * @param bool $allowed
     */
    public function setPublicReadAccess($allowed)
    {
        $this->setReadAccess(self::PUBLIC_KEY, $allowed);
    }

    /**
     * Get whether the public is allowed to read this object.
     *
     * @return bool
     */
    public function getPublicReadAccess()
    {
        return $this->getReadAccess(self::PUBLIC_KEY);
    }

    /**
     * Set whether the public is allowed to write this object.
     *
     * @param bool $allowed
     */
    public function setPublicWriteAccess($allowed)
    {
        $this->setWriteAccess(self::PUBLIC_KEY, $allowed);
    }

    /**
     * Get whether the public is allowed to write this object.
     *
     * @return bool
     */
    public function getPublicWriteAccess()
    {
        return $this->getWriteAccess(self::PUBLIC_KEY);
    }

    /**
     * Set whether the given user is allowed to read this object.
     *
     * @param ParseUser $user
     * @param bool      $allowed
     *
     * @throws \Exception
     */
    public function setUserReadAccess($user, $allowed)
    {
        if (!$user->getObjectId()) {
            throw new \Exception("cannot setReadAccess for a user with null id");
        }
        $this->setReadAccess($user->getObjectId(), $allowed);
    }

    /**
     * Get whether the given user is *explicitly* allowed to read this object.
     * Even if this returns false, the user may still be able to access it if
     * getPublicReadAccess returns true or a role that the user belongs to has
     * read access.
     *
     * @param ParseUser $user
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function getUserReadAccess($user)
    {
        if (!$user->getObjectId()) {
            throw new \Exception("cannot getReadAccess for a user with null id");
        }

        return $this->getReadAccess($user->getObjectId());
    }

    /**
     * Set whether the given user is allowed to write this object.
     *
     * @param ParseUser $user
     * @param bool      $allowed
     *
     * @throws \Exception
     */
    public function setUserWriteAccess($user, $allowed)
    {
        if (!$user->getObjectId()) {
            throw new \Exception("cannot setWriteAccess for a user with null id");
        }
        $this->setWriteAccess($user->getObjectId(), $allowed);
    }

    /**
     * Get whether the given user is *explicitly* allowed to write this object.
     * Even if this returns false, the user may still be able to access it if
     * getPublicWriteAccess returns true or a role that the user belongs to has
     * write access.
     *
     * @param ParseUser $user
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function getUserWriteAccess($user)
    {
        if (!$user->getObjectId()) {
            throw new \Exception("cannot getWriteAccess for a user with null id");
        }

        return $this->getWriteAccess($user->getObjectId());
    }

    /**
     * Get whether users belonging to the role with the given roleName are
     * allowed to read this object. Even if this returns false, the role may
     * still be able to read it if a parent role has read access.
     *
     * @param string $roleName The name of the role.
     *
     * @return bool
     */
    public function getRoleReadAccessWithName($roleName)
    {
        return $this->getReadAccess('role:'.$roleName);
    }

    /**
     * Set whether users belonging to the role with the given roleName
     * are allowed to read this object.
     *
     * @param string $roleName The name of the role.
     * @param bool   $allowed  Whether the given role can read this object.
     */
    public function setRoleReadAccessWithName($roleName, $allowed)
    {
        $this->setReadAccess('role:'.$roleName, $allowed);
    }

    /**
     * Get whether users belonging to the role with the given roleName are
     * allowed to write this object. Even if this returns false, the role may
     * still be able to write it if a parent role has write access.
     *
     * @param string $roleName The name of the role.
     *
     * @return bool
     */
    public function getRoleWriteAccessWithName($roleName)
    {
        return $this->getWriteAccess('role:'.$roleName);
    }

    /**
     * Set whether users belonging to the role with the given roleName
     * are allowed to write this object.
     *
     * @param string $roleName The name of the role.
     * @param bool   $allowed  Whether the given role can write this object.
     */
    public function setRoleWriteAccessWithName($roleName, $allowed)
    {
        $this->setWriteAccess('role:'.$roleName, $allowed);
    }

    /**
     * Check whether the role is valid or not.
     *
     * @param ParseRole $role
     *
     * @throws \Exception
     */
    private static function validateRoleState($role)
    {
        if (!$role->getObjectId()) {
            throw new \Exception(
                "Roles must be saved to the server before they can be used in an ACL."
            );
        }
    }

    /**
     * Get whether users belonging to the given role are allowed to read this
     * object. Even if this returns false, the role may still be able to read
     * it if a parent role has read access. The role must already be saved on
     * the server and its data must have been fetched in order to use this method.
     *
     * @param ParseRole $role The role to check for access.
     *
     * @return bool
     */
    public function getRoleReadAccess($role)
    {
        $this->validateRoleState($role);

        return $this->getRoleReadAccessWithName($role->getName());
    }

    /**
     * Set whether users belonging to the given role are allowed to read this
     * object. The role must already be saved on the server and its data must
     * have been fetched in order to use this method.
     *
     * @param ParseRole $role    The role to assign access.
     * @param bool      $allowed Whether the given role can read this object.
     */
    public function setRoleReadAccess($role, $allowed)
    {
        $this->validateRoleState($role);
        $this->setRoleReadAccessWithName($role->getName(), $allowed);
    }

    /**
     * Get whether users belonging to the given role are allowed to write this
     * object. Even if this returns false, the role may still be able to write
     * it if a parent role has write access. The role must already be saved on
     * the server and its data must have been fetched in order to use this method.
     *
     * @param ParseRole $role The role to check for access.
     *
     * @return bool
     */
    public function getRoleWriteAccess($role)
    {
        $this->validateRoleState($role);

        return $this->getRoleWriteAccessWithName($role->getName());
    }

    /**
     * Set whether users belonging to the given role are allowed to write this
     * object. The role must already be saved on the server and its data must
     * have been fetched in order to use this method.
     *
     * @param ParseRole $role    The role to assign access.
     * @param bool      $allowed Whether the given role can read this object.
     */
    public function setRoleWriteAccess($role, $allowed)
    {
        $this->validateRoleState($role);
        $this->setRoleWriteAccessWithName($role->getName(), $allowed);
    }

    /**
     * Sets a default ACL that will be applied to all ParseObjects when they
     * are created.
     *
     * @param ParseACL $acl                      The ACL to use as a template for all ParseObjects
     *                                           created after setDefaultACL has been called. This
     *                                           value will be copied and used as a template for the
     *                                           creation of new ACLs, so changes to the instance
     *                                           after setDefaultACL() has been called will not be
     *                                           reflected in new ParseObjects.
     * @param bool     $withAccessForCurrentUser If true, the ParseACL that is applied to
     *                                           newly-created ParseObjects will provide read
     *                                           and write access to the ParseUser#getCurrentUser()
     *                                           at the time of creation. If false, the provided
     *                                           ACL will be used without modification. If acl is
     *                                           null, this value is ignored.
     */
    public static function setDefaultACL($acl, $withAccessForCurrentUser)
    {
        self::$defaultACLWithCurrentUser = null;
        self::$lastCurrentUser = null;
        if ($acl) {
            self::$defaultACL = clone $acl;
            self::$defaultACL->_setShared(true);
            self::$defaultACLUsesCurrentUser = $withAccessForCurrentUser;
        } else {
            self::$defaultACL = null;
        }
    }

    /**
     * Get the defaultACL.
     *
     * @return ParseACL
     */
    public static function _getDefaultACL()
    {
        if (self::$defaultACLUsesCurrentUser && self::$defaultACL) {
            $last = self::$lastCurrentUser ? clone self::$lastCurrentUser : null;
            if (!ParseUser::getCurrentUser()) {
                return self::$defaultACL;
            }
            if ($last != ParseUser::getCurrentUser()) {
                self::$defaultACLWithCurrentUser = clone self::$defaultAC;
                self::$defaultACLWithCurrentUser->_setShared(true);
                self::$defaultACLWithCurrentUser->setUserReadAccess(ParseUser::getCurrentUser(), true);
                self::$defaultACLWithCurrentUser->setUserWriteAccess(ParseUser::getCurrentUser(), true);
                self::$lastCurrentUser = clone ParseUser::getCurrentUser();
            }

            return self::$defaultACLWithCurrentUser;
        }

        return self::$defaultACL;
    }
}
