<?php
/**
 * Class ParseException | Parse/ParseException.php
 */

namespace Parse;

use Exception;

/**
 * Class ParseException - Wrapper for \Exception class.
 *
 * @author Fosco Marotto <fjm@fb.com>
 * @package Parse
 */
class ParseException extends Exception
{
    /**
     * Constructs a Parse\Exception.
     *
     * @param string     $message  Message for the Exception.
     * @param int        $code     Error code.
     * @param \Exception $previous Previous Exception.
     */
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
