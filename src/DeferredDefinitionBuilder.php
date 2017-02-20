<?php

namespace Nayjest\DI;

use Nayjest\DI\Exception\DefinitionBuilderException;

/**
 * Builds definitions that can be added to hub later using "register($hub)" method.
 *
 */
class DeferredDefinitionBuilder extends AbstractDefinitionBuilder
{
    /**
     * @var HubInterface
     */
    private $hub;

    /** @var DefinitionInterface[] */
    private $definitions = [];

    public function register(HubInterface $hub)
    {
        if ($this->hub !== null) {
            throw new DefinitionBuilderException("DefinitionBuilder already registered its definitions.");
        }
        $this->hub = $hub;
        $hub->addDefinitions($this->definitions);
        return $this;
    }

    protected function addDefinition(DefinitionInterface $definition)
    {
        if ($this->hub === null) {
            $this->definitions[] = $definition;
            return;
        }
        $this->hub->addDefinition($definition);
    }
}
