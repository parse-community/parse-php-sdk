<?php

namespace Parse;

use Exception;

/**
 * ParseAnalytics - Handles sending app-open and custom analytics events.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class ParseAnalytics
{
    /**
     * Tracks the occurrence of a custom event with additional dimensions.
     * Parse will store a data point at the time of invocation with the given
     * event name.
     *
     * Dimensions will allow segmentation of the occurrences of this custom
     * event. Keys and values should be strings, and will throw
     * otherwise.
     *
     * To track a user signup along with additional metadata, consider the
     * following:
     * <pre>
     * $dimensions = array(
     *    'gender' => 'm',
     *    'source' => 'web',
     *    'dayType' => 'weekend'
     * );
     * ParseAnalytics::track('signup', $dimensions);
     * </pre>
     *
     * There is a default limit of 4 dimensions per event tracked.
     *
     * @param string $name       The name of the custom event
     * @param array  $dimensions The dictionary of segment information
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public static function track($name, $dimensions = [])
    {
        $name = trim($name);

        if (strlen($name) === 0) {
            throw new Exception('A name for the custom event must be provided.');
        }

        foreach ($dimensions as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new Exception('Dimensions expected string keys and values.');
            }
        }

        return ParseClient::_request(
            'POST',
            '/1/events/'.$name,
            null,
            static::_toSaveJSON($dimensions)
        );
    }

    public static function _toSaveJSON($data)
    {
        return json_encode(
            [
                'dimensions' => $data,
            ],
            JSON_FORCE_OBJECT
        );
    }
}
