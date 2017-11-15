<?php
/**
 * Class ParseAudience | Parse/ParseAudience.php
 */

namespace Parse;

/**
 * Class ParseAudience - Representation of Audience for tracking and sending push notifications
 *
 * @author Ben Friedman <friedman.benjamin@gmail.com>
 * @package Parse
 */
class ParseAudience extends ParseObject
{
    /**
     * Parse Class name
     *
     * @var string
     */
    public static $parseClassName = '_Audience';

    /**
     * Create a new audience with name & query
     *
     * @param string $name      Name of the audience to create
     * @param ParseQuery $query Query to create audience with
     * @return ParseAudience
     */
    public static function createAudience($name, $query)
    {
        $audience = new ParseAudience();
        $audience->setName($name);
        $audience->setQuery($query);
        return $audience;
    }

    /**
     * Sets the name of this audience
     *
     * @param string $name  Name to set
     */
    public function setName($name)
    {
        $this->set('name', $name);
    }

    /**
     * Gets the name for this audience
     *
     * @return string
     */
    public function getName()
    {
        return $this->get('name');
    }

    /**
     * Sets the query for this Audience
     *
     * @param ParseQuery $query Query for this Audience
     */
    public function setQuery($query)
    {
        $this->set('query', json_encode($query->_getOptions()));
    }

    /**
     * Gets the query for this Audience
     *
     * @return ParseQuery
     */
    public function getQuery()
    {
        $query = new ParseQuery('_Installation');
        $query->_setConditions(json_decode($this->get('query'), true));
        return $query;
    }

    /**
     * Gets when this Audience was last used
     *
     * @return \DateTime|null
     */
    public function getLastUsed()
    {
        return $this->get('lastUsed');
    }

    /**
     * Gets the times this Audience has been used
     *
     * @return int
     */
    public function getTimesUsed()
    {
        return $this->get('timesUsed');
    }
}
