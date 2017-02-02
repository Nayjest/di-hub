<?php

namespace Nayjest\DI;

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
        //$method = $method ?: ComponentMethodNaming::trackedBy($this->definition, $id);
        $this->definition->trackedBy[$id] = $method;
        return $this;
    }

    public function uses($id, $method = null)
    {
        $this->definition->tracks[$id] = $method;// ?: ComponentMethodNaming::tracks($this->definition, $id);
        return $this;
    }

    public function namedLocallyAs($id)
    {
        $this->definition->localId = $id;
        return $this;
    }
}