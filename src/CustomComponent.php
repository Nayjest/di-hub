<?php

namespace Nayjest\DI;

use BadMethodCallException;

class CustomComponent extends Component
{
    /**
     * @var Definition[]
     */
    private $definitions = [];
    /** @var  Definition */
    private $lastDefinition;
    private $values = [];
    public $methods = [];
    private $isLocked = false;
    public static function make()
    {
        return new static;
    }

    protected function checkLock()
    {
        if ($this->isLocked){
            throw new \Exception("Can't modify definitions after registering component");
        }
    }

    public function define($id, $value = null)
    {
        $this->checkLock();
        $this->lastDefinition  = $this->definitions[] = new Definition($id, $this);
        $this->methods[ComponentMethodNaming::getter($this->lastDefinition)] = function() use($id) {
          return $this->values[$id];
        };
        $this->values[$id] = $value;
        return $this;
    }

    public function withSetter(callable $onSet = null)
    {
        $this->checkLock();
        $id = $this->lastDefinition->id;
        $this->methods[ComponentMethodNaming::setter($this->lastDefinition)] = function($newVal) use($onSet, $id) {
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
        $methodName = ComponentMethodNaming::trackedBy($this->lastDefinition, $id);
        $this->methods[$methodName] = $func;
        $this->lastDefinition->trackedBy[$id] = $methodName;
        return $this;
    }

    public function uses($id, callable $func)
    {
        $methodName = ComponentMethodNaming::tracks($this->lastDefinition, $id);
        $this->methods[$methodName] = $func;
        $this->lastDefinition->tracks[$id] =  $methodName;
        return $this;
    }


    public function register(ComponentDefinitions $definitions)
    {
        $this->isLocked = true;
        foreach($this->definitions as $src) {
            $d = $definitions->define($src->id);
            if ($src->hasSetter) {
                $d->withSetter();
            }
            foreach(array_keys($src->trackedBy) as $id) {
                $d->usedBy($id);
            }
            foreach(array_keys($src->tracks) as $id) {
                $d->uses($id);
            }
        }
    }

    public function __call($name, $arguments)
    {
        if (!array_key_exists($name, $this->methods)) {
            throw new BadMethodCallException();
        }
        return call_user_func_array($this->methods[$name], $arguments);
    }
}