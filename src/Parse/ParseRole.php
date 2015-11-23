<?php

namespace Parse;

/**
 * ParseRole - Representation of an access Role.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class ParseRole extends ParseObject
{
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
            throw new ParseException(
                "A role's name can only be set before it has been saved."
            );
        }
        if (!is_string($name)) {
            throw new ParseException(
                "A role's name must be a string."
            );
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

    public function save($useMasterKey = false)
    {
        if (!$this->getACL()) {
            throw new ParseException(
                'Roles must have an ACL.'
            );
        }
        if (!$this->getName() || !is_string($this->getName())) {
            throw new ParseException(
                'Roles must have a name.'
            );
        }

        return parent::save($useMasterKey);
    }
}
