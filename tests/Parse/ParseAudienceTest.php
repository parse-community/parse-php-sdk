<?php
/**
 * Created by PhpStorm.
 * User: Bfriedman
 * Date: 11/7/17
 * Time: 23:56
 */

namespace Parse\Test;


use Parse\ParseClient;

class ParseAudienceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group audience-tests
     */
    public function testGetPushAudiences()
    {
        $response = ParseClient::_request(
            'GET',
            'push_audiences',
            null,
            null,
            true
        );

        echo json_encode($response, JSON_PRETTY_PRINT);
    }
}