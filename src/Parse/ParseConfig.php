<?php

namespace Parse;

/**
 * ParseConfig - For accessing Parse Config settings
 *
 * @package  Parse
 * @author   Fosco Marotto <fjm@fb.com>
 */
class ParseConfig {

  private $currentConfig;

  /**
   * Creates
   */
  public function __construct() {
    $result = ParseClient::_request("GET", "/1/config");
    $this->setConfig($result['params']);
  }

  public function get($key) {
    if (isset($this->currentConfig[$key])) {
      return $this->currentConfig[$key];
    }
    return null;
  }

  public function escape($key) {
    if (isset($this->currentConfig[$key])) {
      return htmlentities($this->currentConfig[$key]);
    }
    return null;
  }

  protected function setConfig($config) {
    $this->currentConfig = $config;
  }

}