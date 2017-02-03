<?php

namespace Nayjest\DI;

trait ComponentTrait
{
    /** @var  HubInterface */
    protected $hub;

    public function setHub(HubInterface $hub)
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
