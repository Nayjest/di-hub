<?php
namespace Nayjest\DI;

use Nayjest\DI\Exception\AlreadyDefinedException;
use Nayjest\DI\Exception\NotFoundException;

class Hub implements HubInterface
{
    /** @var ItemController[] */
    private $items = [];

    private $relationController;

    public function __construct()
    {
        $this->relationController = new RelationController($this->items, $this);
    }

    /**
     * @param Definition $definition
     * @return $this
     */
    public function addDefinition(Definition $definition)
    {
        if ($this->has($definition->id)) {
            throw new AlreadyDefinedException;
        }
        $this->items[$definition->id] = new ItemController($definition, $this->relationController);
        $this->relationController->onNewItemOrValue($definition->id);
        return $this;
    }

    /**
     * @param Definition[] $definitions
     * @return $this
     */
    public function addDefinitions(array $definitions)
    {
        foreach($definitions as $definition) {
            $this->addDefinition($definition);
        }
        return $this;
    }


    public function get($id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException;
        }
        return $this->items[$id]->get();
    }


    /**
     * Set's item value.
     * Throws exception if item isn't defined or has no setter.
     *
     * @api
     * @param string $id
     * @param mixed $value
     * @return $this
     */
    public function set($id, $value)
    {
        if (!$this->has($id)) {
            throw new NotFoundException;
        }
        $this->items[$id]->set($value);
        return $this;
    }

    /**
     * Returns true if hub contains item with specified ID, otherwise returns false.
     *
     * @api
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->items);
    }
}
