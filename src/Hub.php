<?php
namespace Nayjest\DI;

use Nayjest\DI\Definition\DefinitionInterface;
use Nayjest\DI\Definition\ItemDefinition;
use Nayjest\DI\Definition\RelationDefinition;
use Nayjest\DI\Exception\AlreadyDefinedException;
use Nayjest\DI\Exception\CanNotRemoveDefinitionException;
use Nayjest\DI\Exception\NotFoundException;
use Nayjest\DI\Exception\UnsupportedDefinitionTypeException;
use Nayjest\DI\Internal\AbstractHub;
use Nayjest\DI\Internal\ItemController;
use Nayjest\DI\Internal\ItemControllerInterface;
use Nayjest\DI\Internal\RelationController;

/**
 * Class Hub
 *
 */
class Hub extends AbstractHub
{
    public function __construct(array $definitions = null)
    {
        $this->items = [];
        $this->relationController = new RelationController($this->items);
        if ($definitions !== null) {
            $this->addDefinitions($definitions);
        }
    }

    /**
     * @param DefinitionInterface $definition
     * @return $this
     */
    public function addDefinition(DefinitionInterface $definition)
    {
        if ($definition instanceof ItemDefinition) {
            $this->addItemDefinition($definition);
        } elseif ($definition instanceof RelationDefinition) {
            $this->relationController->addRelation($definition);
        } else {
            throw UnsupportedDefinitionTypeException::makeFor($definition);
        }
        return $this;
    }

    public function &get($id)
    {
        $item = $this->getItem($id);
        if ($item->isInitialized()) {
            return $item->get();
        } else {
            $val =& $item->get();
            $this->relationController->onInitialize($id, null);
            return $val;
        }
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
        $item = $this->getItem($id);
        $wasInitialized = $item->isInitialized();
        $prevVal = $wasInitialized ? $item->get() : null;
        $item->set($value);
        if ($wasInitialized) {
            $this->relationController->onInitialize($id, $prevVal);
        }
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

    /**
     * @param string $id
     * @return bool
     */
    public function isInitialized($id)
    {
        return $this->has($id) && $this->getItem($id)->isInitialized();
    }

    public function getIds()
    {
        return array_keys($this->items);
    }

    protected function addItemDefinition(ItemDefinition $definition)
    {
        $id = $definition->id;
        if ($this->has($id)) {
            throw new AlreadyDefinedException("Item '{$id}' already defined.");
        }
        $this->items[$id] = $item = $definition->controller ?: new ItemController($definition);
        if ($this->relationController->hasInitializedDependantFrom($id)) {
            $item->get(true);
            $this->relationController->onInitialize($id, null);
        }
    }

    /**
     * @param string $id
     * @return $this
     */
    public function remove($id)
    {
        $canRemove = !$this->getItem($id)->isInitialized() && $this->relationController->canRemove($id);
        if (!$canRemove) {
            throw new CanNotRemoveDefinitionException;
        }
        unset($this->items[$id]);
        return $this;
    }

    /**
     * @param string $id
     * @return ItemControllerInterface
     */
    protected function getItem($id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Item '$id' not found");
        }
        return $this->items[$id];
    }
}
