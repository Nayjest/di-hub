<?php

namespace Nayjest\DI\Internal;

use Nayjest\DI\Exception\ReadonlyException;
use Nayjest\DI\Definition\ItemDefinition;

/**
 * Class Item
 *
 * @internal
 */
class ItemController implements ItemControllerInterface
{
    /**
     * @var ItemDefinition
     */
    private $definition;

    private $initialized = false;

    private $value = null;

    public function __construct(ItemDefinition $definition)
    {
        $this->definition = $definition;
    }

    public function isInitialized()
    {
        return $this->initialized;
    }

    public function set($value)
    {
        if ($this->definition->readonly) {
            throw new ReadonlyException("Can't modify {$this->definition->id}, it's marked as readonly.");
        }
        $this->definition->source = $this->wrapIfCallable($value);
        if ($this->initialized) {
            $this->initialize();
        }
    }

    public function &get($initialize = true)
    {
        if ($initialize && !$this->initialized) {
            $this->initialize();
        }
        return $this->value;
    }

    protected function initialize()
    {
        $this->value = $this->readSource();
        $this->initialized = true;
    }

    protected function readSource()
    {
        $src = $this->definition->source;
        return $this->isCallable($src) ? call_user_func($src) : $src;
    }

    protected function isCallable($value)
    {
        return !is_string($value) && is_callable($value);
    }

    protected function wrapIfCallable($value)
    {
        return $this->isCallable($value) ? function () use ($value) {
            return $value;
        } : $value;
    }
}
