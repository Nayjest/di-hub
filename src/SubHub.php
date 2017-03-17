<?php

namespace Nayjest\DI;

use Nayjest\DI\Builder\DefinitionBuilder;
use Nayjest\DI\Definition\DefinitionInterface;
use Nayjest\DI\Definition\ItemDefinition;
use Nayjest\DI\Definition\RelationDefinition;
use Nayjest\DI\Exception\NotFoundException;
use Nayjest\DI\Exception\UnsupportedDefinitionTypeException;
use Nayjest\DI\Internal\ItemControllerWrapper;
use Nayjest\DI\Internal\RelationController;
use ReflectionClass;

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
class SubHub implements HubInterface
{

    /** @var string */
    private $prefix;

    /** @var Hub */
    protected $hub;

    /** @var  Hub */
    protected $externalHub;

    /** @var  DefinitionBuilder */
    private $builderInstance;

    public function __construct($namePrefix, HubInterface $internalHub, HubInterface $externalHub = null)
    {
        $this->prefix = $namePrefix;
        $this->hub = $internalHub;
        if ($externalHub) {
            $this->register($externalHub);
        }
    }

    public function getId()
    {
        return $this->prefix . 'hub';
    }

    protected function realExternalHub(HubInterface $hub)
    {
        while ($hub instanceof SubHub) {
            $hub = $hub->hub;
        }
        return $hub;
    }

    public function register(HubInterface $externalHub)
    {
        $this->externalHub = $externalHub;
        //$realExternalHub = $externalHub;
        $realExternalHub = $this->realExternalHub($externalHub);
        $externalHub->builder()->define($this->getId(), $this);

        $reflection = new ReflectionClass(Hub::class);
        $itemsProperty = $reflection->getProperty('items');
        $itemsProperty->setAccessible(true);
        $relationControllerProperty = $reflection->getProperty('relationController');
        $relationControllerProperty->setAccessible(true);
        /** @var RelationController $externalRelationController */
        $externalRelationController = $relationControllerProperty->getValue($realExternalHub);
        $internalItems = $itemsProperty->getValue($this->hub);
        $externalItems = $itemsProperty->getValue($realExternalHub);

        foreach ($internalItems as $id => $item) {
            $externalId = $this->prefixedId($id);
            $externalItems[$externalId] = new ItemControllerWrapper($id, $this->hub);

            # Define relation [item -> external.item]
            $this->hub->builder()->defineRelation(
                null,
                $id,
                function ($target, $source, $prevSource) use ($externalRelationController, $externalId) {
                    $externalRelationController->onInitialize($externalId, $prevSource);
                }
            );
        }
        $itemsProperty->setValue($realExternalHub, $externalItems);
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
            $this->externalHub->set($this->prefixedId($id), $value);
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

    public function &get($id)
    {
        if ($this->externalHub) {
            # Definitions can be delegated to external hub
            return $this->externalHub->get($this->prefixedId($id));
        } else {
            return $this->hub->get($id);
        }
    }

    public function has($id)
    {
        if ($this->externalHub) {
            # Definitions can be delegated to external hub
            return $this->externalHub->has($this->prefixedId($id));
        } else {
            return $this->hub->has($id);
        }
    }

    protected function delegateDefinitionToExternalHub(DefinitionInterface $definition)
    {
        if ($definition instanceof ItemDefinition) {
            $externalDefinition = new ItemDefinition(
                $this->prefixedId($definition->id),
                $definition->source,
                $definition->readonly
            );
        } elseif ($definition instanceof RelationDefinition) {
            $externalDefinition = new RelationDefinition(
                $this->prefixedId($definition->target),
                $this->prefixedId($definition->source),
                $definition->handler
            );
            $externalDefinition->propagated = $definition->propagated;
        } else {
            throw UnsupportedDefinitionTypeException::makeFor($definition);
        }
        $this->externalHub->addDefinition($externalDefinition);
    }

    public function isInitialized($id)
    {
        return $this->externalHub
            ? $this->externalHub->isInitialized($this->prefixedId($id))
            : $this->hub->isInitialized($id);
    }
}
