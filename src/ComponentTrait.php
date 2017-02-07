<?php

namespace Nayjest\DI;

trait ComponentTrait
{
    /** @var  HubInterface */
    protected $hub;

    final protected function notifyHub($id)
    {
        if ($this->hub) {
            $this->hub->update($id);
        }
    }

    /**
     * @internal
     *
     * @param string $message
     * @param array $arguments
     * @return mixed
     */
    final public function handle($message, array $arguments)
    {
        if ($message === Hub::MESSAGE_REGISTER) {
            /** @var $this ComponentInterface|self */
            $this->hub = $arguments[1];
        }
        if (!is_string($message) && is_callable($message)) {
            return call_user_func_array($message, $arguments);
        }
        return call_user_func_array([$this, $message], $arguments);
    }
}
