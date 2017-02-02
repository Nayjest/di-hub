<?php

namespace Nayjest\DI\Exception;

use RuntimeException;

class ReadonlyException extends RuntimeException implements HubException
{
}
