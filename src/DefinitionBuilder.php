<?php

namespace Nayjest\DI;

class DefinitionBuilder extends AbstractDefinitionBuilder
{
    /**
     * @var HubInterface
     */
    private $hub;

    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }

    protected function addDefinition(DefinitionInterface $definition)
    {
        $this->hub->addDefinition($definition);
    }
}
