<?php

namespace Parse\Internal;

use Parse\ParseException;

/**
 * Class IncrementOperation - Operation to increment numeric object key.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class IncrementOperation implements FieldOperation
{
    /**
     * Amount to increment by.
     *
     * @var int
     */
    private $value;

    /**
     * Creates an IncrementOperation object.
     *
     * @param int $value Amount to increment by.
     */
    public function __construct($value = 1)
    {
        $this->value = $value;
    }

    /**
     * Get the value for this operation.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get an associative array encoding for this operation.
     *
     * @return array
     */
    public function _encode()
    {
        return ['__op' => 'Increment', 'amount' => $this->value];
    }

    /**
     * Apply the current operation and return the result.
     *
     * @param mixed  $oldValue Value prior to this operation.
     * @param mixed  $object   Value for this operation.
     * @param string $key      Key to set Value on.
     *
     * @throws ParseException
     *
     * @return int New value after application.
     */
    public function _apply($oldValue, $object, $key)
    {
        if ($oldValue && !is_numeric($oldValue)) {
            throw new ParseException('Cannot increment a non-number type.');
        }

        return $oldValue + $this->value;
    }

    /**
     * Merge this operation with a previous operation and return the
     * resulting operation.
     *
     * @param FieldOperation $previous Previous Operation.
     *
     * @throws ParseException
     *
     * @return FieldOperation
     */
    public function _mergeWithPrevious($previous)
    {
        if (!$previous) {
            return $this;
        }
        if ($previous instanceof DeleteOperation) {
            return new SetOperation($this->value);
        }
        if ($previous instanceof SetOperation) {
            return new SetOperation($previous->getValue() + $this->value);
        }
        if ($previous instanceof self) {
            return new self(
                $previous->getValue() + $this->value
            );
        }
        throw new ParseException(
            'Operation is invalid after previous operation.'
        );
    }
}
