<?php

namespace Tests\Integration\Classes;

use Nette\Application\BadRequestException;

/**
 * @author Jáchym Toušek
 */
class DisabledException extends BadRequestException
{

	/** @var int */
	protected $defaultCode = 403;

}