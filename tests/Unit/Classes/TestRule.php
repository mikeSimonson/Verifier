<?php

namespace Arachne\Tests\Verifier\Unit\Classes;

use Arachne\Verifier\IRule;
use Nette\Object;

/**
 * @author Jáchym Toušek
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class TestRule extends Object implements IRule
{
}
