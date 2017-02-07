<?php

namespace Nayjest\DI;

interface ComponentInterface
{
    /**
     * @internal
     *
     * @param string $message
     * @param array $arguments
     * @return mixed
     */
    public function handle($message, array $arguments);
}
