<?php

namespace Nayjest\DI;

use Nayjest\DI\Exception\BadMethodCallException;
use Nayjest\DI\Exception\DefinitionLockException;
use Nayjest\DI\Internal\ComponentMethodNaming;
use Nayjest\DI\Internal\Definition;

class Component extends AbstractComponent
{
    /**
     * @var Definition[]
     */
    private $definitions = [];
    /** @var  Definition */
    private $lastDefinition;
    private $values = [];
    public $methods = [];
    private $extends = [];


    /**
     * When component registered it should be locked for definition changes.
     *
     * @var bool
     */
    private $isLocked = false;

    public static function make()
    {
        return new static;
    }

    public function define($id, $value = null)
    {
        $this->checkLock();
        $this->lastDefinition = $this->definitions[] = new Definition($id);
        $getterName = ComponentMethodNaming::getter($this->lastDefinition);
        $this->methods[$getterName] = $this->lastDefinition->getter = function () use ($id) {
            return $this->values[$id];
        };
        $this->withSetter();
        $this->values[$id] = $value;
        return $this;
    }

    public function readonly()
    {
        $this->checkLock();
        $setterName = ComponentMethodNaming::setter($this->lastDefinition);
        if (array_key_exists($setterName, $this->methods)) {
            unset($this->methods[$setterName]);
        }
        $this->lastDefinition->hasSetter = false;
        $this->lastDefinition->setter = null;
        return $this;
    }

    public function withSetter(callable $onSet = null)
    {
        $this->checkLock();
        $id = $this->lastDefinition->id;
        $setterName = ComponentMethodNaming::setter($this->lastDefinition);
        $this->methods[$setterName] = $this->lastDefinition->setter = function ($newVal) use ($onSet, $id) {
            if ($onSet) {
                $newVal = $onSet($newVal);
            }
            $this->values[$id] = $newVal;
            $this->notifyHub($id);
            return $this;
        };
        $this->lastDefinition->hasSetter = true;
        return $this;
    }

    public function usedBy($id, callable $func)
    {
        $this->checkLock();
        $this->lastDefinition->usedBy[$id] = $func;
        return $this;
    }

    public function uses($id, callable $func)
    {
        $this->checkLock();
        $this->lastDefinition->uses[$id] = $func;
        return $this;
    }

    public function __call($name, $arguments)
    {
        if (!array_key_exists($name, $this->methods)) {
            throw new BadMethodCallException();
        }
        return call_user_func_array($this->methods[$name], $arguments);
    }

    public function extend($id, callable $func)
    {
        $this->checkLock();
        $this->methods[ComponentMethodNaming::extend($id)] = $func;
        $this->extends[] = $id;
        return $this;
    }

    protected function register(ComponentDefinitions $definitions, HubInterface $hub)
    {
        $this->isLocked = true;
        foreach ($this->definitions as $definition) {
            $definitions->add($definition);
        }
        foreach ($this->extends as $id) {
            $definitions->extend($id);
        }
    }

    protected function checkLock()
    {
        if ($this->isLocked) {
            throw new DefinitionLockException(
                "Can't modify definitions after registering component."
            );
        }
    }
}
