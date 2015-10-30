<?php

namespace Parse;

/**
 * ParseAggregateException - Multiple error condition.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class ParseAggregateException extends ParseException
{
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
