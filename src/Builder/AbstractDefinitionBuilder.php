<?php

namespace Nayjest\DI\Builder;

use Nayjest\DI\Definition\DefinitionInterface;
use Nayjest\DI\Exception\DefinitionBuilderException;
use Nayjest\DI\Definition\Value;
use Nayjest\DI\Definition\Relation;

abstract class AbstractDefinitionBuilder
{
    /** @var  string|null */
    protected $currentItemId;

    abstract protected function addDefinition(DefinitionInterface $definition);

    final public function defineMany(array $definitions)
    {
        foreach ($definitions as $id => $src) {
            $this->define($id, $src);
        }
        // Don't allow to call uses()/usedBy() after defineMany()
        // because it's not intuitive that last element will be used as current item
        $this->currentItemId = null;
        return $this;
    }

    final public function define($id, $source = null, $readonly = false)
    {
        $this->addDefinition(new Value($id, $source, $readonly));
        $this->currentItemId = $id;
        return $this;
    }

    final public function defineRelation($target, $source, callable $func)
    {
        $this->addDefinition(new Relation($target, $source, $func));
        return $this;
    }

    final public function uses($id, callable $func)
    {
        if ($this->currentItemId === null) {
            throw new DefinitionBuilderException("Can't call 'uses' method with no current item id");
        }
        $this->addDefinition(new Relation($this->currentItemId, $id, $func));
        return $this;
    }

    final public function usedBy($id, callable $func)
    {
        if ($this->currentItemId === null) {
            throw new DefinitionBuilderException("Can't call 'uses' method with no current item id");
        }
        $this->addDefinition(new Relation($id, $this->currentItemId, $func));
        return $this;
    }
}
