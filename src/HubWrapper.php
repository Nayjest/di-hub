<?php

namespace Nayjest\DI;

use Nayjest\DI\Definition\DefinitionInterface;
use Nayjest\DI\Internal\AbstractHub;

/**
 * HubWrapper is a base class for hub wrappers/decorators.
 */
class HubWrapper extends AbstractHub
{
    /**
     * @var HubInterface
     */
    protected $hub;

    /**
     * Constructor.
     *
     * @param HubInterface $hub
     */
    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }

    /**
     * @return string[]
     */
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

    /**
     * @param string $id
     * @return mixed
     */
    public function &get($id)
    {
        return $this->hub->get($id);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return $this->hub->has($id);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function isInitialized($id)
    {
        return $this->hub->isInitialized($id);
    }

    /**
     * Removes definition from hub.
     *
     * @param string|DefinitionInterface $target definition instance or id
     * @return $this
     */
    public function remove($target)
    {
        $this->hub->remove($target);
        return $this;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function isPrivate($id)
    {
        return $this->hub->isPrivate($id);
    }
}
