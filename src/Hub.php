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
    const INTERNAL_DEFINITION_PREFIX = 'internal_';

    /**
     * Hub constructor.
     * @param DefinitionInterface[]|null $definitions
     */
    public function __construct(array $definitions = null)
    {
        $this->items = [];
        $this->relationController = new RelationController($this->items);
        if ($definitions !== null) {
            $this->addDefinitions($definitions);
        }
    }

    /**
     * Adds definition to hub.
     *
     * @param DefinitionInterface $definition
     * @return $this
     */
    public function addDefinition(DefinitionInterface $definition)
    {
        if ($definition instanceof ItemDefinition) {
            $this->addItemDefinition($definition);
        } elseif ($definition instanceof RelationDefinition) {
            if (is_array($definition->source)) {
                $this->makeMultiSourceRelation($definition);
            } else {
                $this->relationController->addRelation($definition);
            }
        } else {
            throw UnsupportedDefinitionTypeException::makeFor($definition);
        }
        return $this;
    }

    protected function makeMultiSourceRelation(RelationDefinition $multiSourceRelation)
    {
        $tempItemName = self::INTERNAL_DEFINITION_PREFIX . rand(1, PHP_INT_MAX - 1);
        $tempItem = new ItemDefinition($tempItemName, function () use ($multiSourceRelation) {
            $data = [];
            foreach ($multiSourceRelation->source as $sourceName) {
                $data[$sourceName] = $this->get($sourceName);
            }
            return $data;
        });
        $tmpToTargetRelation = new RelationDefinition(
            $multiSourceRelation->target,
            $tempItemName,
            function (&$target, $source) use ($multiSourceRelation) {
                $arguments = [
                    &$target
                ];
                foreach ($multiSourceRelation->source as $srcName) {
                    $arguments[] = $source[$srcName];
                }
                call_user_func_array($multiSourceRelation->handler, $arguments);
            }
        );
        $definitions = [
            $tempItem,
            $tmpToTargetRelation
        ];
        foreach ($multiSourceRelation->source as $srcName) {
            $relation = new RelationDefinition($tempItemName, $srcName, function (&$target, $src) use ($srcName) {
                $target[$srcName] = $src;
            });
            $definitions[] = $relation;
        }
        $this->addDefinitions($definitions);
    }

    /**
     * Returns item by reference.
     *
     * @param string $id
     * @return mixed
     */
    public function &get($id)
    {
        $item = $this->getItem($id);
        if (!$item->isInitialized()) {
            $this->relationController->initialize($id, null);
        }
        return $item->get();
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

        if ($item->isInitialized()) {
            $prevVal = $item->get();
            $item->set($value);
            $this->relationController->initialize($id, $prevVal);
        } else {
            $item->set($value);
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

    /**
     * Removes item from hub.
     *
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

    protected function addItemDefinition(ItemDefinition $definition)
    {
        $id = $definition->id;
        if ($this->has($id)) {
            throw new AlreadyDefinedException("Item '{$id}' already defined.");
        }
        $this->items[$id] = $definition->controller ?: new ItemController($definition);
        if ($this->relationController->hasInitializedDependantFrom($id)) {
            $this->relationController->initialize($id, null);
        }
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
