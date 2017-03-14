<?php

namespace Nayjest\DI\Internal;

/**
 * Class ItemControllerWrapper
 *
 * This class is used only with SubHub.
 *
 * @internal
 */
class ItemControllerWrapper
{
    /** @var RelationController */
    private $relationController;
    /**
     * @var ItemController
     */
    private $item;
    private $id;

    public function __construct(ItemController $item, $id, RelationController $relationController)
    {
        $this->relationController = $relationController;
        $this->item = $item;
        $this->id = $id;
        $this->initializeIfUsed();
        if ($this->isInitialized()) {
            $this->relationController->onInitialize($this->id, null);
        }
    }

    public function isInitialized()
    {
        return $this->item->isInitialized();
    }

    public function set($value)
    {
        $this->item->set($value);
    }

    public function &get($initialize = true)
    {
        return $this->item->get($initialize);
    }

    protected function initializeIfUsed()
    {
        if ($this->relationController->hasInitializedDependantFrom($this->id)) {
            $this->item->get(true);
        }
    }
}
