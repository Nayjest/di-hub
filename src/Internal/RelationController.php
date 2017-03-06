<?php

namespace Nayjest\DI\Internal;

use Nayjest\DI\Exception\AlreadyDefinedException;
use Nayjest\DI\Definition\RelationDefinition;

/**
 * Class RelationController
 *
 * @internal
 */
class RelationController
{
    /** @var ItemController[] */
    private $items;
    /** @var RelationDefinition[] */
    private $relations = [];

    public function __construct(array &$items)
    {
        $this->items = &$items;
    }

    public function addRelation(RelationDefinition $definition)
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
                $this->handleRelation($definition, true);
            } else {
                // get() will call initialization
                // and relation will be processed on init
                $this->items[$source]->get();
            }
        }
    }

    public function onInitialize($id, $prevValue)
    {
        $this->handleDependencies($id);
        $this->notifyDependant($id, $prevValue);
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
            $this->handleRelation($relation, true, null);
        }
    }

    protected function notifyDependant($id, $prevValue)
    {
        $propagated = [];
        foreach ($this->getRelationsBySource($id) as $relation) {
            if ($relation->target !== null) {
                $targetItem = $this->items[$relation->target];
                if (!$targetItem->isInitialized()) {
                    continue;
                }
                if ($relation->propagated) {
                    $propagated[$relation->target] = $targetItem->get();
                }
            }
            $this->handleRelation($relation, false, $prevValue);
        }
        foreach ($propagated as $updatedId => $valueBeforeUpdate) {
            $this->notifyDependant($updatedId, $valueBeforeUpdate);
        }
    }

    protected function handleRelation(RelationDefinition $relation, $initializeSource, $prevSourceValue = null)
    {
        $source = $relation->source ? $this->items[$relation->source]->get($initializeSource) : null;
        if ($relation->target === null) {
            $target = null;
        } else {
            $target = &$this->items[$relation->target]->get(false);
        }
        call_user_func_array($relation->handler, [
            &$target,
            $source,
            $prevSourceValue
        ]);
    }

    /**
     * @param $id
     * @return RelationDefinition[]
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
     * @return RelationDefinition[]
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
