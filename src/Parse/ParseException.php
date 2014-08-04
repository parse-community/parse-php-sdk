<?php

namespace Parse;

/**
 * ParseException - Wrapper for \Exception class
 *
 * @package  Parse
 * @author   Fosco Marotto <fjm@fb.com>
 */
class ParseException extends \Exception
{

  /**
   * Constructs a Parse\Exception
   *
   * @param string     $message  Message for the Exception.
   * @param int        $code     Error code.
   * @param \Exception $previous Previous Exception.
   */
  public function __construct($message, $code = 0,
                              \Exception $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }

}