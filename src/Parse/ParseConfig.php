<?php
/**
 * Class ParseConfig | Parse/ParseConfig.php
 */

namespace Parse;

/**
 * Class ParseConfig - For accessing Parse Config settings.
 *
 * @author Fosco Marotto <fjm@fb.com>
 * @package Parse
 */
class ParseConfig
{
    /**
     * Current configuration data
     *
     * @var array
     */
    private $currentConfig;

    /**
     * ParseConfig constructor.
     */
    public function __construct()
    {
        $result = ParseClient::_request('GET', 'config');
        $this->setConfig($result['params']);
    }

    /**
     * Gets a config value
     *
     * @param string $key   Key of value to get
     * @return mixed
     */
    public function get($key)
    {
        if (isset($this->currentConfig[$key])) {
            return $this->currentConfig[$key];
        }
    }

    /**
     * Gets a config value with html characters encoded
     *
     * @param string $key   Key of value to get
     * @return string
     */
    public function escape($key)
    {
        if (isset($this->currentConfig[$key])) {
            return htmlentities($this->currentConfig[$key]);
        }
    }

    /**
     * Sets the config
     *
     * @param array $config Config to set
     */
    protected function setConfig($config)
    {
        $this->currentConfig = $config;
    }

    /**
     * Gets the current config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->currentConfig;
    }
}
