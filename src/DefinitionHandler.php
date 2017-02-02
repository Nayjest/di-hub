<?php

namespace Nayjest\DI;


class DefinitionHandler
{
    /**
     * @var Definition
     */
    private $definition;

    public function __construct(Definition $definition)
    {
        $this->definition = $definition;
    }

    public function hasSetter()
    {
        return $this->definition->hasSetter;
    }

    public function usedBy($id)
    {
        return in_array($id, array_keys($this->definition->trackedBy));
    }

    public function uses($id)
    {
        return in_array($id, array_keys($this->definition->tracks));
    }

    public function get()
    {
        if ($this->definition->getter === null) {
            $this->definition->getter = ComponentMethodNaming::getter($this->definition);
        }
        return $this->definition->component->{$this->definition->getter}();
    }

    public function set($value)
    {
        if (!$this->definition->hasSetter) {
            throw new \Exception("Setting {$this->definition->id} is not possible, setter is not defined");
        }
        if ($this->definition->setter === null) {
            $this->definition->setter = ComponentMethodNaming::setter($this->definition);
        }
        $this->definition->component->{$this->definition->setter}($value);
    }

    public function trackTo($id, $receiver, $newValue, $oldValue = null) {
        if ($this->definition->trackedBy[$id] === null) {
            $this->definition->trackedBy[$id] = ComponentMethodNaming::trackedBy($this->definition, $id);
        }
        $this->definition->component->{$this->definition->trackedBy[$id]}(
            $receiver,
            $newValue,
            $oldValue
        );
    }

    public function trackFrom($id, $receiver, $newValue, $oldValue = null) {
        if ($this->definition->tracks[$id] === null) {
            $this->definition->tracks[$id] = ComponentMethodNaming::tracks($this->definition, $id);
        }
        $this->definition->component->{$this->definition->tracks[$id]}(
            $receiver,
            $newValue,
            $oldValue
        );
    }
}