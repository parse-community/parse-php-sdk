<?php

namespace Parse;

use Exception;

/**
 * ParseSchema - Handles schemas data from Parse.
 * All the schemas methods needs use the master key of your application.
 *
 * @see https://parse.com/docs/rest/guide#schemas
 *
 * @author Júlio César Gonçalves de Oliveira <julio@pinguineras.com.br>
 */
Class ParseSchema {

	/**
	 * Class name for data stored on Parse.
	 *
	 * @var string
	 */
	private $className;


	/**
	 *
	 * Fields to create
	 *
	 * @var array
	 */
	private $fields = [];


	/**
	 * Create a Parse Schema
	 *
	 * @param mixed $className Class Name of data on Parse.
	 */
	public function __construct($className = NULL)
	{
		if($className)
			$this->className = $className;
	}


	/**
	 * Get all the Schema data on Parse
	 *
	 * @param bool $useMasterKey Need to be true to make schema requests
	 * 
	 * @return array
	 */
	public function all($useMasterKey = true)
	{
		$sessionToken = NULL;
		if (ParseUser::getCurrentUser()) {
			$sessionToken = ParseUser::getCurrentUser()->getSessionToken();
		}

		$result = ParseClient::_request(
			'GET',
			'schemas/',
			$sessionToken,
			NULL,
			$useMasterKey
		);

		return $result;

	}


	/**
	 * Get the
	 *
	 *
	 *
	 */
	public function get($useMasterKey = true)
	{
		self::assertClassName();

		$sessionToken = NULL;
		if (ParseUser::getCurrentUser()) {
			$sessionToken = ParseUser::getCurrentUser()->getSessionToken();
		}

		$result = ParseClient::_request(
			'GET',
			'schemas/' . $this->className,
			$sessionToken,
			NULL,
			true
		);

		if (empty($result)) {
			throw new ParseException('Object not found.', 101);
		}

		return $result;
	}


	/**
	 *
	 *
	 *
	 *
	 */
	public function update($useMasterKey = true){

		self::assertClassName();

		$sessionToken = NULL;
		if (ParseUser::getCurrentUser()) {
			$sessionToken = ParseUser::getCurrentUser()->getSessionToken();
		}
	}


	/**
	 *
	 *
	 *
	 *
	 */
	public function save($useMasterKey = true){

		self::assertClassName();

		if (ParseUser::getCurrentUser()) {
			$sessionToken = ParseUser::getCurrentUser()->getSessionToken();
		}
	}



		/**
	 *
	 *
	 *
	 *
	 */
	public function addField($fieldName = NULL, $fieldType = 'String')
	{

	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function removeField($fieldName = NULL)
	{
		
	}


	/**
	 *
	 *
	 *
	 *
	 */
	public function assertClassName()
	{

		if(self::$className === null)
		{
			throw new Exception("You must set a Class Name before make any request.");
			
		}
	}
}