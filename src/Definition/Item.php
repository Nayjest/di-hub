<?php

namespace Nayjest\DI\Definition;

use Nayjest\DI\Internal\ItemControllerInterface;

/**
 * Item definition.
 */
class Item implements DefinitionInterface
{
    /** @var  string */
    public $id;

    public $readonly = false;

    /**
     * If item source is callable, it's result will be used as value.
     *
     * @var callable|mixed
     */
    public $source;

    /**
     * It's possible to specify item controller instance.
     * @internal
     * @var ItemControllerInterface|null
     */
    public $controller = null;

    /**
     * ItemDefinition constructor.
     * @param $id
     * @param mixed|callable $source
     * @param bool $readonly
     */
    public function __construct($id, $source = null, $readonly = false)
    {
        $this->id = $id;
        $this->source = $source;
        $this->readonly = $readonly;
    }
}
