<?php

namespace Nayjest\DI;

class RelationController
{
    /** @var ItemController[] */
    private $items;

    public function __construct(array &$items, HubInterface $hub)
    {
        $this->items = &$items;
    }

    public function onNewItemOrValue($id)
    {
        if ($this->hasInitializedDependantFrom($id)) {
            // forces initialization
            $this->items[$id]->get();
        }
    }

    public function onInitialize($id, $prevValue)
    {
        $this->handleDependencies($id);
        $this->notifyDependant($id, $prevValue);
    }

    protected function hasInitializedDependantFrom($id)
    {
        foreach ($this->getRelationsBySource($id) as $relation) {
            if ($this->items[$relation->target]->isInitialized()) {
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
            $targetItem = $this->items[$relation->target];
            if (!$targetItem->isInitialized()) {
                continue;
            }
            if ($relation->propagated) {
                $propagated[$relation->target] = $targetItem->get();
            }
            $this->handleRelation($relation, false, $prevValue);
        }
        foreach ($propagated as $updatedId => $valueBeforeUpdate) {
            $this->notifyDependant($updatedId, $valueBeforeUpdate);
        }
    }

    protected function handleRelation(Relation $relation, $initializeSource, $prevSourceValue)
    {
        $source = $relation->source?$this->items[$relation->source]->get($initializeSource):null;
        $target = &$this->items[$relation->target]->get(false);
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
        foreach ($this->items as $item) {
            foreach ($item->definition->relations as $relation) {
                if ($relation->target === $id) {
                    $res[] = $relation;
                }
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
        foreach ($this->items as $item) {
            foreach ($item->definition->relations as $relation) {
                if ($relation->source === $id) {
                    $res[] = $relation;
                }
            }
        }
        return $res;
    }
}
