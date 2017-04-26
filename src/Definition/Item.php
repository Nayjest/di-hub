<?php

namespace Nayjest\DI\Definition;

/**
 * Item definition.
 */
class Item extends Value implements DefinitionInterface
{
    /** @var Relation */
    public $relation;

    /**
     * Item constructor.
     *
     * @param $id
     * @param null|string|string[]|mixed $sourceDependencies
     *        dependency id(s) as string or array or strings;
     *        if $handler not passed to 3rd argument, will be used as value
     * @param callable|null $handler
     */
    public function __construct($id, $sourceDependencies = null, callable $handler = null)
    {
        if ($handler) {
            $source = null;
            $this->relation = new Relation($id, $sourceDependencies, $handler);
        } else {
            $source = $sourceDependencies;
        }
        parent::__construct($id, $source);
    }
}
