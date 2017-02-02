<?php

namespace Nayjest\DI;

use Nayjest\DI\Internal\Definition;

class DefinitionBuilder
{
    /**
     * @var Definition
     */
    protected $definition;


    public function __construct(Definition $definition)
    {
        $this->definition = $definition;
    }

    public function withSetter()
    {
        $this->definition->hasSetter = true;
        return $this;
    }

    public function usedBy($id, $method = null)
    {
        $this->definition->usedBy[$id] = $method;
        return $this;
    }

    public function uses($id, $method = null)
    {
        $this->definition->uses[$id] = $method;
        return $this;
    }

    public function namedLocallyAs($id)
    {
        $this->definition->localId = $id;
        return $this;
    }
}