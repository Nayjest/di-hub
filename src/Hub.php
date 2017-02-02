<?php
namespace Nayjest\DI;
class Hub
{
    /**
     * @var Definition[]
     */
    private $definitions = [];
    private $extensions = [];
    private $initialized = [];
    private $trackStack = [];

    protected function isUsedByInitializedItems(Definition $definition)
    {
        foreach(array_keys($this->initialized) as $id) {
            if (
            $this->definitions[$id]->uses($definition->id)
                || $definition->isUsedBy($id)
            ) {
                return true;
            }
        }
        return false;
    }

    protected function registerDefinition(Definition $definition)
    {
        $id = $definition->id;
        if ($this->has($id)) {
            throw new \Exception("Can't redefine $id");
        }
        $this->definitions[$id] = $definition;
        $definition->handler = new DefinitionHandler($definition);

        // initialize immediately if definition used by initialized items
        if ($this->isUsedByInitializedItems($definition)) {
            $this->initialize($id);
        }
    }
    public function add(Component $component)
    {
        $definitions = [];
        $extensions = [];
        $component->register(
            new ComponentDefinitions(
                $definitions,
                $extensions,
                $component
            )
        );
        $component->setHub($this);

        // merge extensions
        foreach ($extensions as $id) {
            if (!array_key_exists($id, $this->extensions)) {
                $this->extensions[$id] = [];
            }
            $this->extensions[$id][] = $component;
        }

        // merge definitions
        foreach ($definitions as $definition) {
            $this->registerDefinition($definition);
        }
        return $this;
    }

    public function has($id)
    {
        return array_key_exists($id, $this->definitions);
    }

    protected function hasInitialized($id)
    {
        return array_key_exists($id, $this->initialized);
    }

    public function get($id)
    {
        $this->checkDefinitionExistence($id);
        if (!$this->hasInitialized($id)) {
            $this->initialize($id);
        }
        return $this->initialized[$id];
    }

    public function set($id, $value)
    {
        $this->checkDefinitionExistence($id);
        $this->definitions[$id]->set($value);
        return $this;
    }

    protected function checkDefinitionExistence($id)
    {
        if (!$this->has($id)) {
            throw new \Exception("has no $id");
        }
    }

    public function update($id)
    {
        if ($this->hasInitialized($id)) {
            \dump("reinitialize $id");
            $this->initialize($id);
        } else {
            \dump("no reinitialize $id required");
        }
        return $this;
    }


    protected function initialize($id)
    {
        $old = $this->hasInitialized($id) ? $this->initialized[$id] : null;
        $this->initialized[$id] = $this->definitions[$id]->handler->get();
        $this->extend($id);
        $this->trackTo($id);
        $this->trackFrom($id, $this->initialized[$id], $old);
    }

    protected function trackTo($id)
    {
        $definition = $this->definitions[$id];
        foreach (array_keys($definition->tracks) as $trackedId) {
            if (!$this->hasInitialized($trackedId)) {
                // will call trackFrom
                $this->get($trackedId);
            } else {
                $this->trackStack[] = "to($id)-i:$id.?$trackedId";
                $definition->trackFrom(
                    $trackedId,
                    $this->initialized[$id],
                    $this->initialized[$trackedId],
                    null
                );
            }
        }

        foreach ($this->definitions as $definition) {
            if (array_key_exists($id, $definition->trackedBy)) {
                if (!$this->hasInitialized($definition->id)) {
                    // will call trackFrom
                    $this->get($definition->id);
                } else {
                    $this->trackStack[] = "to($id)-e:{$definition->id}.$id";
                    $definition->trackTo(
                        $id,
                        $this->initialized[$id],
                        $this->initialized[$definition->id],
                        null
                    );
                }
            }
        }
    }

    protected function trackFrom($id, $value, $prevValue)
    {
        $definition = $this->definitions[$id];
        foreach ($definition->trackedBy as $trackedBy => $method) {
            if (!$this->hasInitialized($trackedBy)) {
                continue;
            }
            $this->trackStack[] = "from($id)-i:$id.$trackedBy";
            $definition->trackTo($trackedBy, $this->initialized[$trackedBy], $value, $prevValue);
//            if ($method === null) {
//                $method = ComponentMethodNaming::trackedBy($definition, $trackedBy);
//            }
//            $definition->component->{$method}(
//                $this->initialized[$trackedBy],
//                $value,
//                $prevValue
//            );
        };
        foreach ($this->definitions as $definition) {
            if (
                array_key_exists($id, $definition->tracks)
                && $this->hasInitialized($definition->id)
            ) {
                $this->trackStack[] = "from($id)-e:.$id.{$definition->id}";
                $definition->trackFrom($id, $this->initialized[$definition->id], $value, $prevValue);
                /*
                $definition->component->{$definition->tracks[$id]}(
                    $this->initialized[$definition->id],
                    $value,
                    $prevValue
                );
                */
            }
        }
    }

    protected function extend($id)
    {
        if (!array_key_exists($id, $this->extensions)) {
            return;
        }
        $item = $this->initialized[$id];
        foreach ($this->extensions[$id] as $component) {
            $method = 'extend' . ucfirst($id);
            $component->$method($item);
        }
    }

    public function onReplace($id, $from, $to)
    {
    }

    public function onModify($id)
    {
    }
}