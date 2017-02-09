<?php

namespace Nayjest\DI;

/**
 * Class Definition.
 * Stores item definition.
 *
 */
class Definition
{
    /** @var  string */
    public $id;

    public $readonly = false;

//    /**
//     * Called when setting new value.
//     *
//     * First argument = &$value
//     *
//     * @var callable
//     */
//    public $onSet;

    /**
     * Getter is called when value isn't initialized yet. It should return any value.
     *
     * @var callable|mixed
     */
    public $source;

    /** @var Relation[] */
    public $relations = [];

    public function __construct($id, $source = null)
    {
        $this->id = $id;
        $this->source = $source;
    }
}
