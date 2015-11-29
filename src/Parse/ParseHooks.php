<?php

namespace Parse;

/**
 * ParseHooks - Representation of a Parse Hooks object.
 *
 * @author Phelipe Alves <phelipealvessouza@gmail.com>
 */
class ParseHooks
{
    /**
     * Fetch the list of all cloud functions.
     *
     * @throws ParseException
     *
     * @return array
     */
    public function fetchFunctions()
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'GET',
            'hooks/functions',
            $sessionToken,
            null,
            true
        );

        if (!isset($result['results'])) {
            throw new ParseException('Hooks functions not found.', 101);
        }

        return $result['results'];
    }

    /**
     * Fetch a single cloud function with a given name.
     *
     * @param string $functionName
     *
     * @throws ParseException
     *
     * @return array
     */
    public function fetchFunction($functionName)
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'GET',
            'hooks/functions/'.$functionName,
            $sessionToken,
            null,
            true
        );

        if (!isset($result['results'])) {
            throw new ParseException('Hooks functions not found.', 101);
        }

        return $result['results'];
    }

    /**
     * Fetch the list of all cloud triggers.
     *
     * @throws ParseException
     *
     * @return array
     */
    public function fetchTriggers()
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'GET',
            'hooks/triggers',
            $sessionToken,
            null,
            true
        );

        if (!isset($result['results'])) {
            throw new ParseException('Hooks triggers not found.', 101);
        }

        return $result['results'];
    }

    /**
     * Fetch a single cloud trigger.
     *
     * @param $className
     * @param $triggerName
     *
     * @throws ParseException
     *
     * @return array
     */
    public function fetchTrigger($className, $triggerName)
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'GET',
            'hooks/triggers/'.$className.'/'.$triggerName,
            $sessionToken,
            null,
            true
        );

        if (!isset($result['results'])) {
            throw new ParseException('Hooks trigger not found.', 101);
        }

        return $result;
    }

    /**
     * Create a new function webhook.
     *
     * @param $functionName
     * @param $url
     *
     * @throws ParseException
     *
     * @return array
     */
    public function createFunction($functionName, $url)
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'POST',
            'hooks/functions',
            $sessionToken,
            json_encode([
                'functionName' => $functionName,
                'url'          => $url,
            ]),
            true
        );

        return $result;
    }

    /**
     * Create a new trigger webhook.
     *
     * @param $className
     * @param $triggerName
     * @param $url
     *
     * @return array
     */
    public function createTrigger($className, $triggerName, $url)
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'POST',
            'hooks/triggers',
            $sessionToken,
            json_encode([
                'className'   => $className,
                'triggerName' => $triggerName,
                'url'         => $url,
            ]),
            true
        );

        return $result;
    }

    /**
     * Edit the url of a function webhook that was already created.
     *
     * @param $functionName
     * @param $url
     *
     * @throws ParseException
     *
     * @return array
     */
    public function editFunction($functionName, $url)
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'PUT',
            'hooks/functions/'.$functionName,
            $sessionToken,
            json_encode([
                'url' => $url,
            ]),
            true
        );

        return $result;
    }

    /**
     * Edit the url of a trigger webhook that was already crated.
     *
     * @param $className
     * @param $triggerName
     * @param $url
     *
     * @return array
     */
    public function editTrigger($className, $triggerName, $url)
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'PUT',
            'hooks/triggers/'.$className.'/'.$triggerName,
            $sessionToken,
            json_encode([
                'url' => $url,
            ]),
            true
        );

        return $result;
    }

    /**
     * Delete a function webhook.
     *
     * @param $functionName
     *
     * @throws ParseException
     *
     * @return array
     */
    public function deleteFunction($functionName)
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'PUT',
            'hooks/functions/'.$functionName,
            $sessionToken,
            json_encode([
                '__op' => 'Delete',
            ]),
            true
        );

        return $result;
    }

    /**
     * Delete a trigger webhook.
     *
     * @param $className
     * @param $triggerName
     *
     * @return array
     */
    public function deleteTrigger($className, $triggerName)
    {
        $sessionToken = null;
        if (ParseUser::getCurrentUser()) {
            $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        }

        $result = ParseClient::_request(
            'PUT',
            'hooks/triggers/'.$className.'/'.$triggerName,
            $sessionToken,
            json_encode([
                '__op' => 'Delete',
            ]),
            true
        );

        return $result;
    }
}
