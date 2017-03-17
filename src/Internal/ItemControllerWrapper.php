<?php

namespace Nayjest\DI\Internal;
use Nayjest\DI\HubInterface;

/**
 * Class ItemControllerWrapper
 *
 * This class is used only with SubHub.
 *
 * @internal
 */
class ItemControllerWrapper
{
    /**
     * @var
     */
    private $internalId;
    /**
     * @var HubInterface
     */
    private $internalHub;

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
        $this->internalHub->set($this->internalId, $value);
    }

    public function &get($initialize = true)
    {
        $val = null;
        if ($this->isInitialized() || $initialize) {
            $val =& $this->internalHub->get($this->internalId);
        }
        return $val;
    }
}
