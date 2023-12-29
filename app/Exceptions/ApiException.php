<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{

	public function __construct($errorMsg)
	{
		if (is_array($errorMsg)){
			parent::__construct($errorMsg[1], $errorMsg[0]);
		}
	}
}
