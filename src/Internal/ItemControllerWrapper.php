<?php

namespace Nayjest\DI\Internal;

use Nayjest\DI\Exception\InternalErrorException;
use Nayjest\DI\HubInterface;

/**
 * Class ItemControllerWrapper
 *
 * This class is used only with SubHub.
 *
 * @internal
 */
class ItemControllerWrapper implements ItemControllerInterface
{
    /**
     * @var
     */
    public $internalId;

    /**
     * @var HubInterface
     */
    private $internalHub;

    public $initializingNow = false;

    public function __construct($internalId, HubInterface $internalHub)
    {
        $this->internalId = $internalId;
        $this->internalHub = $internalHub;
    }

    public function isInitialized()
    {
        return $this->internalHub->isInitialized($this->internalId);
    }

    public function set($value)
    {
        $this->initializingNow = true;
        try {
            $this->internalHub->set($this->internalId, $value);
        } finally {
            $this->initializingNow = false;
        }
    }

    public function &get()
    {
        if (!$this->isInitialized()) {
            throw new InternalErrorException(
                "Trying to read uninitialized item {$this->internalId} via ItemControllerWrapper"
            );
        }
        return $this->internalHub->get($this->internalId);
    }

    public function initialize()
    {
        $this->initializingNow = !$this->isInitialized();
        $this->internalHub->get($this->internalId);
        $this->initializingNow = false;
    }
}
