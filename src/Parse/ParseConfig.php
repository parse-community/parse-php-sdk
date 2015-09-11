<?php

namespace Parse;

/**
 * ParseConfig - For accessing Parse Config settings.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class ParseConfig
{
    private $currentConfig;

    /**
     * Creates.
     */
    public function __construct()
    {
        $result = ParseClient::_request('GET', 'config');
        $this->setConfig($result['params']);
    }

    public function get($key)
    {
        if (isset($this->currentConfig[$key])) {
            return $this->currentConfig[$key];
        }
    }

    public function escape($key)
    {
        if (isset($this->currentConfig[$key])) {
            return htmlentities($this->currentConfig[$key]);
        }
    }

    protected function setConfig($config)
    {
        $this->currentConfig = $config;
    }
}
