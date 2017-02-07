<?php

namespace Nayjest\DI;

abstract class AbstractComponent implements ComponentInterface
{
    use ComponentTrait;
    abstract protected function register(ComponentDefinitions $definitions, HubInterface $hub);
}
