<?php

namespace Gedmo\Exception;

use Gedmo\Exception;

/**
 * Mapping exception thrown when the mapping configuration is invalid.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class InvalidMappingException extends InvalidArgumentException implements Exception
{
}
