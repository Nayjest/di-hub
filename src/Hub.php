<?php
namespace Nayjest\DI;

class Hub implements HubInterface
{
    const MESSAGE_REGISTER = 'register';

    use HubTrait;
}
