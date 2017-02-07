<?php

namespace Nayjest\DI\Internal;

/**
 * Class Definition.
 * Stores item definition.
 *
 * @internal
 */
class Definition
{
    public function __construct($id)
    {
        $this->id = $id;
    }

    public $id;
    public $hasSetter = true;
    public $setter;
    public $getter;
    public $usedBy = [];
    public $uses = [];
    public $localId;
}
