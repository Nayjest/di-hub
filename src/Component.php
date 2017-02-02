<?php

namespace Nayjest\DI;

use Exception;

abstract class Component
{
    /** @var  Hub */
    protected $hub;

    abstract public function register(ComponentDefinitions $definitions);

    public function setHub(Hub $hub)
    {
        $this->hub = $hub;
    }
    protected function notifyHub($id)
    {
        if($this->hub) {
            \dump("do hub update {$id}");
            $this->hub->update($id);
        }
    }

}