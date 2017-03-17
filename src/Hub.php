<?php
namespace Nayjest\DI;

use Nayjest\DI\Builder\DefinitionBuilder;
use Nayjest\DI\Definition\DefinitionInterface;
use Nayjest\DI\Definition\ItemDefinition;
use Nayjest\DI\Definition\RelationDefinition;
use Nayjest\DI\Exception\AlreadyDefinedException;
use Nayjest\DI\Exception\CanNotRemoveDefinitionException;
use Nayjest\DI\Exception\NotFoundException;
use Nayjest\DI\Exception\UnsupportedDefinitionTypeException;
use Nayjest\DI\Internal\ItemController;
use Nayjest\DI\Internal\RelationController;

class Hub implements HubInterface
{
    /** @var ItemController[] */
    private $items;

    private $relationController;

    protected $builderInstance;

    public function __construct(array $definitions = null)
    {
        $this->items = [];
        $this->relationController = new RelationController($this->items);
        if ($definitions !== null) {
            $this->addDefinitions($definitions);
        }
    }

    public function builder()
    {
        if ($this->builderInstance === null) {
            $this->builderInstance = new DefinitionBuilder($this);
        }
        return $this->builderInstance;
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

    /**
     * @param ItemDefinition[] $definitions
     * @return $this
     */
    public function addDefinitions(array $definitions)
    {
        foreach ($definitions as $definition) {
            $this->addDefinition($definition);
        }
        return $this;
    }

    public function &get($id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException;
        }
        $item = $this->items[$id];
        if ($item->isInitialized()) {
            return $item->get();
        } else {
            $val =& $item->get();
            $this->relationController->onInitialize($id, null);
            return $val;
        }
        //return $this->items[$id]->get();
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
        $item = $this->items[$id];
        $wasInitialized = $item->isInitialized();
        $prevVal = $wasInitialized?$item->get():null;
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
        return $this->has($id) && $this->items[$id]->isInitialized();
    }

    protected function addItemDefinition(ItemDefinition $definition)
    {
        $id = $definition->id;
        if ($this->has($id)) {
            throw new AlreadyDefinedException("Item '{$id}' already defined.");
        }
        $this->items[$id] = new ItemController($definition);
        if ($this->relationController->hasInitializedDependantFrom($id)) {
            $this->items[$id]->get(true);
            $this->relationController->onInitialize($id, null);
        }
    }

    public function remove($id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException;
        }
        $canRemove = $this->relationController->canRemove($id) && !$this->items[$id]->isInitialized();
        if (!$canRemove) {
            throw new CanNotRemoveDefinitionException;
        }
        unset($this->items[$id]);
        return $this;
    }
}
