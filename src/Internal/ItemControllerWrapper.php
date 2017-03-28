<?php

namespace Nayjest\DI\Internal;
use Exception;
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
        } catch(Exception $e) {
            $this->initializingNow = false;
            throw $e;
        }
        $this->initializingNow = false;
    }

    public function &get($initialize = true)
    {
        $val = null;
        if ($this->isInitialized() || $initialize) {
            $this->initializingNow = !$this->isInitialized();
            try {
                $val =& $this->internalHub->get($this->internalId);
            } catch(Exception $e) {
                $this->initializingNow = false;
                throw $e;
            }
            $this->initializingNow = false;
        }
        return $val;
    }
}
