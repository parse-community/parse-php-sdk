<?php
/**
 * Class ParseAggregateException | Parse/ParseAggregateException.php
 */

namespace Parse;

/**
 * Class ParseAggregateException - Multiple error condition.
 *
 * @author Fosco Marotto <fjm@fb.com>
 * @package Parse
 */
class ParseAggregateException extends ParseException
{
    /**
     * Collection of error values
     *
     * @var array
     */
    private $errors;

    /**
     * Constructs a Parse\ParseAggregateException.
     *
     * @param string     $message  Message for the Exception.
     * @param array      $errors   Collection of error values.
     * @param \Exception $previous Previous exception.
     */
    public function __construct($message, $errors = [], $previous = null)
    {
        parent::__construct($message, 600, $previous);
        $this->errors = $errors;
    }

    /**
     * Return the aggregated errors that were thrown.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
