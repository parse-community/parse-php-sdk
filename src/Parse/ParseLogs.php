<?php
/**
 * Class ParseLogs | Parse/ParseLogs.php
 */

namespace Parse;

/**
 * Class ParseLogs - Allows access to server side parse script logs
 *
 * @author Ben Friedman <friedman.benjamin@gmail.com>
 * @package Parse
 */
class ParseLogs
{

    /**
     * Requests script logs from the server
     *
     * @param string $level Level of logs to return (info/error), default is info
     * @param int $size     Number of rows to return, default is 100
     * @param null $from    Earliest logs to return from, defaults to 1 week ago
     * @param null $until   Latest logs to return from, defaults to current time
     * @param null $order   Order to sort logs by (asc/desc), defaults to descending
     * @return array
     */
    public static function getScriptLogs(
        $level = 'info',
        $size = 100,
        $from = null,
        $until = null,
        $order = null
    ) {
        $data = [
            'level' => $level,
            'size'  => $size,
        ];

        if (isset($from) && $from instanceof \DateTime) {
            $data['from'] = ParseClient::getProperDateFormat($from);
        }

        if (isset($until) && $until instanceof \DateTime) {
            $data['until'] = ParseClient::getProperDateFormat($until);
        }

        if (isset($order)) {
            $data['order'] = $order;
        }

        $response = ParseClient::_request(
            'GET',
            'scriptlog',
            null,
            $data,
            true
        );

        return $response;
    }

    /**
     * Returns info logs
     *
     * @param int $size     Lines to return, 100 by default
     * @param null $from    Earliest logs to return from, default is 1 week ago
     * @param null $until   Latest logs to return from, defaults to current time
     * @param null $order   Order to sort logs by (asc/desc), defaults to descending
     * @return array
     */
    public static function getInfoLogs($size = 100, $from = null, $until = null, $order = null)
    {
        return self::getScriptLogs('info', $size, $from, $until, $order);
    }

    /**
     * Returns error logs
     *
     * @param int $size     Lines to return, 100 by default
     * @param null $from    Earliest logs to return from, default is 1 week ago
     * @param null $until   Latest logs to return from, defaults to current time
     * @param null $order   Order to sort logs by (asc/desc), defaults to descending
     * @return array
     */
    public static function getErrorLogs($size = 100, $from = null, $until = null, $order = null)
    {
        return self::getScriptLogs('error', $size, $from, $until, $order);
    }
}
