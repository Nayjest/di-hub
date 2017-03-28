<?php

namespace Nayjest\DI;

use Nayjest\DI\Definition\DefinitionInterface;
use Nayjest\DI\Definition\ItemDefinition;
use Nayjest\DI\Definition\RelationDefinition;
use Nayjest\DI\Internal\AbstractHub;
use Nayjest\DI\Internal\ItemControllerWrapper;

/**
 * Class SubHub
 *
 * This class allows to organize hierarchy of hubs.
 * It acts as full-featured decorator for nested hub and exposes it's data to parent(external) hub.
 *
 * All data of nested hub will be accessible in parent hub with keys prefixed by SubHub prefix.
 *
 * SubHub maintains relations between items of parent and nested hubs
 * (relations with target or source from parent hub must be defined ).
 *
 */
class SubHub extends HubWrapper
{

    /** @var string */
    private $prefix;

    /** @var  AbstractHub */
    protected $externalHub;

    /**
     * SubHub constructor.
     * @param string $namePrefix
     * @param AbstractHub $internalHub
     * @param AbstractHub|null $externalHub
     */
    public function __construct($namePrefix, AbstractHub $internalHub, AbstractHub $externalHub = null)
    {
        parent::__construct($internalHub);
        $this->prefix = $namePrefix;
        # required for multilevel hierarchy
        $this->relationController = $internalHub->relationController;
        if ($externalHub) {
            $this->register($externalHub);
        }
    }

    public function register(HubInterface $externalHub)
    {
        $this->externalHub = $externalHub;
        $externalHub->addDefinition(new ItemDefinition($this->getId(), $this));
        foreach ($this->hub->getIds() as $id) {
            $this->exposeItem($id);
        }
    }

    public function getId()
    {
        return $this->prefix . 'hub';
    }

    protected function prefixedId($id)
    {
        return ($id === null) ? null : $this->prefix . $id;
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
        if ($this->externalHub) {
            $externalId = $this->prefixedId($id);
            $this->externalHub->set($externalId, $value);
        } else {
            parent::set($id, $value);
        }
        return $this;
    }

    /**
     * @param DefinitionInterface $definition
     * @return $this
     */
    public function addDefinition(DefinitionInterface $definition)
    {
        parent::addDefinition($definition);
        if ($this->externalHub && $definition instanceof ItemDefinition) {
            $this->exposeItem($definition->id);
        }
        return $this;
    }

    /**
     * @param $internalId
     * @return ItemDefinition
     */
    protected function makeExternalItemDefinition($internalId)
    {
        $definition = new ItemDefinition($this->prefixedId($internalId));
        $definition->controller = new ItemControllerWrapper($internalId, $this->hub);
        return $definition;
    }

    protected function exposeItem($internalId)
    {
        $itemDefinition = $this->makeExternalItemDefinition($internalId);
        $this->externalHub->addDefinition($itemDefinition);
        $this->hub->addDefinition($this->makeExternalInitCallerRelation($itemDefinition));
    }

    protected function makeExternalInitCallerRelation(ItemDefinition $item)
    {
        /** @var ItemControllerWrapper $wrapper */
        $wrapper = $item->controller;
        $handler = function ($target, $source, $prevSource) use ($item, $wrapper) {
            if ($wrapper->initializingNow) {
                return;
            }
            $this->externalHub->relationController->onInitialize($item->id, $prevSource);
        };
        return new RelationDefinition(null, $wrapper->internalId, $handler);
    }
}
