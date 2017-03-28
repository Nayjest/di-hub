<?php

namespace Nayjest\DI\Internal;

use Nayjest\DI\Builder\DefinitionBuilder;
use Nayjest\DI\Definition\DefinitionInterface;
use Nayjest\DI\HubInterface;

abstract class AbstractHub implements HubInterface
{
    /** @var ItemControllerInterface[] */
    protected $items;

    /**
     * @var RelationController
     */
    protected $relationController;

    /** @var  DefinitionBuilder */
    protected $builderInstance;

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
}
