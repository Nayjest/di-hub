<?php

namespace Nayjest\DI;

use Nayjest\DI\Internal\Definition;

class ComponentDefinitions
{
    private $definitions;

    /**
     * @var array
     */
    private $extensions;

    public function __construct(array &$definitions, array &$extensions)
    {
        $this->definitions = &$definitions;
        $this->extensions = &$extensions;
    }

    public function define($id)
    {
        $definition = new Definition($id);
        $this->definitions[$id] = $definition;
        return new DefinitionBuilder($definition);
    }

    public function add(Definition $definition)
    {
        $this->definitions[$definition->id] = $definition;
        return $this;
    }

    public function extend($id)
    {
        $this->extensions[] = $id;
        return $this;
    }
}
