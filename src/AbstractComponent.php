<?php

namespace Nayjest\DI;

abstract class AbstractComponent implements ComponentInterface
{
    use ComponentTrait;

    abstract public function register(ComponentDefinitions $definitions);
}
