<?php

namespace Nayjest\DI;

class Definition
{
    public function __construct($id, Component $component, $localId = null)
    {
        $this->id = $id;
        $this->localId = $localId;
        $this->component = $component;
    }

    public $id;
    public $hasSetter = false;
    public $setter;
    public $getter;
    public $trackedBy = [];
    public $tracks = [];
    public $localId;
    /** @var  Component */
    public $component;

    /** @var  DefinitionHandler */
    public $handler;


    public function isUsedBy($id)
    {
        return in_array($id, array_keys($this->trackedBy));
    }

    public function uses($id)
    {
        return in_array($id, array_keys($this->tracks));
    }

    public function get()
    {
        if ($this->getter === null) {
            $this->getter = ComponentMethodNaming::getter($this);
        }
        return $this->component->{$this->getter}();
    }

    public function set($value)
    {
        if (!$this->hasSetter) {
            throw new \Exception("Setting $this->id is not possible, setter is not defined");
        }
        if ($this->setter === null) {
            $this->setter = ComponentMethodNaming::setter($this);
        }
        $this->component->{$this->setter}($value);
    }

    public function trackTo($id, $receiver, $newValue, $oldValue = null) {
        if ($this->trackedBy[$id] === null) {
            $this->trackedBy[$id] = ComponentMethodNaming::trackedBy($this, $id);
        }
        $this->component->{$this->trackedBy[$id]}(
            $receiver,
            $newValue,
            $oldValue
        );
    }

    public function trackFrom($id, $receiver, $newValue, $oldValue = null) {
        if ($this->tracks[$id] === null) {
            $this->tracks[$id] = ComponentMethodNaming::tracks($this, $id);
        }
        $this->component->{$this->tracks[$id]}(
            $receiver,
            $newValue,
            $oldValue
        );
    }
}