<?php

namespace Nayjest\DI;

use Nayjest\DI\Definition\DefinitionInterface;
use Nayjest\DI\Internal\AbstractHub;

class HubWrapper extends AbstractHub
{
    /**
     * @var HubInterface
     */
    protected $hub;

    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }

    public function getIds()
    {
        return $this->hub->getIds();
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
        $this->hub->set($id, $value);
        return $this;
    }

    /**
     * @param DefinitionInterface $definition
     * @return $this
     */
    public function addDefinition(DefinitionInterface $definition)
    {
        $this->hub->addDefinition($definition);
        return $this;
    }

    public function &get($id)
    {
        return $this->hub->get($id);
    }

    public function has($id)
    {
        return $this->hub->has($id);
    }

    public function isInitialized($id)
    {
        return $this->hub->isInitialized($id);
    }
}
