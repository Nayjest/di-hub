<?php

namespace Nayjest\DI;

use Nayjest\DI\Exception\ReadonlyException;
use Nayjest\DI\Internal\Definition;

class ValueComponent extends AbstractComponent
{
    /** @var mixed */
    private $value;

    /** @var  Definition */
    private $definition;

    /**
     * Constructor.
     *
     * @param string $id
     * @param mixed $value
     * @param bool $readonly
     */
    public function __construct($id, $value = null, $readonly = false)
    {
        $this->value = $value;
        $this->definition = new Definition($id);
        $this->definition->localId = 'value';
        if ($readonly) {
            $this->definition->hasSetter = false;
        }
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        if (!$this->definition->hasSetter) {
            throw new ReadonlyException;
        }
        $this->value = $value;
        $this->notifyHub($this->definition->id);
        return $this;
    }

    protected function register(ComponentDefinitions $definitions, HubInterface $hub)
    {
        $definitions->add($this->definition);
    }

    public function uses($id, callable $function)
    {
        $this->definition->uses[$id] = $function;
        return $this;
    }

    public function usedBy($id, callable $function)
    {
        $this->definition->usedBy[$id] = $function;
        return $this;
    }
}
