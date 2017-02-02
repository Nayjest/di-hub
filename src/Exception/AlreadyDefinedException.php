<?php

namespace Nayjest\DI\Exception;

use RuntimeException;

class AlreadyDefinedException extends RuntimeException implements HubException
{
}
