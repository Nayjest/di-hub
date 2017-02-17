<?php

namespace Nayjest\DI;

use Interop\Container\ContainerInterface;
use Nayjest\DI\Exception\DefinitionBuilderException;

class DefinitionBuilder implements ContainerInterface
{
    /**
     * @var HubInterface
     */
    private $hub;

    /** @var  string|null */
    private $currentItemId;

    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }

    public function defineMany(array $definitions)
    {
        foreach ($definitions as $id => $src) {
            $this->define($id, $src);
        }
        // Don't allow to call uses()/usedBy() after defineMany()
        // because it's not intuitive that last element will be used as current item
        $this->currentItemId = null;
        return $this;
    }

    public function define($id, $source = null, $readonly = false)
    {
        $this->currentItemId = $id;
        $this->hub->addDefinition(new ItemDefinition($id, $source, $readonly));
        return $this;
    }

    public function defineRelation($target, $source, callable $func)
    {
        $this->hub->addDefinition(new RelationDefinition($target, $source, $func));
    }

    public function uses($id, callable $func)
    {
        if (is_array($id)) {
            foreach($id as $identifier) {
                $this->uses($identifier, $func);
            }
            return $this;
        }
        if ($this->currentItemId === null) {
            throw new DefinitionBuilderException("Can't call 'uses' method with no current item id");
        }
        $this->hub->addDefinition(new RelationDefinition($this->currentItemId, $id, $func));
        return $this;
    }

    public function usedBy($id, callable $func)
    {
        if (is_array($id)) {
            foreach($id as $identifier) {
                $this->usedBy($identifier, $func);
            }
            return $this;
        }
        if ($this->currentItemId === null) {
            throw new DefinitionBuilderException("Can't call 'uses' method with no current item id");
        }
        $rel = new RelationDefinition($id, $this->currentItemId, $func);
        $this->hub->addDefinition($rel);
        return $this;
    }
}