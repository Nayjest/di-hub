<?php

namespace Nayjest\DI\Definition;

use Nayjest\DI\Internal\ItemControllerInterface;

/**
 * Value definition.
 */
class Value implements DefinitionInterface
{
    /**
     * @todo looks like private values not works with sub-hubs, that much limits it's usage
     */
    const FLAG_PRIVATE = 1;

    /**
     * Readonly item can't be modified via $hub->set()
     * Readonly item still can be modified from relation.
     */
    const FLAG_READONLY = 2;

    /**
     * Identifier.
     *
     * @var  string
     */
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
