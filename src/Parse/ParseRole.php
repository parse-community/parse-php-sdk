<?php
/**
 * Class ParseRole | Parse/ParseRole.php
 */

namespace Parse;

/**
 * Class ParseRole - Representation of an access Role.
 *
 * @author Fosco Marotto <fjm@fb.com>
 * @package Parse
 */
class ParseRole extends ParseObject
{
    /**
     * Parse Class name
     *
     * @var string
     */
    public static $parseClassName = '_Role';

    /**
     * Create a ParseRole object with a given name and ACL.
     *
     * @param string   $name
     * @param ParseACL $acl
     *
     * @return ParseRole
     */
    public static function createRole($name, ParseACL $acl)
    {
        $role = ParseObject::create(static::$parseClassName);
        $role->setName($name);
        $role->setACL($acl);

        return $role;
    }

    /**
     * Returns the role name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->get('name');
    }

    /**
     * Sets the role name.
     *
     * @param string $name The role name
     *
     * @throws ParseException
     */
    public function setName($name)
    {
        if ($this->getObjectId()) {
            throw new ParseException("A role's name can only be set before it has been saved.");
        }
        if (!is_string($name)) {
            throw new ParseException("A role's name must be a string.", 139);
        }

        return $this->set('name', $name);
    }

    /**
     * Gets the ParseRelation for the ParseUsers which are direct children of
     *     this role.    These users are granted any privileges that this role
     *     has been granted.
     *
     * @return ParseRelation
     */
    public function getUsers()
    {
        return $this->getRelation('users');
    }

    /**
     * Gets the ParseRelation for the ParseRoles which are direct children of
     *     this role.    These roles' users are granted any privileges that this role
     *     has been granted.
     *
     * @return ParseRelation
     */
    public function getRoles()
    {
        return $this->getRelation('roles');
    }

    /**
     * Handles pre-saving of this role
     * Calls ParseObject::save to finish
     *
     * @param bool $useMasterKey
     * @throws ParseException
     */
    public function save($useMasterKey = false)
    {
        if (!$this->getACL()) {
            throw new ParseException('Roles must have an ACL.', 123);
        }
        if (!$this->getName() || !is_string($this->getName())) {
            throw new ParseException('Roles must have a name.', 139);
        }
        if ($this->getObjectId() === null) {
            // Not yet saved, verify this name is not taken
            // ParseServer does not validate duplicate role names as of parse-server v2.3.2
            $query = new ParseQuery('_Role');
            $query->equalTo('name', $this->getName());
            if ($query->count(true) > 0) {
                throw new ParseException("Cannot add duplicate role name of '{$this->getName()}'", 137);
            }
        }

        return parent::save($useMasterKey);
    }
}
