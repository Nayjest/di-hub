<?php

namespace Nayjest\DI\Definition;

class Relation implements DefinitionInterface
{
    /** @var  string|null|array */
    public $source;

    /** @var  string */
    public $target;

    /** @var  callable */
    public $handler;

    /** @var  string  */
    public $parent;

    public $propagated = true;

    public function __construct($target, $source, callable $handler)
    {
        $this->target = $target;
        $this->source = $source;
        $this->handler = $handler;
    }
}
