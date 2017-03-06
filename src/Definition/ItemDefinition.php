<?php

namespace Nayjest\DI\Definition;

/**
 * Class Definition.
 * Stores item definition.
 *
 */
class ItemDefinition implements DefinitionInterface
{
    /** @var  string */
    public $id;

    public $readonly = false;

    /**
     * Getter is called when value isn't initialized yet. It should return any value.
     *
     * @var callable|mixed
     */
    public $source;

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
