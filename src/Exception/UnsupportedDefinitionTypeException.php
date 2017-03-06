<?php

namespace Nayjest\DI\Exception;

use InvalidArgumentException;
use Nayjest\DI\Definition\DefinitionInterface;

class UnsupportedDefinitionTypeException extends InvalidArgumentException implements HubException
{
    public static function makeFor(DefinitionInterface $definition)
    {
        return new static("Unsupported definition " . get_class($definition));
    }
}
