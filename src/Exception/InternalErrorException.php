<?php

namespace Nayjest\DI\Exception;

use RuntimeException;

/**
 * This exception signalizes bug in Hub implementation.
 *
 * If you got InternalErrorException, please report bug here:
 * https://github.com/Nayjest/di-hub/issues
 */
class InternalErrorException extends RuntimeException implements HubException
{
}
