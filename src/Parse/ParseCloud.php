<?php

namespace Parse;

/**
 * ParseCloud - Facilitates calling Parse Cloud functions
 *
 * @package  Parse
 * @author   Fosco Marotto <fjm@fb.com>
 */
class ParseCloud
{

  /**
   * Makes a call to a Cloud function
   *
   * @param string $name Cloud function name
   * @param array  $data Parameters to pass
   * @param boolean $useMasterKey Whether to use the Master Key
   *
   * @return mixed
   */
  public static function run($name, $data = array(), $useMasterKey = false)
  {
    $sessionToken = null;
    if (ParseUser::getCurrentUser()) {
      $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
    }
    $response = ParseClient::_request(
      'POST',
      '/1/functions/' . $name,
      $sessionToken,
      json_encode(ParseClient::_encode($data, null, false)),
      $useMasterKey
    );
    return ParseClient::_decode($response['result']);
  }

}