<?php

namespace Nayjest\DI\Definition;

class Relation implements DefinitionInterface
{
    /**
     * ID of value(s) that will modify target or NULL in target should be modified by data outside the hub.
     *
     * @var string|null|array
     */
    public $source;

    /**
     * ID of value that will be modified by relation.
     *
     * @var string
     */
    public $target;

    /** @var  callable */
    public $handler;

    /** @var  string  */
    public $parent;

    public $propagated = true;

    /**
     * Relation constructor.
     *
     * @param string $target
     * @param string|string[]|null $source
     * @param callable $handler
     */
    public function __construct($target, $source, callable $handler)
    {
        $this->target = $target;
        $this->source = $source;
        $this->handler = $handler;
    }

    /**
     * Returns true if relation has multiple sources.
     *
     * Hub replaces multi-source relations to composition of relations internally.
     *
     * Unlike regular relations,
     * handler functions of multi-source relations accepts multiple source arguments:
     *
     * - multi-source relation arguments: &$target, $source1, $source2, ...$sourceN
     * - regular relation arguments: &$target, $source, $prevSource
     *
     * @return bool
     */
    public function isMultiSource()
    {
        return is_array($this->source);
    }
}
