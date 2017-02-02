<?php

namespace Nayjest\DI\Internal;

/**
 * Class Definition
 * @internal
 */
class Definition
{
    public function __construct($id)
    {
        $this->id = $id;
    }

    public $id;
    public $hasSetter = false;
    public $setter;
    public $getter;
    public $usedBy = [];
    public $uses = [];
    public $localId;
}
