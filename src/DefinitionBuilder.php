<?php

namespace Nayjest\DI;

class DefinitionBuilder
{
    /**
     * @var HubInterface
     */
    private $hub;
    private $definition;

    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }

    public function define($id, $value = null, $readonly = false)
    {
        $this->definition = new Definition($id, $value);
        if ($readonly) {
            $this->definition->readonly = true;
        }
        return $this;
    }
}