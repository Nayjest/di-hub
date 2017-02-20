<?php

namespace Nayjest\DI;

use Nayjest\DI\Exception\DefinitionBuilderException;

abstract class AbstractDefinitionBuilder
{
    /** @var  string|null */
    private $currentItemId;

    abstract protected function addDefinition(DefinitionInterface $definition);

    public final function defineMany(array $definitions)
    {
        foreach ($definitions as $id => $src) {
            $this->define($id, $src);
        }
        // Don't allow to call uses()/usedBy() after defineMany()
        // because it's not intuitive that last element will be used as current item
        $this->currentItemId = null;
        return $this;
    }

    public final function define($id, $source = null, $readonly = false)
    {
        $this->addDefinition(new ItemDefinition($id, $source, $readonly));
        $this->currentItemId = $id;
        return $this;
    }

    public final function defineRelation($target, $source, callable $func)
    {
        $this->addDefinition(new RelationDefinition($target, $source, $func));
        return $this;
    }

    public final function uses($id, callable $func)
    {
        if (is_array($id)) {
            foreach ($id as $identifier) {
                $this->uses($identifier, $func);
            }
            return $this;
        }
        if ($this->currentItemId === null) {
            throw new DefinitionBuilderException("Can't call 'uses' method with no current item id");
        }
        $this->addDefinition(new RelationDefinition($this->currentItemId, $id, $func));
        return $this;
    }

    public final function usedBy($id, callable $func)
    {
        if (is_array($id)) {
            foreach ($id as $identifier) {
                $this->usedBy($identifier, $func);
            }
            return $this;
        }
        if ($this->currentItemId === null) {
            throw new DefinitionBuilderException("Can't call 'uses' method with no current item id");
        }
        $this->addDefinition(new RelationDefinition($id, $this->currentItemId, $func));
        return $this;
    }
}
