<?php

namespace Nayjest\DI\Internal;

/**
 * Interface ItemControllerInterface
 * @internal
 */
interface ItemControllerInterface
{
    public function isInitialized();

    /**
     * 1) Throw Exception if definition is readonly
     * 2) Change definition source
     *
     * @param $value
     */
    public function set($value);

    public function &get();

    public function initialize();
}
