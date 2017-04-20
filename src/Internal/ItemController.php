<?php

namespace Nayjest\DI\Internal;

use Nayjest\DI\Exception\InternalErrorException;
use Nayjest\DI\Exception\ReadonlyException;
use Nayjest\DI\Definition\Item;

/**
 * Class Item
 *
 * @internal
 */
class ItemController implements ItemControllerInterface
{
    /**
     * @var Item
     */
    private $definition;

    private $initialized = false;

    private $value = null;

    public function __construct(Item $definition)
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
    }

    public function &get()
    {
        if (!$this->isInitialized()) {
            throw new InternalErrorException(
                "Trying to read uninitialized item {$this->definition->id}"
            );
        }
        return $this->value;
    }

    public function initialize()
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
