<?php

namespace Parse;

/**
 * ParseBytes - Representation of a Byte array for storage on a Parse Object.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class ParseBytes implements Internal\Encodable
{
    /**
     * Byte array.
     *
     * @var array
     */
    private $byteArray;

    /**
     * Create a ParseBytes object with a given byte array.
     *
     * @param array $byteArray
     *
     * @return ParseBytes
     */
    public static function createFromByteArray(array $byteArray)
    {
        $bytes = new ParseBytes();
        $bytes->setByteArray($byteArray);

        return $bytes;
    }

    /**
     * Create a ParseBytes object with a given base 64 encoded data string.
     *
     * @param string $base64Data
     *
     * @return ParseBytes
     */
    public static function createFromBase64Data($base64Data)
    {
        $bytes = new ParseBytes();
        $bytes->setBase64Data($base64Data);

        return $bytes;
    }

    private function setBase64Data($base64Data)
    {
        $byteArray = unpack('C*', base64_decode($base64Data));
        $this->setByteArray($byteArray);
    }

    private function setByteArray(array $byteArray)
    {
        $this->byteArray = $byteArray;
    }

    /**
     * Encode to associative array representation.
     *
     * @return array
     */
    public function _encode()
    {
        $data = "";
        foreach ($this->byteArray as $byte) {
            $data .= chr($byte);
        }

        return [
            '__type' => 'Bytes',
            'base64' => base64_encode($data),
        ];
    }
}
