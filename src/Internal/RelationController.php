<?php

namespace Nayjest\DI\Internal;

use Nayjest\DI\Exception\AlreadyDefinedException;
use Nayjest\DI\Definition\Relation;
use Nayjest\DI\Exception\NotFoundException;

/**
 * Class RelationController
 *
 * @internal
 */
class RelationController
{
    /** @var ItemControllerInterface[] */
    private $items;

    /** @var Relation[] */
    protected $relations = [];

    public function __construct(array &$items)
    {
        $this->items = &$items;
    }

    public function addRelation(Relation $definition)
    {
        if (in_array($definition, $this->relations, true)) {
            throw new AlreadyDefinedException(
                "Relation ('{$definition->source}' to '{$definition->target}') already defined."
            );
        }
        $this->relations[] = $definition;
        $target = $definition->target;
        $source = $definition->source;

        $needHandleImmediate =
            isset($this->items[$target])
            && isset($this->items[$source])
            && $this->items[$target]->isInitialized();

        if ($needHandleImmediate) {
            if ($this->items[$source]->isInitialized()) {
                $this->handleRelation($definition);
            } else {
                $this->initialize($source, null);
            }
        }
    }

    public function initialize($id, $prevValue, Relation $fromRelation = null)
    {
        $this->items[$id]->initialize();
        $this->handleDependencies($id);
        $this->notifyDependant($id, $prevValue, $fromRelation);
    }

    public function canRemove($id)
    {
        return empty($this->getRelationsBySource($id));
    }

    public function hasInitializedDependantFrom($id)
    {
        foreach ($this->getRelationsBySource($id) as $relation) {
            if ($relation->target !== null && $this->items[$relation->target]->isInitialized()) {
                return true;
            }
        }
        return false;
    }

    protected function handleDependencies($id)
    {
        foreach ($this->getRelationsByTarget($id) as $relation) {
            $this->handleRelation($relation, null);
        }
    }

    protected function notifyDependant($id, $prevValue, Relation $excluded = null)
    {
        $propagated = [];
        foreach ($this->getRelationsBySource($id) as $relation) {
            if ($relation === $excluded) {
                continue;
            }
            if ($relation->target !== null) {
                $targetItem = $this->items[$relation->target];
                if (!$targetItem->isInitialized()) {
                    continue;
                }
                if ($relation->propagated) {
                    $propagated[$relation->target] = $targetItem->get();
                }
            }
            $this->handleRelation($relation, $prevValue);
        }
        foreach ($propagated as $updatedId => $valueBeforeUpdate) {
            $this->notifyDependant($updatedId, $valueBeforeUpdate);
        }
    }

    protected function handleRelation(Relation $relation, $prevSourceValue = null)
    {
        if ($relation->source === null) {
            $source = null;
        } elseif (array_key_exists($relation->source, $this->items)) {
            if (!$this->items[$relation->source]->isInitialized()) {
                $this->initialize($relation->source, null, $relation);
            }
            $source = $this->items[$relation->source]->get();
        } else {
            throw new NotFoundException(
                "Item '$relation->source' not found. It's required to update '$relation->target'."
            );
        }
        if ($relation->target === null) {
            $target = null;
        } else {
            $target = &$this->items[$relation->target]->get();
        }
        call_user_func_array($relation->handler, [
            &$target,
            $source,
            $prevSourceValue
        ]);
    }

    /**
     * @param $id
     * @return Relation[]
     */
    protected function getRelationsByTarget($id)
    {
        $res = [];
        foreach ($this->relations as $relation) {
            if ($relation->target === $id) {
                $res[] = $relation;
            }
        }
        return $res;
    }

    /**
     * @param $id
     * @return Relation[]
     */
    protected function getRelationsBySource($id)
    {
        $res = [];
        foreach ($this->relations as $relation) {
            if ($relation->source === $id) {
                $res[] = $relation;
            }
        }
        return $res;
    }
}
