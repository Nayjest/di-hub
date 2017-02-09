<?php

namespace Nayjest\DI;

use Interop\Container\ContainerInterface;

interface HubInterface extends ContainerInterface
{
    /**
     * Set's item value.
     * Throws exception if item isn't defined or has no setter.
     *
     * @api
     * @param string $id
     * @param mixed $value
     * @return $this
     */
    public function set($id, $value);

    /**
     * @param Definition $definition
     * @return $this
     */
    public function addDefinition(Definition $definition);

    /**
     * @param Definition[] $definitions
     * @return $this
     */
    public function addDefinitions(array $definitions);
}
