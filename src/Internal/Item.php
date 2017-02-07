<?php

namespace Nayjest\DI\Internal;

use Nayjest\DI\ComponentInterface;
use Nayjest\DI\Exception\ReadonlyException;

/**
 * Class Item
 *
 * @internal
 */
class Item
{
    /**
     * @var Definition
     */
    private $definition;
    /**
     * @var ComponentInterface
     */
    private $component;

    private $value;

    private $initialized = false;

    public function __construct(Definition $definition, ComponentInterface $component)
    {
        $this->definition = $definition;
        $this->component = $component;
    }

    public function isInitialized()
    {
        return $this->initialized;
    }

    public function usedBy($id)
    {
        return in_array($id, array_keys($this->definition->usedBy));
    }

    public function uses($id)
    {
        return in_array($id, array_keys($this->definition->uses));
    }

    public function getUsedIds()
    {
        return array_keys($this->definition->uses);
    }

    public function getUsedByIds()
    {
        return array_keys($this->definition->usedBy);
    }

    public function initializeValue()
    {
        if ($this->definition->getter === null) {
            $this->definition->getter = ComponentMethodNaming::getter($this->definition);
        }
        $this->value = $this->component->handle($this->definition->getter, []);
        $this->initialized = true;
        return $this->value;
    }

    public function set($value)
    {
        if (!$this->definition->hasSetter) {
            throw new ReadonlyException("Can't modify {$this->definition->id}, setter is not defined.");
        }
        if ($this->definition->setter === null) {
            $this->definition->setter = ComponentMethodNaming::setter($this->definition);
        }
        $this->component->handle($this->definition->setter, [$value]);
    }

    public function useItem($id, $newValue, $oldValue = null)
    {
        if ($this->definition->uses[$id] === null) {
            $this->definition->uses[$id] = ComponentMethodNaming::tracks($this->definition, $id);
        }
        $this->component->handle($this->definition->uses[$id], [
            &$this->value,
            $newValue,
            $oldValue
        ]);
    }

    public function useItByItem($id, &$receiver, $prevValue = null)
    {
        if ($this->definition->usedBy[$id] === null) {
            $this->definition->usedBy[$id] = ComponentMethodNaming::trackedBy($this->definition, $id);
        }
        $this->component->handle($this->definition->usedBy[$id], [
            &$receiver,
            $this->value,
            $prevValue
        ]);
    }

    public function getValue()
    {
        return $this->value;
    }

    public function &getValueRef()
    {
        return $this->value;
    }
}
