<?php

namespace Nayjest\DI;

class ComponentDefinitions
{
    private $definitions;
    /**
     * @var Component
     */
    private $component;
    /**
     * @var array
     */
    private $extensions;

    public function __construct(array &$definitions, array &$extensions, Component $component)
    {
        $this->definitions = &$definitions;
        $this->component = $component;
        $this->extensions = $extensions;
    }

    public function define($id, $localId = null)
    {
        $definition = new Definition($id, $this->component, $localId);
        $this->definitions[$id] = $definition;
        return new DefinitionBuilder($definition);
    }

    public function extend($id)
    {
        $this->extensions[] = $id;
        return $this;
    }
}