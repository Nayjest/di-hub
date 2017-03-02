<?php

namespace Nayjest\DI;

use Nayjest\DI\Exception\UnsupportedDefinitionTypeException;
use ReflectionClass;

/**
 * Class SubHub
 *
 * This class allows to organize hierarchy of hubs.
 * It acts as full-featured decorator for nested hub and exposes it's data to parent(external) hub.
 *
 * All data of nested hub will be accessible in parent hub with keys in following format:
 * '<sub_hub_id>.<item_id_in_nested_hub>'
 *
 * SubHub maintains relations between items of parent and nested hubs
 * (relations with target or source from parent hub must be defined ).
 *
 */
class SubHub implements HubInterface
{
    /** @var Hub */
    private $hub;
    /** @var  Hub */
    private $externalHub;
    private $id;
    private $builderInstance;

    public function __construct($id, Hub $internalHub, Hub $externalHub = null)
    {
        $this->id = $id;
        $this->hub = $internalHub;
        if ($externalHub) {
            $this->register($externalHub);
        }
    }

    public function register(HubInterface $externalHub)
    {
        $this->externalHub = $externalHub;

        $externalHub->builder()->define($this->id, $this);

        $reflection = new ReflectionClass(Hub::class);
        $itemsProperty = $reflection->getProperty('items');
        $itemsProperty->setAccessible(true);
        $relationControllerProperty = $reflection->getProperty('relationController');
        $relationControllerProperty->setAccessible(true);
        /** @var RelationController $externalRelationController */
        $externalRelationController = $relationControllerProperty->getValue($externalHub);
        $internalItems = $itemsProperty->getValue($this->hub);
        $externalItems = $itemsProperty->getValue($externalHub);

        foreach ($internalItems as $id => $item) {
            $externalId = $this->externalId($id);
            $externalItems[$externalId] = new ItemControllerWrapper($item, $externalId, $externalRelationController);

            # Define relation [item -> external.item]
            $this->hub->builder()->defineRelation(
                null,
                $id,
                function ($target, $source, $prevSource) use ($externalRelationController, $externalId) {
                    $externalRelationController->onInitialize($externalId, $prevSource);
                }
            );
        }
        $itemsProperty->setValue($externalHub, $externalItems);
    }

    protected function externalId($internalId)
    {
        return ($internalId === null) ? null : "{$this->id}.{$internalId}";
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
            $this->externalHub->set($this->externalId($id), $value);
        } else {
            $this->hub->set($id, $value);
        }
        return $this;
    }

    /**
     * @param DefinitionInterface $definition
     * @return $this
     */
    public function addDefinition(DefinitionInterface $definition)
    {
        if ($this->externalHub) {
            $this->delegateDefinitionToExternalHub($definition);
        } else {
            $this->hub->addDefinition($definition);
        }
        return $this;
    }

    /**
     * @param DefinitionInterface[] $definitions
     * @return $this
     */
    public function addDefinitions(array $definitions)
    {
        foreach ($definitions as $definition) {
            $this->addDefinition($definition);
        }
        return $this;
    }

    /**
     * @return DefinitionBuilder
     */
    public function builder()
    {
        if ($this->builderInstance === null) {
            $this->builderInstance = new DefinitionBuilder($this);
        }
        return $this->builderInstance;
    }

    public function get($id)
    {
        if ($this->externalHub) {
            # Definitions can be delegated to external hub
            return $this->externalHub->get($this->externalId($id));
        } else {
            return $this->hub->get($id);
        }
    }

    public function has($id)
    {
        if ($this->externalHub) {
            # Definitions can be delegated to external hub
            return $this->externalHub->has($this->externalId($id));
        } else {
            return $this->hub->has($id);
        }
    }

    protected function delegateDefinitionToExternalHub(DefinitionInterface $definition)
    {
        if ($definition instanceof ItemDefinition) {
            $externalDefinition = new ItemDefinition(
                $this->externalId($definition->id),
                $definition->source,
                $definition->readonly
            );
        } elseif ($definition instanceof RelationDefinition) {
            $externalDefinition = new RelationDefinition(
                $this->externalId($definition->target),
                $this->externalId($definition->source),
                $definition->handler
            );
            $externalDefinition->propagated = $definition->propagated;
        } else {
            throw UnsupportedDefinitionTypeException::makeFor($definition);
        }
        $this->externalHub->addDefinition($externalDefinition);
    }
}