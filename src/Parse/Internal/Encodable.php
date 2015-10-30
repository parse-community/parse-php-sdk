<?php

namespace Parse\Internal;

/**
 * Class Encodable - Interface for Parse Classes which provide an encode
 * method.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
interface Encodable
{
    /**
     * Returns an associate array encoding of the implementing class.
     *
     * @return mixed
     */
    public function _encode();
}
