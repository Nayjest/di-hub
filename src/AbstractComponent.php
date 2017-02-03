<?php

namespace Nayjest\DI;

abstract class AbstractComponent implements ComponentInterface
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
        if ($this->hub) {
            $this->hub->update($id);
        }
    }

    public function handle($message, array $arguments)
    {
        return call_user_func_array([$this, $message], $arguments);
    }
}
