<?php

namespace Nayjest\DI;

use Nayjest\DI\Exception\ReadonlyException;

/**
 * Class Item
 *
 * @internal
 */
class ItemController
{
    /**
     * @var ItemDefinition
     */
    private $definition;

    private $initialized = false;

    private $value = null;

    /** @var RelationController */
    private $relationController;

    public function __construct(ItemDefinition $definition, RelationController $relationController)
    {
        $this->definition = $definition;
        $this->relationController = $relationController;
        $this->initializeIfUsed();
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
        $this->initialized = false;
        $this->initializeIfUsed();
    }

    public function &get($initialize = true)
    {
        if ($initialize && !$this->initialized) {
            $this->initialize();
        }
        return $this->value;
    }

    protected function initializeIfUsed()
    {
        if ($this->relationController->hasInitializedDependantFrom($this->definition->id)) {
            $this->initialize();
        }
    }

    protected function initialize()
    {
        $oldValue = $this->value;
        $this->value = $this->readSource();
        $this->relationController->onInitialize($this->definition->id, $oldValue);
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
