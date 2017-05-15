<?php

namespace Nayjest\DI\Definition;

use Nayjest\DI\Internal\ItemControllerInterface;

/**
 * Value definition.
 */
class Value implements DefinitionInterface
{
    const FLAG_PRIVATE = 1;
    const FLAG_READONLY = 2;

    /** @var  string */
    public $id;

    public $flags;

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
     * @param int $flags
     */
    public function __construct($id, $source = null, $flags = 0)
    {
        $this->id = $id;
        $this->source = $source;
        $this->flags = $flags;
    }

    /**
     * @param int $flags
     * @return Value
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;
        return $this;
    }
}
